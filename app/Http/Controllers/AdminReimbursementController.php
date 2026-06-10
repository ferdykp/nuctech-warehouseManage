<?php

namespace App\Http\Controllers;

use App\Models\Reimbursement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\Log;
use App\Exports\ReimbursementExport;
use Maatwebsite\Excel\Facades\Excel;

class AdminReimbursementController extends Controller
{
    /**
     * MENAMPILKAN LOG KLAIM (DENGAN HAK AKSES ROLE BARU)
     */
    /**
     * MENAMPILKAN LOG KLAIM (DENGAN HAK AKSES ROLE BARU DAN FILTER BULAN)
     */
    public function index(Request $request) // 🟩 Tambahkan parameter Request $request di sini
    {
        // Gunakan strtolower agar pencarian string case-insensitive aman
        $role = strtolower(auth()->user()->role ?? 'admin_site');
        $pageTitle = 'Reimbursement Claims';

        $query = Reimbursement::with('user');

        // Hak akses monitoring berkas diperluas untuk management & approval track
        if (in_array($role, ['superadmin', 'manager', 'station_master', 'team_leader'])) {
            // Jika team_leader sedang login, dia harus melihat berkas miliknya 
            // DAN berkas milik admin_site yang statusnya sudah 'pending_leader'
            if ($role === 'team_leader') {
                $query->where(function ($q) {
                    $q->where('user_id', auth()->id())
                        ->orWhere('status', 'pending_leader');
                });
            }
            // Untuk tingkat di atasnya (station_master, manager, superadmin) memantau alur sesuai porsinya
            elseif ($role === 'station_master') {
                $query->where(function ($q) {
                    $q->where('user_id', auth()->id())
                        ->orWhere('status', 'pending_station');
                });
            } elseif ($role === 'manager') {
                $query->where(function ($q) {
                    $q->where('user_id', auth()->id())
                        ->orWhere('status', 'pending_manager');
                });
            }
        } else {
            // Jika yang login adalah admin_site biasa, hanya boleh melihat buatannya sendiri
            $query->where('user_id', auth()->id());
        }

        // ── 🟩 LOGIKA FILTER BULAN BERDASARKAN INPUT REQUEST ──
        if ($request->filled('month')) {
            $query->whereMonth('date', $request->month);
        }

        // Menghitung total dana disetujui (cloning query agar filter bulan juga ikut memotong total dana)
        $totalApprovedAmount = (clone $query)->where('status', 'approved')->sum('amount');

        // Ambil data terbaru dan tambahkan appends query string agar navigasi halaman pagination tidak mereset filter bulan
        $reimbursements = $query->latest()->paginate(10)->withQueryString();

        return view('reimbursements.index', compact('reimbursements', 'pageTitle', 'totalApprovedAmount'));
    }

    public function create()
    {
        return view('reimbursements.create');
    }

    /**
     * PENGAJUAN KLAIM BARU (DENGAN STATUS AWAL DINAMIS)
     */

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
            // 🟩 PERBAIKAN: Tambahkan 'file' sebelum 'mimes' agar instance dibaca sebagai file upload utuh
            'receipt_attachment' => 'required|file|mimes:jpeg,png,jpg,pdf|max:4096',
        ]);

        $path = null;

        // Pastikan file valid saat diunggah
        if ($request->hasFile('receipt_attachment') && $request->file('receipt_attachment')->isValid()) {
            try {
                $path = $request->file('receipt_attachment')->store('receipts', 'public');
            } catch (\Exception $e) {
                // Jika gagal tulis ke folder local (masalah permission folder storage)
                return redirect()->back()->withInput()->with('error', 'Gagal menulis file ke storage. Periksa permission folder server Anda.');
            }
        } else {
            // Jika file corrupt atau melebihi php.ini post_max_size
            return redirect()->back()->withInput()->with('error', 'File lampiran tidak valid atau ukurannya melebihi batas upload server php.ini Anda.');
        }

        // ── LOGIKA DETEKSI STATUS AWAL BERDASARKAN ROLE ──
        $userRole = strtolower(auth()->user()->role ?? 'admin_site');
        $initialStatus = ($userRole === 'team_leader') ? 'pending_leader' : 'pending';

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
            'status' => $initialStatus
        ]);

        return redirect()->route('reimbursements.index')->with('success', 'Reimbursement claim filed successfully.');
    }

    /**
     * HALAMAN WORKSPACE DIGITAL SIGNATURE
     */
    public function approval($id)
    {
        $reimbursement = Reimbursement::findOrFail($id);
        $currentRole = strtolower(auth()->user()->role ?? 'admin_site');
        $myId = auth()->id();

        // Proteksi URL: admin_site tidak diizinkan membuka berkas milik admin_site lainnya
        if ($currentRole === 'admin_site' && $reimbursement->user_id !== $myId) {
            abort(403, 'Unauthorized action.');
        }

        return view('reimbursements.approval', compact('reimbursement'));
    }

    /**
     * PROSES SIMPAN TTD & TRANSISI ESTAFET STATUS BERJENJANG
     */
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
        $user          = auth()->user();
        $invoicePath   = storage_path('app/public/' . $reimbursement->receipt_attachment);
        $extension     = strtolower(pathinfo($invoicePath, PATHINFO_EXTENSION));

        // 1. Ambil input data TTD baru dari form canvas hantaran frontend
        $newSignatures = [];
        if ($request->filled('signatures_json')) {
            $decoded = json_decode($request->signatures_json, true);
            if (is_array($decoded) && count($decoded) > 0) {
                $newSignatures = $decoded;
            }
        }
        if (empty($newSignatures)) {
            $newSignatures = [[
                'image'       => $request->signature,
                'pos_x'       => $request->pos_x,
                'pos_y'       => $request->pos_y,
                'scale_w'     => $request->scale_w,
                'scale_h'     => $request->scale_h,
                'signer_name' => $user->name ?? '',
                'signer_date' => now()->format('Y-m-d'),
            ]];
        }

        // 2. LOGIKA MERGE ARRAY: Gabungkan riwayat TTD lama di DB agar tidak hilang terhapus
        $existingSignatures = json_decode($reimbursement->signatures_json, true) ?? [];
        $combinedSignatures = array_merge($existingSignatures, $newSignatures);
        $reimbursement->signatures_json = json_encode($combinedSignatures);

        // 3. Simpan file gambar TTD baru ke storage public
        $sigPaths = [];
        foreach ($newSignatures as $idx => $sig) {
            $sigData = $sig['image'];
            $imgType = 'png';
            if (preg_match('/^data:image\/(\w+);base64,/', $sigData, $m)) {
                $imgType = strtolower($m[1]);
                $sigData = substr($sigData, strpos($sigData, ',') + 1);
            }
            $sigBytes    = base64_decode($sigData);
            $sigFileName = 'signatures/sig_' . $id . '_' . $user->id . '_' . time() . '_' . $idx . '.' . $imgType;
            Storage::disk('public')->put($sigFileName, $sigBytes);

            $sigPaths[] = [
                'path'        => storage_path('app/public/' . $sigFileName),
                'pos_x'       => (float) $sig['pos_x'],
                'pos_y'       => (float) $sig['pos_y'],
                'scale_w'     => (float) $sig['scale_w'],
                'scale_h'     => (float) $sig['scale_h'],
                'signer_name' => $sig['signer_name'] ?? $user->name,
                'signer_date' => $sig['signer_date'] ?? now()->format('Y-m-d'),
            ];
        }

        // 4. Proses penyuntikan/stamp tanda tangan fisik ke dokumen berkas asli
        if ($reimbursement->receipt_attachment && file_exists($invoicePath)) {

            if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                // ── JIKA FORMAT NOTA ADALAH GAMBAR ──
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

                    if (!empty($s['signer_name'])) {
                        $textY = $pixelY + $pixelH + 4;
                        $image->text($s['signer_name'], $pixelX + ($pixelW / 2), $textY, function ($font) {
                            $font->size(11);
                            $font->color([30, 41, 59]);
                            $font->align('center');
                        });
                        if (!empty($s['signer_date'])) {
                            $image->text($s['signer_date'], $pixelX + ($pixelW / 2), $textY + 14, function ($font) {
                                $font->size(9);
                                $font->color([100, 116, 139]);
                                $font->align('center');
                            });
                        }
                    }
                }
                $image->save($invoicePath);
            } elseif ($extension === 'pdf') {
                // ── JIKA FORMAT NOTA ADALAH PDF ──
                try {
                    $pdf       = new Fpdi();
                    $pageCount = $pdf->setSourceFile($invoicePath);

                    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                        $templateId = $pdf->importPage($pageNo);
                        $size       = $pdf->getTemplateSize($templateId);
                        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                        $pdf->useTemplate($templateId);

                        $pageHeight = $size['height'];

                        foreach ($sigPaths as $s) {
                            $absYpct    = $s['pos_y'] / 100;
                            $pageIndex  = (int) floor($absYpct * $pageCount);
                            $targetPage = $pageIndex + 1;

                            if ($targetPage !== $pageNo) continue;

                            $localYpct = ($absYpct * $pageCount) - $pageIndex;

                            $mmX = ($s['pos_x']   / 100) * $size['width'];
                            $mmY = $localYpct * $pageHeight;
                            $mmW = ($s['scale_w'] / 100) * $size['width'];
                            $mmH = ($s['scale_h'] / 100) * $pageHeight;

                            if ($mmW < 5) $mmW = 30;
                            if ($mmH < 3) $mmH = 15;

                            $pdf->Image($s['path'], $mmX, $mmY, $mmW, $mmH);

                            if (!empty($s['signer_name'])) {
                                $pdf->SetFont('Helvetica', 'B', 7);
                                $pdf->SetTextColor(30, 41, 59);
                                $pdf->SetXY($mmX, $mmY + $mmH + 1);
                                $pdf->Cell($mmW, 3, $s['signer_name'], 0, 1, 'C');
                                if (!empty($s['signer_date'])) {
                                    $pdf->SetFont('Helvetica', '', 6);
                                    $pdf->SetTextColor(100, 116, 139);
                                    $pdf->SetXY($mmX, $mmY + $mmH + 3.5);
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

        // 5. ── LOGIKA TRANSISI STATUS ESTAFET WORKFLOW BERJENJANG ──
        $currentRole = strtolower($user->role ?? 'admin_site');
        $nextStatus  = 'pending';

        switch ($currentRole) {
            case 'admin_site':
                // admin_site TTD klaimnya sendiri -> Berkas naik ke meja Team Leader
                $nextStatus = 'pending_leader';
                break;

            case 'team_leader':
                // BAIK Leader TTD berkas miliknya sendiri ATAU memvalidasi milik admin_site,
                // Berkas seketika dilempar naik jenjang ke Station Master
                $nextStatus = 'pending_station';
                break;

            case 'station_master':
                // Station Master TTD -> Berkas naik ke meja Manager
                $nextStatus = 'pending_manager';
                break;

            case 'manager':
            case 'superadmin':
                // Manager/Superadmin memberikan TTD Final -> Berkas rampung sah (Approved)
                $nextStatus = 'approved';
                break;
        }

        // Simpan semua akumulasi perubahan ke dalam database
        // $reimbursement->status = $nextStatus;
        $reimbursement->status = (string) trim($nextStatus);
        if ($nextStatus === 'approved') {
            $reimbursement->approved_by = $user->id;
            $reimbursement->digital_signature = $sigPaths[0]['path'] ?? null;
        }
        $reimbursement->save();

        return redirect()->route('reimbursements.index')->with('success', 'Dokumen berhasil ditandatangani. Status saat ini: ' . strtoupper($nextStatus));
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

    // public function destroy($id)
    // {
    //     // Pembatalan pengajuan berkas diperbolehkan jika berstatus pending (admin_site) atau pending_leader (Team Leader)
    //     // $reimbursement = Reimbursement::where('user_id', auth()->id())
    //     //     ->whereIn('status', ['pending', 'pending_leader', 'pending_station', 'pending_manager'])
    //     //     ->findOrFail($id);

    //     // if ($reimbursement->receipt_attachment) {
    //     //     Storage::disk('public')->delete($reimbursement->receipt_attachment);
    //     // }
    //     $reimbursement = Reimbursement::findOrFail($id);

    //     $reimbursement->delete();
    //     return redirect()->route('reimbursements.index')->with('success', 'Claim canceled successfully.');
    // }
    public function destroy($id)
    {
        // Mencari data reimbursement tanpa memedulikan status atau user_id (Bisa dihapus di semua kondisi)
        $reimbursement = Reimbursement::findOrFail($id);

        // Hapus berkas lampiran dari storage jika ada sebelum data di database dihapus
        if ($reimbursement->receipt_attachment) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($reimbursement->receipt_attachment);
        }

        // Hapus data dari database
        $reimbursement->delete();

        return redirect()->route('reimbursements.index')->with('success', 'Claim canceled successfully.');
    }

    public function show($id)
    {
        $reimbursement = Reimbursement::findOrFail($id);
        return view('reimbursements.index', compact('reimbursement'));
    }


    // 🟩 TAMBAHKAN: parameter Request $request di dalam kurung fungsi
    // public function exportApprovedPdf(Request $request)
    // {
    //     // 1. Inisialisasi query awal untuk klaim yang disetujui (Approved)
    //     $query = Reimbursement::where('status', 'approved')->with('user');

    //     // 🟩 TAMBAHKAN FILTER BULAN: Jika ada parameter 'month' di URL, saring PDF-nya
    //     if ($request->filled('month')) {
    //         $query->whereMonth('date', $request->month);
    //     }

    //     // Ambil hasil data yang sudah disaring
    //     $reimbursements = $query->latest()->get();

    //     if ($reimbursements->isEmpty()) {
    //         return redirect()->back()->with('error', 'Tidak ada data reimbursement APPROVED untuk bulan yang dipilih.');
    //     }

    //     // Inisialisasi FPDI (Landscape, milimeter, A4)
    //     $pdf = new Fpdi('L', 'mm', 'A4');
    //     $pdf->SetAutoPageBreak(false);

    //     // Dimensi standar A4 Landscape
    //     $canvasWidth  = 297;
    //     $canvasHeight = 210;

    //     // Aturan Layout Grid Berdampingan
    //     $marginOuter  = 10;
    //     $gapCenter    = 6;
    //     $maxPageWidth = ($canvasWidth - ($marginOuter * 2) - $gapCenter) / 2;
    //     $maxPageHeight = $canvasHeight - 20;

    //     // Koleksi semua halaman/gambar yang siap dicetak ke dalam antrean tunggal
    //     $documentQueue = [];

    //     foreach ($reimbursements as $reimbursement) {
    //         $invoicePath = storage_path('app/public/' . $reimbursement->receipt_attachment);

    //         if (!$reimbursement->receipt_attachment || !file_exists($invoicePath)) {
    //             continue;
    //         }

    //         $extension = strtolower(pathinfo($invoicePath, PATHINFO_EXTENSION));

    //         if ($extension === 'pdf') {
    //             try {
    //                 $subPdf = new Fpdi();
    //                 $pageCount = $subPdf->setSourceFile($invoicePath);

    //                 for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
    //                     $documentQueue[] = [
    //                         'type' => 'pdf',
    //                         'file' => $invoicePath,
    //                         'page_no' => $pageNo
    //                     ];
    //                 }
    //             } catch (\Exception $e) {
    //                 \Log::error("Gagal membaca file PDF " . $invoicePath . " : " . $e->getMessage());
    //                 continue;
    //             }
    //         } elseif (in_array($extension, ['jpg', 'jpeg', 'png'])) {
    //             $documentQueue[] = [
    //                 'type' => 'image',
    //                 'file' => $invoicePath
    //             ];
    //         }
    //     }

    //     // 2. Proses pengurasan antrean untuk dicetak berpasangan (Side-by-Side)
    //     $totalItems = count($documentQueue);

    //     for ($i = 0; $i < $totalItems; $i += 2) {
    //         $pdf->AddPage('L', [$canvasWidth, $canvasHeight]);

    //         // DOKUMEN SISI KIRI
    //         $leftItem = $documentQueue[$i];
    //         $x1 = $marginOuter;
    //         $y1 = ($canvasHeight - $maxPageHeight) / 2;

    //         if ($leftItem['type'] === 'pdf') {
    //             $pdf->setSourceFile($leftItem['file']);
    //             $tplId = $pdf->importPage($leftItem['page_no']);
    //             $size  = $pdf->getTemplateSize($tplId);
    //             $ratio = $size['width'] / $size['height'];

    //             $w1 = $maxPageWidth;
    //             $h1 = $w1 / $ratio;
    //             if ($h1 > $maxPageHeight) {
    //                 $h1 = $maxPageHeight;
    //                 $w1 = $h1 * $ratio;
    //             }
    //             $x1_centered = $x1 + (($maxPageWidth - $w1) / 2);
    //             $y1_centered = ($canvasHeight - $h1) / 2;

    //             $pdf->useTemplate($tplId, $x1_centered, $y1_centered, $w1, $h1);
    //         } else {
    //             list($imgWidth, $imgHeight) = getimagesize($leftItem['file']);
    //             $ratio = $imgWidth / $imgHeight;

    //             $w1 = $maxPageWidth;
    //             $h1 = $w1 / $ratio;
    //             if ($h1 > $maxPageHeight) {
    //                 $h1 = $maxPageHeight;
    //                 $w1 = $h1 * $ratio;
    //             }
    //             $x1_centered = $x1 + (($maxPageWidth - $w1) / 2);
    //             $y1_centered = ($canvasHeight - $h1) / 2;

    //             $pdf->Image($leftItem['file'], $x1_centered, $y1_centered, $w1, $h1);
    //         }

    //         $pdf->SetDrawColor(40, 40, 40);
    //         $pdf->SetLineWidth(0.3);
    //         $pdf->Rect($x1, $y1, $maxPageWidth, $maxPageHeight);

    //         // DOKUMEN SISI KANAN
    //         if (isset($documentQueue[$i + 1])) {
    //             $rightItem = $documentQueue[$i + 1];
    //             $x2 = $marginOuter + $maxPageWidth + $gapCenter;
    //             $y2 = ($canvasHeight - $maxPageHeight) / 2;

    //             if ($rightItem['type'] === 'pdf') {
    //                 $pdf->setSourceFile($rightItem['file']);
    //                 $tplId = $pdf->importPage($rightItem['page_no']);
    //                 $size  = $pdf->getTemplateSize($tplId);
    //                 $ratio = $size['width'] / $size['height'];

    //                 $w2 = $maxPageWidth;
    //                 $h2 = $w2 / $ratio;
    //                 if ($h2 > $maxPageHeight) {
    //                     $h2 = $maxPageHeight;
    //                     $w2 = $h2 * $ratio;
    //                 }
    //                 $x2_centered = $x2 + (($maxPageWidth - $w2) / 2);
    //                 $y2_centered = ($canvasHeight - $h2) / 2;

    //                 $pdf->useTemplate($tplId, $x2_centered, $y2_centered, $w2, $h2);
    //             } else {
    //                 list($imgWidth, $imgHeight) = getimagesize($rightItem['file']);
    //                 $ratio = $imgWidth / $imgHeight;

    //                 $w2 = $maxPageWidth;
    //                 $h2 = $w2 / $ratio;
    //                 if ($h2 > $maxPageHeight) {
    //                     $h2 = $maxPageHeight;
    //                     $w2 = $h2 * $ratio;
    //                 }
    //                 $x2_centered = $x2 + (($maxPageWidth - $w2) / 2);
    //                 $y2_centered = ($canvasHeight - $h2) / 2;

    //                 $pdf->Image($rightItem['file'], $x2_centered, $y2_centered, $w2, $h2);
    //             }

    //             $pdf->Rect($x2, $y2, $maxPageWidth, $maxPageHeight);
    //         }
    //     }

    //     // Dinamis penamaan file berdasarkan parameter bulan
    //     $monthName = $request->filled('month') ? date('F', mktime(0, 0, 0, $request->month, 10)) : 'All_Months';
    //     $fileName = "reimbursements_approved_{$monthName}_" . now()->format('Y') . ".pdf";

    //     return response($pdf->Output('S', $fileName))
    //         ->header('Content-Type', 'application/pdf')
    //         ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    // }
    public function exportApprovedPdf(Request $request)
    {
        // 1. Inisialisasi query awal untuk klaim yang disetujui (Approved)
        $query = Reimbursement::where('status', 'approved')->with('user');

        if ($request->filled('month')) {
            $query->whereMonth('date', $request->month);
        }

        $reimbursements = $query->latest()->get();

        if ($reimbursements->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada data reimbursement APPROVED untuk bulan yang dipilih.');
        }

        // Inisialisasi FPDI (Landscape, milimeter, A4)
        $pdf = new Fpdi('L', 'mm', 'A4');
        $pdf->SetAutoPageBreak(false);

        $canvasWidth  = 297;
        $canvasHeight = 210;

        $marginOuter  = 10;
        $gapCenter    = 6;
        $maxPageWidth = ($canvasWidth - ($marginOuter * 2) - $gapCenter) / 2;
        $maxPageHeight = $canvasHeight - 20;

        // Koleksi semua halaman/gambar yang siap dicetak ke dalam antrean tunggal
        $documentQueue = [];

        // 🟩 Tambah counter untuk melacak nomor urut file claim
        $claimCounter = 1;

        foreach ($reimbursements as $reimbursement) {
            $invoicePath = storage_path('app/public/' . $reimbursement->receipt_attachment);

            if (!$reimbursement->receipt_attachment || !file_exists($invoicePath)) {
                continue;
            }

            $extension = strtolower(pathinfo($invoicePath, PATHINFO_EXTENSION));

            if ($extension === 'pdf') {
                try {
                    $subPdf = new Fpdi();
                    $pageCount = $subPdf->setSourceFile($invoicePath);

                    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                        $documentQueue[] = [
                            'type' => 'pdf',
                            'file' => $invoicePath,
                            'page_no' => $pageNo,
                            'claim_no' => $claimCounter // 🟩 Pasang nomor urut klaim
                        ];
                    }
                } catch (\Exception $e) {
                    \Log::error("Gagal membaca file PDF " . $invoicePath . " : " . $e->getMessage());
                    continue;
                }
            } elseif (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                $documentQueue[] = [
                    'type' => 'image',
                    'file' => $invoicePath,
                    'claim_no' => $claimCounter // 🟩 Pasang nomor urut klaim
                ];
            }

            // 🟩 Naikkan nomor urut setelah satu file claim selesai diproses ke antrean
            $claimCounter++;
        }

        // 2. Proses pengurasan antrean untuk dicetak berpasangan (Side-by-Side)
        $totalItems = count($documentQueue);

        for ($i = 0; $i < $totalItems; $i += 2) {
            $pdf->AddPage('L', [$canvasWidth, $canvasHeight]);

            // -----------------------------------------------------------------
            // DOKUMEN SISI KIRI
            // -----------------------------------------------------------------
            $leftItem = $documentQueue[$i];
            $x1 = $marginOuter;
            $y1 = ($canvasHeight - $maxPageHeight) / 2;

            if ($leftItem['type'] === 'pdf') {
                $pdf->setSourceFile($leftItem['file']);
                $tplId = $pdf->importPage($leftItem['page_no']);
                $size  = $pdf->getTemplateSize($tplId);
                $ratio = $size['width'] / $size['height'];

                $w1 = $maxPageWidth;
                $h1 = $w1 / $ratio;
                if ($h1 > $maxPageHeight) {
                    $h1 = $maxPageHeight;
                    $w1 = $h1 * $ratio;
                }
                $x1_centered = $x1 + (($maxPageWidth - $w1) / 2);
                $y1_centered = ($canvasHeight - $h1) / 2;

                $pdf->useTemplate($tplId, $x1_centered, $y1_centered, $w1, $h1);
            } else {
                list($imgWidth, $imgHeight) = getimagesize($leftItem['file']);
                $ratio = $imgWidth / $imgHeight;

                $w1 = $maxPageWidth;
                $h1 = $w1 / $ratio;
                if ($h1 > $maxPageHeight) {
                    $h1 = $maxPageHeight;
                    $w1 = $h1 * $ratio;
                }
                $x1_centered = $x1 + (($maxPageWidth - $w1) / 2);
                $y1_centered = ($canvasHeight - $h1) / 2;

                $pdf->Image($leftItem['file'], $x1_centered, $y1_centered, $w1, $h1);
            }

            // CETAK BORDER UNTUK SISI KIRI
            $pdf->SetDrawColor(40, 40, 40);
            $pdf->SetLineWidth(0.3);
            $pdf->Rect($x1, $y1, $maxPageWidth, $maxPageHeight);

            // 🟩 CETAK NOMOR URUT DI POJOK KANAN BAWAH BINGKAI KIRI
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->SetTextColor(40, 40, 40);
            // Geser posisi X mendekati batas kanan bingkai kiri (x1 + lebar - margin teks)
            $pdf->SetXY($x1 + $maxPageWidth - 20, $y1 + $maxPageHeight - 8);
            $pdf->Cell(15, 5, 'No. ' . $leftItem['claim_no'], 0, 0, 'R');


            // -----------------------------------------------------------------
            // DOKUMEN SISI KANAN (Jika Tersedia)
            // -----------------------------------------------------------------
            if (isset($documentQueue[$i + 1])) {
                $rightItem = $documentQueue[$i + 1];
                $x2 = $marginOuter + $maxPageWidth + $gapCenter;
                $y2 = ($canvasHeight - $maxPageHeight) / 2;

                if ($rightItem['type'] === 'pdf') {
                    $pdf->setSourceFile($rightItem['file']);
                    $tplId = $pdf->importPage($rightItem['page_no']);
                    $size  = $pdf->getTemplateSize($tplId);
                    $ratio = $size['width'] / $size['height'];

                    $w2 = $maxPageWidth;
                    $h2 = $w2 / $ratio;
                    if ($h2 > $maxPageHeight) {
                        $h2 = $maxPageHeight;
                        $w2 = $h2 * $ratio;
                    }
                    $x2_centered = $x2 + (($maxPageWidth - $w2) / 2);
                    $y2_centered = ($canvasHeight - $h2) / 2;

                    $pdf->useTemplate($tplId, $x2_centered, $y2_centered, $w2, $h2);
                } else {
                    list($imgWidth, $imgHeight) = getimagesize($rightItem['file']);
                    $ratio = $imgWidth / $imgHeight;

                    $w2 = $maxPageWidth;
                    $h2 = $w2 / $ratio;
                    if ($h2 > $maxPageHeight) {
                        $h2 = $maxPageHeight;
                        $w2 = $h2 * $ratio;
                    }
                    $x2_centered = $x2 + (($maxPageWidth - $w2) / 2);
                    $y2_centered = ($canvasHeight - $h2) / 2;

                    $pdf->Image($rightItem['file'], $x2_centered, $y2_centered, $w2, $h2);
                }

                // CETAK BORDER UNTUK SISI KANAN
                $pdf->Rect($x2, $y2, $maxPageWidth, $maxPageHeight);

                // 🟩 CETAK NOMOR URUT DI POJOK KANAN BAWAH BINGKAI KANAN
                $pdf->SetFont('Helvetica', 'B', 10);
                $pdf->SetTextColor(40, 40, 40);
                // Geser posisi X mendekati batas kanan bingkai kanan (x2 + lebar - margin teks)
                $pdf->SetXY($x2 + $maxPageWidth - 20, $y2 + $maxPageHeight - 8);
                $pdf->Cell(15, 5, 'No. ' . $rightItem['claim_no'], 0, 0, 'R');
            }
        }

        $monthName = $request->filled('month') ? date('F', mktime(0, 0, 0, $request->month, 10)) : 'All_Months';
        $fileName = "reimbursements_approved_{$monthName}_" . now()->format('Y') . ".pdf";

        return response($pdf->Output('S', $fileName))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    public function exportExcel(Request $request)
    {
        $search = $request->get('search');

        // Menamai berkas sesuai format nama user dan bulan berjalan seperti gambar contoh
        $fileName = 'Ferdy_Software_Expense_Record_' . now()->format('F_Y') . '.xlsx';

        return Excel::download(new ReimbursementExport($search), $fileName);
    }

    public function exportSinglePdf($id)
    {
        // 1. Cari data reimbursement spesifik berdasarkan ID
        $reimbursement = Reimbursement::findOrFail($id);

        $invoicePath = storage_path('app/public/' . $reimbursement->receipt_attachment);

        if (!$reimbursement->receipt_attachment || !file_exists($invoicePath)) {
            return redirect()->back()->with('error', 'Berkas nota bukti lampiran tidak ditemukan fisik datanya.');
        }

        // Inisialisasi FPDI (Landscape, milimeter, A4)
        $pdf = new Fpdi('L', 'mm', 'A4');
        $pdf->SetAutoPageBreak(false);

        $canvasWidth  = 297;
        $canvasHeight = 210;

        // Aturan Layout Grid Berdampingan (Menjaga estetika layout yang sama)
        $marginOuter  = 10;
        $gapCenter    = 6;
        $maxPageWidth = ($canvasWidth - ($marginOuter * 2) - $gapCenter) / 2;
        $maxPageHeight = $canvasHeight - 20;

        $extension = strtolower(pathinfo($invoicePath, PATHINFO_EXTENSION));
        $documentQueue = [];

        // Pecah halaman dokumen ke dalam antrean tunggal
        if ($extension === 'pdf') {
            try {
                $subPdf = new Fpdi();
                $pageCount = $subPdf->setSourceFile($invoicePath);
                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    $documentQueue[] = ['type' => 'pdf', 'file' => $invoicePath, 'page_no' => $pageNo];
                }
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Gagal memproses struktur internal PDF lampiran.');
            }
        } elseif (in_array($extension, ['jpg', 'jpeg', 'png'])) {
            $documentQueue[] = ['type' => 'image', 'file' => $invoicePath];
        }

        // Proses pembuatan lembar PDF cetak berpasangan
        $totalItems = count($documentQueue);
        for ($i = 0; $i < $totalItems; $i += 2) {
            $pdf->AddPage('L', [$canvasWidth, $canvasHeight]);

            // --- SISI KIRI ---
            $leftItem = $documentQueue[$i];
            $x1 = $marginOuter;
            $y1 = ($canvasHeight - $maxPageHeight) / 2;

            if ($leftItem['type'] === 'pdf') {
                $pdf->setSourceFile($leftItem['file']);
                $tplId = $pdf->importPage($leftItem['page_no']);
                $size  = $pdf->getTemplateSize($tplId);
                $ratio = $size['width'] / $size['height'];

                $w1 = $maxPageWidth;
                $h1 = $w1 / $ratio;
                if ($h1 > $maxPageHeight) {
                    $h1 = $maxPageHeight;
                    $w1 = $h1 * $ratio;
                }
                $pdf->useTemplate($tplId, $x1 + (($maxPageWidth - $w1) / 2), ($canvasHeight - $h1) / 2, $w1, $h1);
            } else {
                list($imgWidth, $imgHeight) = getimagesize($leftItem['file']);
                $ratio = $imgWidth / $imgHeight;
                $w1 = $maxPageWidth;
                $h1 = $w1 / $ratio;
                if ($h1 > $maxPageHeight) {
                    $h1 = $maxPageHeight;
                    $w1 = $h1 * $ratio;
                }
                $pdf->Image($leftItem['file'], $x1 + (($maxPageWidth - $w1) / 2), ($canvasHeight - $h1) / 2, $w1, $h1);
            }
            // Border Kiri
            $pdf->SetDrawColor(40, 40, 40);
            $pdf->SetLineWidth(0.3);
            $pdf->Rect($x1, $y1, $maxPageWidth, $maxPageHeight);

            // --- SISI KANAN (Jika halaman berkas bertipe multi-page / ada lembar ke-2) ---
            if (isset($documentQueue[$i + 1])) {
                $rightItem = $documentQueue[$i + 1];
                $x2 = $marginOuter + $maxPageWidth + $gapCenter;
                $y2 = $y1;

                if ($rightItem['type'] === 'pdf') {
                    $pdf->setSourceFile($rightItem['file']);
                    $tplId = $pdf->importPage($rightItem['page_no']);
                    $size  = $pdf->getTemplateSize($tplId);
                    $ratio = $size['width'] / $size['height'];

                    $w2 = $maxPageWidth;
                    $h2 = $w2 / $ratio;
                    if ($h2 > $maxPageHeight) {
                        $h2 = $maxPageHeight;
                        $w2 = $h2 * $ratio;
                    }
                    $pdf->useTemplate($tplId, $x2 + (($maxPageWidth - $w2) / 2), ($canvasHeight - $h2) / 2, $w2, $h2);
                } else {
                    list($imgWidth, $imgHeight) = getimagesize($rightItem['file']);
                    $ratio = $imgWidth / $imgHeight;
                    $w2 = $maxPageWidth;
                    $h2 = $w2 / $ratio;
                    if ($h2 > $maxPageHeight) {
                        $h2 = $maxPageHeight;
                        $w2 = $h2 * $ratio;
                    }
                    $pdf->Image($rightItem['file'], $x2 + (($maxPageWidth - $w2) / 2), ($canvasHeight - $h2) / 2, $w2, $h2);
                }
                // Border Kanan
                $pdf->Rect($x2, $y2, $maxPageWidth, $maxPageHeight);
            }
        }

        $filename = 'invoice_' . strtolower(str_replace(' ', '_', $reimbursement->person_name)) . '_' . $reimbursement->id . '.pdf';

        return response($pdf->Output('S', $filename))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
