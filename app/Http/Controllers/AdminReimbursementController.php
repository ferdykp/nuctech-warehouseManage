<?php

namespace App\Http\Controllers;

use App\Models\Reimbursement;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use setasign\Fpdi\Fpdi;
use App\Exports\ReimbursementExport;
use Maatwebsite\Excel\Facades\Excel;

class AdminReimbursementController extends Controller
{
    /**
     * MENAMPILKAN LOG KLAIM (AJAX + DEBOUNCE READY)
     */
    public function index(Request $request)
    {
        $role = strtolower(auth()->user()->role ?? 'employee_role');
        $pageTitle = 'Reimbursement Claims';

        $query = Reimbursement::with('user');
        $pdfBase64 = null;

        // 1. Filter Role
        if (in_array($role, ['superadmin', 'manager', 'station_master', 'team_leader'])) {
            if ($role === 'team_leader') {
                $query->where(function ($q) {
                    $q->where('user_id', auth()->id())
                        ->orWhere('status', 'pending_leader');
                });
            } elseif ($role === 'station_master') {
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
            $query->where('user_id', auth()->id());
        }

        // 2. Filter Bulan
        if ($request->filled('month')) {
            $query->whereMonth('date', $request->month);
        }

        // 3. Filter Search (Server-side)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('person_name', 'like', "%{$search}%")
                    ->orWhere('comment', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('from_location', 'like', "%{$search}%")
                    ->orWhere('to_location', 'like', "%{$search}%");
            });
        }

        $totalApprovedAmount = (clone $query)->sum('amount');

        // 4. Pagination
        $reimbursements = $query
            ->orderByRaw("FIELD(category, 'transportation', 'delivery', 'office')")
            ->orderBy('person_name', 'asc')
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->paginate(10)
            ->withQueryString();

        return view('reimbursements.index', compact('reimbursements', 'pageTitle', 'totalApprovedAmount', 'pdfBase64'));
    }

    public function create()
    {
        $user = auth()->user();
        $employeesQuery = Employee::with('site');

        // Jika BUKAN superadmin dan akun terikat pada site_id tertentu
        if ($user && $user->role !== 'superadmin' && $user->site_id) {
            $employeesQuery->where('site_id', $user->site_id);
        }

        $employees = $employeesQuery->orderBy('name', 'asc')->get();

        return view('reimbursements.create', compact('employees'));
    }

    /**
     * PENGAJUAN KLAIM BARU
     */
    public function store(Request $request)
    {
        $request->validate([
            'person_name' => 'required|string|max:255',
            'date' => 'required|date',
            'category' => 'required|in:transportation,delivery,office',
            'amount' => 'required|numeric|min:0',
            'receipt_attachment' => 'required|file|mimes:jpeg,png,jpg,pdf|max:4096',
        ]);

        $file = $request->file('receipt_attachment');
        $extension = strtolower($file->getClientOriginalExtension());
        $excludedPages = json_decode($request->excluded_pages, true) ?? [];
        $path = null;

        if ($extension === 'pdf' && !empty($excludedPages)) {
            try {
                $tempPath = $file->getRealPath();
                $pdfData = $this->convertPdfToVersion14($tempPath);
                $targetPdf = $pdfData['path'];

                $pdf = new Fpdi();
                $pageCount = $pdf->setSourceFile($targetPdf);

                $pagesProcessed = 0;
                for ($i = 1; $i <= $pageCount; $i++) {
                    if (!in_array($i, $excludedPages)) {
                        $templateId = $pdf->importPage($i);
                        $size = $pdf->getTemplateSize($templateId);
                        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                        $pdf->useTemplate($templateId);
                        $pagesProcessed++;
                    }
                }

                if ($pdfData['is_temp'] && file_exists($targetPdf)) {
                    @unlink($targetPdf);
                }

                if ($pagesProcessed === 0) {
                    return redirect()->back()->with('error', 'Anda tidak boleh menghapus semua halaman PDF.');
                }

                $fileName = 'receipts/processed_' . time() . '_' . uniqid() . '.pdf';
                Storage::disk('public')->put($fileName, $pdf->Output('S'));
                $path = $fileName;
            } catch (\Exception $e) {
                Log::error("Gagal memproses PDF Slicing: " . $e->getMessage());
                return redirect()->back()->with('error', 'Gagal memproses lampiran PDF.');
            }
        } else {
            $path = $file->store('receipts', 'public');
        }

        $userRole = strtolower(auth()->user()->role ?? 'employee_role');
        $initialStatus = ($userRole === 'team_leader') ? 'pending_leader' : 'pending';

        Reimbursement::create([
            'user_id' => auth()->id(),
            'person_name' => $request->person_name,
            'date' => $request->date,
            'category' => $request->category,
            'from_location' => $request->from_location,
            'to_location' => $request->to_location,
            'amount' => $request->amount,
            'comment' => $request->comment,
            'receipt_attachment' => $path,
            'status' => $initialStatus
        ]);

        return redirect()->route('reimbursements.index')->with('success', 'Claim filed successfully.');
    }

    public function approval($id)
    {
        $reimbursement = Reimbursement::findOrFail($id);
        $currentRole = strtolower(auth()->user()->role ?? 'employee_role');
        $myId = auth()->id();

        if ($currentRole === 'employee_role' && $reimbursement->user_id !== $myId) {
            abort(403, 'Unauthorized action.');
        }

        $pdfBase64 = null;
        if ($reimbursement->receipt_attachment && pathinfo($reimbursement->receipt_attachment, PATHINFO_EXTENSION) === 'pdf') {
            $cleanPath = str_replace('storage/', '', $reimbursement->receipt_attachment);

            $path1 = storage_path('app/public/' . $cleanPath);
            $path2 = storage_path('app/' . $cleanPath);
            $path3 = public_path('storage/' . $cleanPath);

            $finalPath = null;
            if (file_exists($path1)) {
                $finalPath = $path1;
            } elseif (file_exists($path2)) {
                $finalPath = $path2;
            } elseif (file_exists($path3)) {
                $finalPath = $path3;
            }

            if ($finalPath && is_readable($finalPath)) {
                $fileData = file_get_contents($finalPath);
                $pdfBase64 = base64_encode($fileData);
            } else {
                Log::error("PDF Gagal dibaca. Path cek: 1=$path1 | 2=$path2 | 3=$path3");
            }
        }

        return view('reimbursements.approval', compact('reimbursement', 'pdfBase64'));
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
            'page'            => 'nullable|integer',
        ]);

        $reimbursement = Reimbursement::findOrFail($id);
        $user          = auth()->user();
        $invoicePath   = storage_path('app/public/' . str_replace(['storage/', 'public/'], '', $reimbursement->receipt_attachment));
        $extension     = strtolower(pathinfo($invoicePath, PATHINFO_EXTENSION));

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
                'page'        => (int) $request->input('page', 1),
            ]];
        }

        $existingSignatures = json_decode($reimbursement->signatures_json, true) ?? [];
        $combinedSignatures = array_merge($existingSignatures, $newSignatures);
        $reimbursement->signatures_json = json_encode($combinedSignatures);

        $sigPaths = [];
        foreach ($newSignatures as $idx => $sig) {
            $sigData = $sig['image'];

            if (preg_match('/^data:image\/(\w+);base64,/', $sigData, $m)) {
                $sigData = substr($sigData, strpos($sigData, ',') + 1);
            }
            $sigBytes = base64_decode($sigData);

            $sigFileName = 'signatures/sig_' . $id . '_' . $user->id . '_' . time() . '_' . $idx . '.png';

            $manager = new ImageManager(new Driver());
            $signatureImg = $manager->read($sigBytes);

            $pngData = $signatureImg->toPng()->toString();
            Storage::disk('public')->put($sigFileName, $pngData);

            $absolutePath = storage_path('app/public/' . $sigFileName);

            $sigPaths[] = [
                'path'        => $absolutePath,
                'pos_x'       => (float) $sig['pos_x'],
                'pos_y'       => (float) $sig['pos_y'],
                'scale_w'     => (float) $sig['scale_w'],
                'scale_h'     => (float) $sig['scale_h'],
                'signer_name' => $sig['signer_name'] ?? $user->name,
                'signer_date' => $sig['signer_date'] ?? now()->format('Y-m-d'),
                'page'        => isset($sig['page']) ? (int) $sig['page'] : 1,
            ];
        }

        if ($reimbursement->receipt_attachment && file_exists($invoicePath)) {

            if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                $manager = new ImageManager(new Driver());
                $imageContent = file_get_contents($invoicePath);
                $image = $manager->read($imageContent);

                foreach ($sigPaths as $s) {
                    $pixelX = (int) round(($s['pos_x'] / 100) * $image->width());
                    $pixelY = (int) round(($s['pos_y'] / 100) * $image->height());
                    $pixelW = (int) round(($s['scale_w'] / 100) * $image->width());
                    $pixelH = (int) round(($s['scale_h'] / 100) * $image->height());
                    if ($pixelW < 20) $pixelW = 100;
                    if ($pixelH < 10) $pixelH = 50;

                    $sigContent = file_get_contents($s['path']);
                    $sigImg = $manager->read($sigContent)->resize($pixelW, $pixelH);

                    $image->place($sigImg, 'top-left', $pixelX, $pixelY);
                }
                $image->save($invoicePath);
            } elseif ($extension === 'pdf') {
                try {
                    // Konversi dulu ke v1.4 sebelum membubuhkan TTD
                    $pdfData = $this->convertPdfToVersion14($invoicePath);
                    $targetPdf = $pdfData['path'];

                    $pdf = new Fpdi();
                    $pdf->SetAutoPageBreak(false);

                    $pageCount = $pdf->setSourceFile($targetPdf);

                    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                        $templateId = $pdf->importPage($pageNo);
                        $size       = $pdf->getTemplateSize($templateId);
                        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                        $pdf->useTemplate($templateId);

                        $pageHeight = $size['height'];

                        foreach ($sigPaths as $s) {
                            $targetPage = (int) ($s['page'] ?? 1);
                            if ($targetPage !== $pageNo) continue;

                            $mmX = ($s['pos_x']   / 100) * $size['width'];
                            $mmY = ($s['pos_y']   / 100) * $pageHeight;
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

                    if ($pdfData['is_temp'] && file_exists($targetPdf)) {
                        @unlink($targetPdf);
                    }
                } catch (\Throwable $e) {
                    Log::error('=== PDF SIGN ERROR ===', [
                        'message' => $e->getMessage(),
                        'file'    => $e->getFile(),
                        'line'    => $e->getLine(),
                    ]);
                    throw $e;
                }
            }
        }

        $currentRole = strtolower($user->role ?? 'employee_role');
        $nextStatus  = 'pending';

        switch ($currentRole) {
            case 'employee_role':
                $nextStatus = 'pending_leader';
                break;
            case 'team_leader':
                $nextStatus = 'pending_station';
                break;
            case 'station_master':
                $nextStatus = 'pending_manager';
                break;
            case 'manager':
            case 'superadmin':
                $nextStatus = 'approved';
                break;
        }

        $reimbursement->status = (string) trim($nextStatus);
        if ($nextStatus === 'approved') {
            $reimbursement->approved_by = $user->id;
            $reimbursement->digital_signature = $sigPaths[0]['path'] ?? null;
        }
        $reimbursement->save();

        return redirect()->route('reimbursements.index')->with('success', 'The document has been successfully signed. Current status: ' . strtoupper($nextStatus));
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
        $reimbursement = Reimbursement::findOrFail($id);
        if ($reimbursement->receipt_attachment) {
            Storage::disk('public')->delete($reimbursement->receipt_attachment);
        }
        $reimbursement->delete();

        return redirect()->route('reimbursements.index')->with('success', 'Claim canceled successfully.');
    }

    public function show($id)
    {
        $reimbursement = Reimbursement::findOrFail($id);
        return view('reimbursements.index', compact('reimbursement'));
    }

    /**
     * EXPORT SUMMARY PDF (DENGAN GHOSTSCRIPT CONVERSION)
     */
    public function exportApprovedPdf(Request $request)
    {
        $query = Reimbursement::where('user_id', auth()->id())->with('user');

        if ($request->filled('month')) {
            $query->whereMonth('date', $request->month);
        }

        $reimbursements = $query
            ->orderByRaw("FIELD(category, 'transportation', 'delivery', 'office')")
            ->orderBy('person_name', 'asc')
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($reimbursements->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada data reimbursement APPROVED untuk bulan yang dipilih.');
        }

        $pdf = new Fpdi('L', 'mm', 'A4');
        $pdf->SetAutoPageBreak(false);

        $canvasWidth  = 297;
        $canvasHeight = 210;
        $marginOuter  = 10;
        $gapCenter    = 6;
        $maxPageWidth = ($canvasWidth - ($marginOuter * 2) - $gapCenter) / 2;
        $maxPageHeight = $canvasHeight - 20;

        $documentQueue = [];
        $claimCounter = 1;

        foreach ($reimbursements as $reimbursement) {
            if (!$reimbursement->receipt_attachment) continue;

            $cleanPath = str_replace(['storage/', 'public/'], '', $reimbursement->receipt_attachment);
            $invoicePath = storage_path('app/public/' . $cleanPath);

            if (!file_exists($invoicePath)) {
                $invoicePath = public_path('storage/' . $cleanPath);
                if (!file_exists($invoicePath)) continue;
            }

            $extension = strtolower(pathinfo($invoicePath, PATHINFO_EXTENSION));

            if ($extension === 'pdf') {
                // 🟢 Konversi PDF via Ghostscript
                $pdfData = $this->convertPdfToVersion14($invoicePath);
                $targetPdf = $pdfData['path'];

                try {
                    $subPdf = new Fpdi();
                    $pageCount = $subPdf->setSourceFile($targetPdf);

                    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                        $documentQueue[] = [
                            'type' => 'pdf',
                            'file' => $targetPdf,
                            'page_no' => $pageNo,
                            'claim_no' => $claimCounter,
                            'is_temp' => $pdfData['is_temp']
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error("FPDI Error pada Summary PDF ID {$reimbursement->id}: " . $e->getMessage());
                    if ($pdfData['is_temp'] && file_exists($targetPdf)) @unlink($targetPdf);
                    continue;
                }
            } elseif (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                $documentQueue[] = [
                    'type' => 'image',
                    'file' => $invoicePath,
                    'claim_no' => $claimCounter,
                    'is_temp' => false
                ];
            }

            if (count($documentQueue) % 2 !== 0) {
                $documentQueue[] = ['type' => 'blank', 'is_temp' => false];
            }

            $claimCounter++;
        }

        if (empty($documentQueue)) {
            return redirect()->back()->with('error', 'Tidak ada berkas nota yang dapat diproses ke PDF Summary.');
        }

        $totalItems = count($documentQueue);

        for ($i = 0; $i < $totalItems; $i += 2) {
            $pdf->AddPage('L', [$canvasWidth, $canvasHeight]);

            $leftItem = $documentQueue[$i];

            if ($leftItem['type'] !== 'blank') {
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

                $pdf->SetDrawColor(40, 40, 40);
                $pdf->SetLineWidth(0.3);
                $pdf->Rect($x1, $y1, $maxPageWidth, $maxPageHeight);

                $pdf->SetFont('Helvetica', 'B', 10);
                $pdf->SetTextColor(40, 40, 40);
                $pdf->SetXY($x1 + $maxPageWidth - 30, $y1 - 6);
                $pdf->Cell(30, 5, 'SN. ' . $leftItem['claim_no'], 0, 0, 'R');
            }

            if (isset($documentQueue[$i + 1])) {
                $rightItem = $documentQueue[$i + 1];

                if ($rightItem['type'] !== 'blank') {
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

                    $pdf->Rect($x2, $y2, $maxPageWidth, $maxPageHeight);

                    $pdf->SetFont('Helvetica', 'B', 10);
                    $pdf->SetTextColor(40, 40, 40);
                    $pdf->SetXY($x2 + $maxPageWidth - 30, $y2 - 6);
                    $pdf->Cell(30, 5, 'SN. ' . $rightItem['claim_no'], 0, 0, 'R');
                }
            }
        }

        // Output PDF Stream
        $pdfContent = $pdf->Output('S');

        // 🟢 Hapus file temporary Ghostscript
        foreach ($documentQueue as $item) {
            if (!empty($item['is_temp']) && file_exists($item['file'])) {
                @unlink($item['file']);
            }
        }

        $monthName = $request->filled('month') ? date('F', mktime(0, 0, 0, $request->month, 10)) : 'All_Months';
        $fileName = "reimbursements_approved_{$monthName}_" . now()->format('Y') . ".pdf";

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    /**
     * EXPORT SINGLE INVOICE PDF
     */
    public function exportSinglePdf($id)
    {
        $reimbursement = Reimbursement::findOrFail($id);
        $cleanPath = str_replace(['storage/', 'public/'], '', $reimbursement->receipt_attachment);
        $invoicePath = storage_path('app/public/' . $cleanPath);

        if (!$reimbursement->receipt_attachment || !file_exists($invoicePath)) {
            return redirect()->back()->with('error', 'Berkas nota bukti lampiran tidak ditemukan fisik datanya.');
        }

        $pdf = new Fpdi('L', 'mm', 'A4');
        $pdf->SetAutoPageBreak(false);

        $canvasWidth  = 297;
        $canvasHeight = 210;
        $marginOuter  = 10;
        $gapCenter    = 6;
        $maxPageWidth = ($canvasWidth - ($marginOuter * 2) - $gapCenter) / 2;
        $maxPageHeight = $canvasHeight - 20;

        $extension = strtolower(pathinfo($invoicePath, PATHINFO_EXTENSION));
        $documentQueue = [];

        if ($extension === 'pdf') {
            $pdfData = $this->convertPdfToVersion14($invoicePath);
            $targetPdfPath = $pdfData['path'];

            try {
                $subPdf = new Fpdi();
                $pageCount = $subPdf->setSourceFile($targetPdfPath);

                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    $documentQueue[] = [
                        'type' => 'pdf',
                        'file' => $targetPdfPath,
                        'page_no' => $pageNo,
                        'is_temp' => $pdfData['is_temp']
                    ];
                }
            } catch (\Exception $e) {
                Log::error("FPDI Parsing Failed: " . $e->getMessage());
                if ($pdfData['is_temp'] && file_exists($targetPdfPath)) @unlink($targetPdfPath);
                return redirect()->back()->with('error', 'Gagal membaca berkas PDF lampiran.');
            }
        } elseif (in_array($extension, ['jpg', 'jpeg', 'png'])) {
            $documentQueue[] = ['type' => 'image', 'file' => $invoicePath, 'is_temp' => false];
        }

        $totalItems = count($documentQueue);
        for ($i = 0; $i < $totalItems; $i += 2) {
            $pdf->AddPage('L', [$canvasWidth, $canvasHeight]);

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

            $pdf->SetDrawColor(40, 40, 40);
            $pdf->SetLineWidth(0.3);
            $pdf->Rect($x1, $y1, $maxPageWidth, $maxPageHeight);

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
                $pdf->Rect($x2, $y2, $maxPageWidth, $maxPageHeight);
            }
        }

        $pdfContent = $pdf->Output('S');

        // 🟢 Hapus file temp Ghostscript
        foreach ($documentQueue as $item) {
            if (!empty($item['is_temp']) && file_exists($item['file'])) {
                @unlink($item['file']);
            }
        }

        $filename = 'invoice_' . strtolower(str_replace(' ', '_', $reimbursement->person_name)) . '_' . $reimbursement->id . '.pdf';

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function exportExcel(Request $request)
    {
        $user = auth()->user();
        $search = $request->get('search');
        $month = $request->get('month');

        if ($user->role === 'superadmin' || $request->boolean('all_site')) {
            $siteName = 'ALL_SITES';
        } else {
            $rawSiteName = $user->site->machine_name ?? 'SITE';
            $siteName = Str::slug($rawSiteName, '_');
        }

        $userName = Str::slug($user->name ?? 'User', '_');

        if ($month) {
            $monthName = \Carbon\Carbon::createFromFormat('m', $month)->format('F_Y');
        } else {
            $monthName = now()->format('F_Y');
        }

        $fileName = "Reimbursement_{$siteName}_{$userName}_{$monthName}.xlsx";

        return Excel::download(
            new ReimbursementExport($search, $month, $user->role === 'superadmin' || $request->boolean('all_site')),
            $fileName
        );
    }

    /**
     * Konversi PDF v1.5+ ke PDF v1.4 menggunakan Ghostscript
     */
    private function convertPdfToVersion14($inputPath)
    {
        $outputPath = storage_path('app/public/receipts/converted_' . uniqid() . '.pdf');

        // 🟢 Deteksi binary path Ghostscript (Aman untuk macOS Homebrew & Ubuntu)
        $gsBinary = 'gs';
        if (file_exists('/opt/homebrew/bin/gs')) {
            $gsBinary = '/opt/homebrew/bin/gs';
        } elseif (file_exists('/usr/local/bin/gs')) {
            $gsBinary = '/usr/local/bin/gs';
        } elseif (file_exists('/usr/bin/gs')) {
            $gsBinary = '/usr/bin/gs';
        }

        $command = "{$gsBinary} -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dNOPAUSE -dQUIET -dBATCH -sOutputFile=" . escapeshellarg($outputPath) . " " . escapeshellarg($inputPath);

        exec($command, $output, $returnVar);

        if ($returnVar === 0 && file_exists($outputPath) && filesize($outputPath) > 0) {
            return [
                'path' => $outputPath,
                'is_temp' => true
            ];
        }

        Log::warning("Ghostscript conversion bypassed or failed. Exec Return: {$returnVar}");

        return [
            'path' => $inputPath,
            'is_temp' => false
        ];
    }

    /**
     * TAMPILKAN FORM EDIT REIMBURSEMENT
     */
    public function edit($id)
    {
        $reimbursement = Reimbursement::findOrFail($id);
        $user = auth()->user();

        if ($user->role !== 'superadmin' && $reimbursement->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        $employeesQuery = Employee::query();
        if ($user && $user->role === 'employee_role') {
            $employeesQuery->where('site_id', $user->site_id);
        }
        $employees = $employeesQuery->orderBy('name', 'asc')->get();

        return view('reimbursements.edit', compact('reimbursement', 'employees'));
    }

    /**
     * SIMPAN PERUBAHAN EDIT REIMBURSEMENT
     */
    public function update(Request $request, $id)
    {
        $reimbursement = Reimbursement::findOrFail($id);
        $user = auth()->user();

        if ($user->role !== 'superadmin' && $reimbursement->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        if ($request->has('amount')) {
            $cleanedAmount = str_replace('.', '', $request->amount);
            $request->merge(['amount' => $cleanedAmount]);
        }

        $request->validate([
            'person_name'        => 'required|string|max:255',
            'date'               => 'required|date',
            'category'           => 'required|in:transportation,delivery,office',
            'amount'             => 'required|numeric|min:0',
            'from_location'      => 'nullable|required_if:category,transportation,delivery|string|max:255',
            'to_location'        => 'nullable|required_if:category,transportation,delivery|string|max:255',
            'comment'            => 'nullable|string',
            'receipt_attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:4096',
        ]);

        $dataToUpdate = [
            'person_name'   => $request->person_name,
            'date'          => $request->date,
            'category'      => $request->category,
            'from_location' => in_array($request->category, ['transportation', 'delivery']) ? $request->from_location : null,
            'to_location'   => in_array($request->category, ['transportation', 'delivery']) ? $request->to_location : null,
            'amount'        => $request->amount,
            'comment'       => $request->comment,
        ];

        if ($request->hasFile('receipt_attachment')) {
            if ($reimbursement->receipt_attachment && Storage::disk('public')->exists($reimbursement->receipt_attachment)) {
                Storage::disk('public')->delete($reimbursement->receipt_attachment);
            }

            $file = $request->file('receipt_attachment');
            $extension = strtolower($file->getClientOriginalExtension());
            $excludedPages = json_decode($request->excluded_pages, true) ?? [];

            if ($extension === 'pdf' && !empty($excludedPages)) {
                try {
                    $tempPath = $file->getRealPath();
                    $pdfData = $this->convertPdfToVersion14($tempPath);
                    $targetPdf = $pdfData['path'];

                    $pdf = new Fpdi();
                    $pageCount = $pdf->setSourceFile($targetPdf);

                    $pagesProcessed = 0;
                    for ($i = 1; $i <= $pageCount; $i++) {
                        if (!in_array($i, $excludedPages)) {
                            $templateId = $pdf->importPage($i);
                            $size = $pdf->getTemplateSize($templateId);
                            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                            $pdf->useTemplate($templateId);
                            $pagesProcessed++;
                        }
                    }

                    if ($pdfData['is_temp'] && file_exists($targetPdf)) {
                        @unlink($targetPdf);
                    }

                    if ($pagesProcessed === 0) {
                        return redirect()->back()->with('error', 'Anda tidak boleh menghapus semua halaman PDF.');
                    }

                    $fileName = 'receipts/processed_' . time() . '_' . uniqid() . '.pdf';
                    Storage::disk('public')->put($fileName, $pdf->Output('S'));
                    $dataToUpdate['receipt_attachment'] = $fileName;
                } catch (\Exception $e) {
                    Log::error("Gagal memproses PDF Slicing saat edit: " . $e->getMessage());
                    return redirect()->back()->with('error', 'Gagal memproses lampiran PDF.');
                }
            } else {
                $dataToUpdate['receipt_attachment'] = $file->store('receipts', 'public');
            }
        }

        $reimbursement->update($dataToUpdate);

        return redirect()->route('reimbursements.index')->with('success', 'Reimbursement claim updated successfully.');
    }
}
