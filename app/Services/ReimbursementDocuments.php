<?php

namespace App\Services;

use App\Models\Reimbursement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Fpdi;

class ReimbursementDocuments
{
    public function exportApprovedPdf(Request $request)
    {
        $user = auth()->user();

        if (in_array($user->role, ['superadmin', 'administration'])) {
            $query = Reimbursement::query();
        } else {
            $query = Reimbursement::where('user_id', $user->id);
        }

        $query->with('user');

        if ($request->filled('month')) {
            $query->whereMonth('date', $request->month);
        }

        $reimbursements = $query
            ->orderByRaw("CASE category WHEN 'transportation' THEN 1 WHEN 'delivery' THEN 2 WHEN 'office' THEN 3 ELSE 4 END")
            ->orderBy('person_name', 'asc')
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($reimbursements->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada klaim yang disetujui pada bulan terpilih.');
        }

        $pdf = new Fpdi('L', 'mm', 'A4');
        $pdf->SetAutoPageBreak(false);

        $canvasWidth    = 297;
        $canvasHeight   = 210;
        $marginOuter    = 10;
        $gapCenter      = 6;
        $maxPageWidth   = ($canvasWidth - ($marginOuter * 2) - $gapCenter) / 2;
        $maxPageHeight  = $canvasHeight - 20;

        $documentQueue = [];
        $claimCounter = 1;

        try {
            foreach ($reimbursements as $reimbursement) {
                if (!$reimbursement->receipt_attachment) {
                    continue;
                }

                $invoicePath = $this->resolveReceiptPath($reimbursement->receipt_attachment);

                if (!$invoicePath || !file_exists($invoicePath)) {
                    Log::warning("Receipt tidak ditemukan untuk reimbursement ID {$reimbursement->id}");
                    continue;
                }

                $extension = strtolower(pathinfo($invoicePath, PATHINFO_EXTENSION));

                if ($extension === 'pdf') {
                    $pdfData = $this->convertPdfToVersion14($invoicePath);
                    $targetPdf = $pdfData['path'];
                    $claimQueueStart = count($documentQueue);
                    $sourcePdfStillNeeded = false;

                    try {
                        $reader = new Fpdi();
                        $pageCount = $reader->setSourceFile($targetPdf);

                        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                            $tplId = $reader->importPage($pageNo);
                            $size = $reader->getTemplateSize($tplId);

                            if ($size['width'] > $size['height']) {
                                // LANDSCAPE: benar-benar buat 2 file setengah halaman.
                                $halves = $this->createLandscapeHalfPages($targetPdf, $pageNo);

                                foreach ($halves as $half) {
                                    $documentQueue[] = [
                                        'type' => 'pdf',
                                        'file' => $half['file'],
                                        'page_no' => 1,
                                        'claim_no' => $claimCounter,
                                        'is_temp' => true,
                                    ];
                                }
                            } else {
                                // PORTRAIT: tetap sebagai satu dokumen.
                                $sourcePdfStillNeeded = true;
                                $documentQueue[] = [
                                    'type' => 'pdf',
                                    'file' => $targetPdf,
                                    'page_no' => $pageNo,
                                    'claim_no' => $claimCounter,
                                    'is_temp' => $pdfData['is_temp'],
                                ];
                            }
                        }

                        // Hanya hapus hasil Ghostscript jika TIDAK ADA halaman
                        // portrait yang masih menggunakannya.
                        if ($pdfData['is_temp'] && !$sourcePdfStillNeeded && file_exists($targetPdf)) {
                            @unlink($targetPdf);
                        }
                    } catch (\Throwable $e) {
                        Log::error("FPDI Error pada Summary PDF ID {$reimbursement->id}: {$e->getMessage()}");

                        if ($pdfData['is_temp'] && file_exists($targetPdf)) {
                            @unlink($targetPdf);
                        }

                        // Buang hanya item temporary yang dibuat untuk klaim ini.
                        $failedItems = array_splice($documentQueue, $claimQueueStart);
                        $this->cleanupTemporaryQueueFiles($failedItems);

                        continue;
                    }
                } elseif (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                    $documentQueue[] = [
                        'type' => 'image',
                        'file' => $invoicePath,
                        'claim_no' => $claimCounter,
                        'is_temp' => false,
                    ];
                }

                // Setiap klaim harus mulai dari slot baru jika jumlah itemnya ganjil.
                if (count($documentQueue) % 2 !== 0) {
                    $documentQueue[] = [
                        'type' => 'blank',
                        'is_temp' => false,
                    ];
                }

                $claimCounter++;
            }

            if (empty($documentQueue)) {
                return redirect()->back()->with('error', 'Tidak ada bukti nota yang dapat dibuat menjadi ringkasan PDF.');
            }

            $this->renderDocumentQueueToA4(
                $pdf,
                $documentQueue,
                $canvasWidth,
                $canvasHeight,
                $marginOuter,
                $gapCenter,
                $maxPageWidth,
                $maxPageHeight,
                true
            );

            $pdfContent = $pdf->Output('S');
        } finally {
            $this->cleanupTemporaryQueueFiles($documentQueue);
        }

        $monthName = $request->filled('month')
            ? date('F', mktime(0, 0, 0, $request->month, 10))
            : 'All_Months';

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
        \App\Services\ReimbursementAccess::view($reimbursement);

        if (!$reimbursement->receipt_attachment) {
            return redirect()->back()->with('error', 'Berkas nota bukti lampiran tidak ditemukan.');
        }

        $invoicePath = $this->resolveReceiptPath($reimbursement->receipt_attachment);

        if (!$invoicePath || !file_exists($invoicePath)) {
            return redirect()->back()->with('error', 'Berkas nota bukti lampiran tidak ditemukan fisik datanya.');
        }

        $pdf = new Fpdi('L', 'mm', 'A4');
        $pdf->SetAutoPageBreak(false);

        $canvasWidth    = 297;
        $canvasHeight   = 210;
        $marginOuter    = 10;
        $gapCenter      = 6;
        $maxPageWidth   = ($canvasWidth - ($marginOuter * 2) - $gapCenter) / 2;
        $maxPageHeight  = $canvasHeight - 20;

        $extension = strtolower(pathinfo($invoicePath, PATHINFO_EXTENSION));
        $documentQueue = [];

        try {
            if ($extension === 'pdf') {
                $pdfData = $this->convertPdfToVersion14($invoicePath);
                $targetPdfPath = $pdfData['path'];

                try {
                    $reader = new Fpdi();
                    $pageCount = $reader->setSourceFile($targetPdfPath);
                    $sourcePdfStillNeeded = false;

                    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                        $tplId = $reader->importPage($pageNo);
                        $size = $reader->getTemplateSize($tplId);

                        if ($size['width'] > $size['height']) {
                            // LANDSCAPE: satu halaman menjadi dua slot.
                            $halves = $this->createLandscapeHalfPages($targetPdfPath, $pageNo);

                            foreach ($halves as $half) {
                                $documentQueue[] = [
                                    'type' => 'pdf',
                                    'file' => $half['file'],
                                    'page_no' => 1,
                                    'claim_no' => null,
                                    'is_temp' => true,
                                ];
                            }
                        } else {
                            $sourcePdfStillNeeded = true;
                            $documentQueue[] = [
                                'type' => 'pdf',
                                'file' => $targetPdfPath,
                                'page_no' => $pageNo,
                                'claim_no' => null,
                                'is_temp' => $pdfData['is_temp'],
                            ];
                        }
                    }

                    // Hapus hasil Ghostscript hanya jika semua halaman sudah
                    // berubah menjadi half-PDF dan tidak ada queue item yang
                    // masih membutuhkan targetPdfPath.
                    if ($pdfData['is_temp'] && !$sourcePdfStillNeeded && file_exists($targetPdfPath)) {
                        @unlink($targetPdfPath);
                    }
                } catch (\Throwable $e) {
                    Log::error("FPDI Parsing Failed: " . $e->getMessage());

                    if ($pdfData['is_temp'] && file_exists($targetPdfPath)) {
                        @unlink($targetPdfPath);
                    }

                    $this->cleanupTemporaryQueueFiles($documentQueue);

                    return redirect()->back()->with('error', 'Gagal membaca berkas PDF lampiran.');
                }
            } elseif (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                $documentQueue[] = [
                    'type' => 'image',
                    'file' => $invoicePath,
                    'claim_no' => null,
                    'is_temp' => false,
                ];
            } else {
                return redirect()->back()->with('error', 'Format berkas lampiran tidak didukung.');
            }

            if (empty($documentQueue)) {
                return redirect()->back()->with('error', 'Tidak ada halaman yang dapat diekspor.');
            }

            // Single invoice tetap memakai layout dua slot per halaman A4.
            if (count($documentQueue) % 2 !== 0) {
                $documentQueue[] = [
                    'type' => 'blank',
                    'is_temp' => false,
                ];
            }

            $this->renderDocumentQueueToA4(
                $pdf,
                $documentQueue,
                $canvasWidth,
                $canvasHeight,
                $marginOuter,
                $gapCenter,
                $maxPageWidth,
                $maxPageHeight,
                false
            );

            $pdfContent = $pdf->Output('S');
        } finally {
            $this->cleanupTemporaryQueueFiles($documentQueue);
        }

        $filename = 'invoice_' . strtolower(str_replace(' ', '_', $reimbursement->person_name)) . '_' . $reimbursement->id . '.pdf';

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Resolve path receipt yang tersimpan di database.
     */
    private function resolveReceiptPath($receiptAttachment)
    {
        $path = SensitiveFiles::path($receiptAttachment);

        return is_file($path) ? $path : null;
    }

    /**
     * Membuat dua PDF temporary dari satu halaman PDF LANDSCAPE.
     *
     * Hasil:
     *   - left  = setengah kiri halaman asli
     *   - right = setengah kanan halaman asli
     *
     * Tidak menggunakan ClippingRect(). Batas page PDF temporary
     * sendiri menjadi clipping boundary, sehingga tidak ada overlap.
     */
    private function createLandscapeHalfPages($sourcePdf, $pageNo)
    {
        $reader = new Fpdi();
        $pageCount = $reader->setSourceFile($sourcePdf);

        if ($pageNo < 1 || $pageNo > $pageCount) {
            throw new \RuntimeException("Halaman PDF tidak valid: {$pageNo}");
        }

        $sourceTemplate = $reader->importPage($pageNo);
        $size = $reader->getTemplateSize($sourceTemplate);

        $pageWidth = (float) $size['width'];
        $pageHeight = (float) $size['height'];

        if ($pageWidth <= $pageHeight) {
            throw new \RuntimeException('createLandscapeHalfPages hanya menerima halaman landscape.');
        }

        $halfWidth = $pageWidth / 2;
        $result = [];
        $createdFiles = [];

        try {
            foreach (['left', 'right'] as $side) {
                $out = new Fpdi();
                $out->SetAutoPageBreak(false);
                $out->setSourceFile($sourcePdf);
                $tplId = $out->importPage($pageNo);

                // Halaman output hanya selebar setengah halaman sumber.
                $out->AddPage('P', [$halfWidth, $pageHeight]);

                if ($side === 'left') {
                    // Bagian kiri halaman asli berada di dalam page output.
                    $x = 0;
                } else {
                    // Geser halaman asli ke kiri agar bagian kanan berada
                    // tepat di dalam page output.
                    $x = -$halfWidth;
                }

                $out->useTemplate(
                    $tplId,
                    $x,
                    0,
                    $pageWidth,
                    $pageHeight
                );

                $tempPath = SensitiveFiles::temporaryPath('half_'.$side);

                $out->Output($tempPath, 'F');

                if (!file_exists($tempPath) || filesize($tempPath) <= 0) {
                    throw new \RuntimeException("Gagal membuat PDF {$side} dari halaman landscape.");
                }

                $createdFiles[] = $tempPath;
                $result[] = [
                    'side' => $side,
                    'file' => $tempPath,
                ];
            }
        } catch (\Throwable $e) {
            foreach ($createdFiles as $file) {
                if (file_exists($file)) {
                    @unlink($file);
                }
            }

            throw $e;
        }

        return $result;
    }

    /**
     * Render queue menjadi halaman A4 landscape, dua slot per halaman.
     */
    private function renderDocumentQueueToA4(
        Fpdi $pdf,
        array $documentQueue,
        $canvasWidth,
        $canvasHeight,
        $marginOuter,
        $gapCenter,
        $maxPageWidth,
        $maxPageHeight,
        $showClaimNumber = false
    ) {
        $totalItems = count($documentQueue);

        for ($i = 0; $i < $totalItems; $i += 2) {
            $pdf->AddPage('L', [$canvasWidth, $canvasHeight]);

            $leftItem = $documentQueue[$i] ?? ['type' => 'blank'];
            $rightItem = $documentQueue[$i + 1] ?? ['type' => 'blank'];

            $this->renderDocumentSlot(
                $pdf,
                $leftItem,
                $marginOuter,
                ($canvasHeight - $maxPageHeight) / 2,
                $maxPageWidth,
                $maxPageHeight,
                $showClaimNumber
            );

            $this->renderDocumentSlot(
                $pdf,
                $rightItem,
                $marginOuter + $maxPageWidth + $gapCenter,
                ($canvasHeight - $maxPageHeight) / 2,
                $maxPageWidth,
                $maxPageHeight,
                $showClaimNumber
            );
        }
    }

    /**
     * Render satu item ke satu slot.
     */
    private function renderDocumentSlot(
        Fpdi $pdf,
        array $item,
        $slotX,
        $slotY,
        $slotW,
        $slotH,
        $showClaimNumber = false
    ) {
        if (($item['type'] ?? 'blank') === 'blank') {
            return;
        }

        $type = $item['type'];
        $file = $item['file'] ?? null;

        if (!$file || !file_exists($file)) {
            return;
        }

        if ($type === 'pdf') {
            $pdf->setSourceFile($file);
            $tplId = $pdf->importPage($item['page_no'] ?? 1);
            $size = $pdf->getTemplateSize($tplId);

            $ratio = $size['width'] / max($size['height'], 0.001);
            $w = $slotW;
            $h = $w / $ratio;

            if ($h > $slotH) {
                $h = $slotH;
                $w = $h * $ratio;
            }

            $x = $slotX + (($slotW - $w) / 2);
            $y = $slotY + (($slotH - $h) / 2);

            $pdf->useTemplate($tplId, $x, $y, $w, $h);
        } elseif ($type === 'image') {
            $imageSize = @getimagesize($file);

            if (!$imageSize || empty($imageSize[0]) || empty($imageSize[1])) {
                return;
            }

            $ratio = $imageSize[0] / max($imageSize[1], 1);
            $w = $slotW;
            $h = $w / $ratio;

            if ($h > $slotH) {
                $h = $slotH;
                $w = $h * $ratio;
            }

            $x = $slotX + (($slotW - $w) / 2);
            $y = $slotY + (($slotH - $h) / 2);

            $pdf->Image($file, $x, $y, $w, $h);
        }

        // Border slot.
        $pdf->SetDrawColor(40, 40, 40);
        $pdf->SetLineWidth(0.3);
        $pdf->Rect($slotX, $slotY, $slotW, $slotH);

        if ($showClaimNumber && !empty($item['claim_no'])) {
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->SetTextColor(40, 40, 40);
            $pdf->SetXY($slotX + $slotW - 30, $slotY - 6);
            $pdf->Cell(30, 5, 'SN. ' . $item['claim_no'], 0, 0, 'R');
        }
    }

    /**
     * Hapus seluruh file temporary yang dibuat selama export.
     */
    private function cleanupTemporaryQueueFiles(array $documentQueue)
    {
        $deleted = [];

        foreach ($documentQueue as $item) {
            if (empty($item['is_temp']) || empty($item['file'])) {
                continue;
            }

            $file = $item['file'];

            if (isset($deleted[$file])) {
                continue;
            }

            if (file_exists($file)) {
                @unlink($file);
            }

            $deleted[$file] = true;
        }
    }

    public function convertPdfToVersion14($inputPath)
    {
        $outputPath = SensitiveFiles::temporaryPath('converted');

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
}
