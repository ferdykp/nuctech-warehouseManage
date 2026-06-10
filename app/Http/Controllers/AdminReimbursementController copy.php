<?php

namespace App\Http\Controllers;

use App\Models\Reimbursement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use setasign\Fpdi\Fpdi; // 🛠️ Tambahkan ini untuk memanipulasi PDF
use Illuminate\Support\Facades\Log; // 🛠️ Tambahkan ini untuk logging error


class AdminReimbursementController extends Controller
{
    public function index(Request $request) // 🟩 Tambahkan parameter Request $request di sini
    {
        $role = auth()->user()->role ?? 'staff';
        $pageTitle = 'Reimbursement Claims';

        $query = Reimbursement::with('user');

        if (!in_array($role, ['superadmin', 'manager'])) {
            $query->where('user_id', auth()->id());
        }

        // ── 🟩 LOGIKA FILTER BULAN BERDASARKAN INPUT REQUEST ──
        if ($request->filled('month')) {
            $query->whereMonth('date', $request->month);
        }

        // Hitung total dana disetujui (cloning query agar filter bulan juga ikut memotong total dana)
        $totalApprovedAmount = (clone $query)->where('status', 'approved')->sum('amount');

        // Ambil data terbaru dan tambahkan appends query string untuk pagination yang aman
        $reimbursements = $query->latest()->paginate(10)->withQueryString();

        return view('reimbursements.index', compact('reimbursements', 'pageTitle', 'totalApprovedAmount'));
    }

    public function create()
    {
        return view('reimbursements.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'person_name' => 'required|string|max:255',
            'date' => 'required|date',
            'category' => 'required|in:transportation,delivery,office',
            'from_location' => 'required_if:category,transportation,delivery|nullable|string|max:255',
            'to_location' => 'required_if:category,transportation,delivery|nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'comment' => 'nullable|string|max:1000',
            'receipt_attachment' => 'required|mimes:jpeg,png,jpg,pdf|max:4096',
        ]);

        $path = null;
        if ($request->hasFile('receipt_attachment')) {
            $path = $request->file('receipt_attachment')->store('receipts', 'public');
        }

        Reimbursement::create([
            'user_id' => auth()->id(),
            'person_name' => $request->person_name,
            'date' => $request->date,
            'category' => $request->category,
            'from_location' => in_array($request->category, ['transportation', 'delivery']) ? $request->from_location : null,
            'to_location' => in_array($request->category, ['transportation', 'delivery']) ? $request->to_location : null,
            'amount' => $request->amount,
            'comment' => $request->comment,
            'receipt_attachment' => $path,
            'status' => 'pending'
        ]);

        return redirect()->route('reimbursements.index')->with('success', 'Reimbursement claim filed successfully.');
    }

    public function approval($id)
    {
        $reimbursement = Reimbursement::findOrFail($id);
        return view('reimbursements.approval', compact('reimbursement'));
    }

    public function approve(Request $request, $id)
    {
        $request->validate([
            'signature'       => 'required|string',
            'pos_x'           => 'required|numeric',
            'pos_y'           => 'required|numeric',
            'scale_w'         => 'required|numeric',
            'scale_h'         => 'required|numeric',
            'signatures_json' => 'nullable|string',
        ]);

        $reimbursement = Reimbursement::findOrFail($id);
        $invoicePath   = storage_path('app/public/' . $reimbursement->receipt_attachment);
        $extension     = strtolower(pathinfo($invoicePath, PATHINFO_EXTENSION));

        // ── Decode daftar TTD: pakai signatures_json jika ada, fallback ke single-sig lama ──
        $signatures = [];
        if ($request->filled('signatures_json')) {
            $decoded = json_decode($request->signatures_json, true);
            if (is_array($decoded) && count($decoded) > 0) {
                $signatures = $decoded;
            }
        }
        if (empty($signatures)) {
            // Fallback backward-compat: satu TTD dari field lama
            $signatures = [[
                'image'       => $request->signature,
                'pos_x'       => $request->pos_x,
                'pos_y'       => $request->pos_y,
                'scale_w'     => $request->scale_w,
                'scale_h'     => $request->scale_h,
                'signer_name' => '',
                'signer_date' => '',
            ]];
        }

        // ── Simpan semua file gambar TTD ke storage ──
        $sigPaths = [];
        foreach ($signatures as $idx => $sig) {
            $sigData = $sig['image'];
            $imgType = 'png';
            if (preg_match('/^data:image\/(\w+);base64,/', $sigData, $m)) {
                $imgType = strtolower($m[1]);
                $sigData = substr($sigData, strpos($sigData, ',') + 1);
            }
            $sigBytes    = base64_decode($sigData);
            $sigFileName = 'signatures/sig_' . $id . '_' . $idx . '_' . time() . '.' . $imgType;
            Storage::disk('public')->put($sigFileName, $sigBytes);
            $sigPaths[$idx] = [
                'path'        => storage_path('app/public/' . $sigFileName),
                'pos_x'       => (float) $sig['pos_x'],
                'pos_y'       => (float) $sig['pos_y'],
                'scale_w'     => (float) $sig['scale_w'],
                'scale_h'     => (float) $sig['scale_h'],
                'signer_name' => $sig['signer_name'] ?? '',
                'signer_date' => $sig['signer_date'] ?? '',
            ];
        }

        // ── Tempel semua TTD ke dokumen ──
        if ($reimbursement->receipt_attachment && file_exists($invoicePath)) {

            if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                // ── FORMAT GAMBAR ──
                $manager = new ImageManager(new Driver());
                $image   = $manager->read($invoicePath);

                foreach ($sigPaths as $s) {
                    $pixelX = (int) round(($s['pos_x'] / 100) * $image->width());
                    $pixelY = (int) round(($s['pos_y'] / 100) * $image->height());
                    $pixelW = (int) round(($s['scale_w'] / 100) * $image->width());
                    $pixelH = (int) round(($s['scale_h'] / 100) * $image->height());
                    if ($pixelW < 20) $pixelW = 100;
                    if ($pixelH < 10) $pixelH = 50;

                    $sigImg = $manager->read($s['path'])->resize($pixelW, $pixelH);
                    $image->place($sigImg, 'top-left', $pixelX, $pixelY);

                    // Tambah teks nama & tanggal di bawah TTD
                    if (!empty($s['signer_name'])) {
                        $textY = $pixelY + $pixelH + 4;
                        $image->text($s['signer_name'], $pixelX + ($pixelW / 2), $textY, function ($font) {
                            $font->size(12);
                            $font->color([30, 41, 59]);
                            $font->align('center');
                        });
                        if (!empty($s['signer_date'])) {
                            $image->text($s['signer_date'], $pixelX + ($pixelW / 2), $textY + 16, function ($font) {
                                $font->size(10);
                                $font->color([100, 116, 139]);
                                $font->align('center');
                            });
                        }
                    }
                }
                $image->save($invoicePath);
            } elseif ($extension === 'pdf') {
                // ── FORMAT PDF ──
                try {
                    $pdf       = new Fpdi();
                    $pageCount = $pdf->setSourceFile($invoicePath);

                    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                        $templateId = $pdf->importPage($pageNo);
                        $size       = $pdf->getTemplateSize($templateId);
                        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                        $pdf->useTemplate($templateId);

                        // Hitung tinggi total semua halaman PDF (untuk konversi % posisi)
                        // Frontend mengukur posY terhadap scrollHeight workspace (1500px render).
                        // Kita perlu tahu halaman mana stamp ini berada dan offset-nya.
                        $totalPdfHeight = $size['height'] * $pageCount; // estimasi sederhana
                        $pageHeight     = $size['height'];

                        foreach ($sigPaths as $s) {
                            // pos_y dihitung dari scrollHeight (1500px). Konversi ke mm.
                            // Proporsi posisi di dalam dokumen penuh
                            $absYpct = $s['pos_y'] / 100; // 0.0 – 1.0
                            // Halaman mana (0-indexed)
                            $pageIndex  = (int) floor($absYpct * $pageCount);
                            $targetPage = $pageIndex + 1;

                            if ($targetPage !== $pageNo) continue;

                            // Posisi dalam halaman ini
                            $localYpct = ($absYpct * $pageCount) - $pageIndex;

                            $mmX = ($s['pos_x']   / 100) * $size['width'];
                            $mmY = $localYpct * $pageHeight;
                            $mmW = ($s['scale_w'] / 100) * $size['width'];
                            $mmH = ($s['scale_h'] / 100) * $pageHeight;

                            if ($mmW < 5) $mmW = 30;
                            if ($mmH < 3) $mmH = 15;

                            $pdf->Image($s['path'], $mmX, $mmY, $mmW, $mmH);

                            // Nama & tanggal penanda tangan di bawah TTD
                            if (!empty($s['signer_name'])) {
                                $pdf->SetFont('Helvetica', 'B', 7);
                                $pdf->SetTextColor(30, 41, 59);
                                $pdf->SetXY($mmX, $mmY + $mmH + 1);
                                $pdf->Cell($mmW, 3, $s['signer_name'], 0, 1, 'C');
                                if (!empty($s['signer_date'])) {
                                    $pdf->SetFont('Helvetica', '', 6);
                                    $pdf->SetTextColor(100, 116, 139);
                                    $pdf->SetXY($mmX, $mmY + $mmH + 4);
                                    $pdf->Cell($mmW, 3, $s['signer_date'], 0, 1, 'C');
                                }
                            }
                        }
                    }

                    $pdf->Output($invoicePath, 'F');
                } catch (\Exception $e) {
                    Log::error("Gagal menyuntikkan TTD ke PDF: " . $e->getMessage());
                }
            }
        }

        // Simpan nama file TTD pertama sebagai referensi di DB
        $firstSigFile = count($sigPaths) > 0
            ? 'signatures/sig_' . $id . '_0_' . array_key_first($sigPaths) . '.' . pathinfo($sigPaths[0]['path'], PATHINFO_EXTENSION)
            : null;

        $reimbursement->update([
            'status'            => 'approved',
            'approved_by'       => auth()->id(),
            'digital_signature' => $sigPaths[0]['path'] ?? null,
        ]);

        return redirect()->route('reimbursements.index')->with('success', 'Claim approved and document signed successfully.');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejected_reason' => 'required|string|max:500'
        ]);

        $reimbursement = Reimbursement::findOrFail($id);
        $reimbursement->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'rejected_reason' => $request->rejected_reason
        ]);

        return redirect()->route('reimbursements.index')->with('success', 'Claim rejected successfully.');
    }

    public function destroy($id)
    {
        $reimbursement = Reimbursement::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->findOrFail($id);

        if ($reimbursement->receipt_attachment) {
            Storage::disk('public')->delete($reimbursement->receipt_attachment);
        }

        $reimbursement->delete();
        return redirect()->route('reimbursements.index')->with('success', 'Claim canceled and removed successfully.');
    }
}
