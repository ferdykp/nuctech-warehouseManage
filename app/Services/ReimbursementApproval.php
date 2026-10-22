<?php

namespace App\Services;

use App\Models\Reimbursement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use setasign\Fpdi\Fpdi;

class ReimbursementApproval
{
    public function approve(Request $request, $id)
    {
        $request->validate([
            'signature'       => 'required|string|max:2800000',
            'pos_x'           => 'required|numeric',
            'pos_y'           => 'required|numeric',
            'scale_w'         => 'required|numeric',
            'scale_h'         => 'required|numeric',
            'signatures_json' => 'nullable|json|max:6000000',
            'page'            => 'nullable|integer',
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $id) {
            $reimbursement = Reimbursement::lockForUpdate()->findOrFail($id);
            \App\Services\ReimbursementAccess::approve($reimbursement);
            \App\Services\ReimbursementAccess::view($reimbursement);
            $user          = auth()->user();
            $invoicePath   = $reimbursement->receipt_attachment ? SensitiveFiles::path($reimbursement->receipt_attachment) : '';
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

            \Illuminate\Support\Facades\Validator::make(['signatures' => $newSignatures], [
                'signatures' => 'required|array|min:1|max:20',
                'signatures.*.image' => 'required|string|max:2800000',
                'signatures.*.pos_x' => 'required|numeric|between:0,100',
                'signatures.*.pos_y' => 'required|numeric|between:0,100',
                'signatures.*.scale_w' => 'required|numeric|gt:0|lte:100',
                'signatures.*.scale_h' => 'required|numeric|gt:0|lte:100',
                'signatures.*.page' => 'nullable|integer|min:1',
            ])->validate();
            foreach ($newSignatures as &$signature) {
                $signature['signer_name'] = $user->name;
                $signature['signer_date'] = now()->format('Y-m-d');
            }
            unset($signature);
            $existingSignatures = $reimbursement->signatures_json ?? [];
            if (is_string($existingSignatures)) $existingSignatures = json_decode($existingSignatures, true) ?? [];
            $combinedSignatures = array_merge($existingSignatures, $newSignatures);
            $reimbursement->signatures_json = $combinedSignatures;

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
                Storage::disk('sensitive')->put($sigFileName, $pngData);

                $absolutePath = SensitiveFiles::path($sigFileName);

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
                        $pdfData = app(ReimbursementDocuments::class)->convertPdfToVersion14($invoicePath);
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
                case 'administration':
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

            return redirect()->route('reimbursements.index')->with('success', 'Dokumen berhasil ditandatangani. Status: ' . \App\Support\UiText::label($nextStatus));
        });
    }

}
