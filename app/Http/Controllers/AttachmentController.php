<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\Reimbursement;
use App\Services\ReimbursementAccess;
use App\Services\SensitiveFiles;
use App\Services\SiteAccess;

class AttachmentController extends Controller
{
    // public function receipt(Reimbursement $reimbursement)
    // {
    //     ReimbursementAccess::view($reimbursement);
    //     abort_unless($reimbursement->receipt_attachment, 404);

    //     return SensitiveFiles::response($reimbursement->receipt_attachment);
    // }
    public function receipt(\App\Models\Reimbursement $reimbursement)
    {
        \App\Services\ReimbursementAccess::view($reimbursement);

        $receipt = $reimbursement->receipt_attachment;

        abort_if(empty($receipt), 404, 'Lampiran nota tidak ditemukan.');

        foreach (['sensitive', 'public'] as $diskName) {
            $disk = \Illuminate\Support\Facades\Storage::disk($diskName);

            if (!$disk->exists($receipt)) {
                continue;
            }

            $path = $disk->path($receipt);

            if (!is_file($path) || !is_readable($path)) {
                continue;
            }

            $mime = mime_content_type($path) ?: 'application/octet-stream';

            abort_unless(
                in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true),
                415,
                'Format lampiran tidak didukung.'
            );

            return response()->file($path, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline',
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        \Illuminate\Support\Facades\Log::warning(
            'Reimbursement receipt not found',
            [
                'reimbursement_id' => $reimbursement->id,
                'receipt_attachment' => $receipt,
            ]
        );

        abort(404, 'File bukti nota tidak ditemukan di penyimpanan.');
    }

    public function leave(LeaveRequest $leave)
    {
        SiteAccess::authorize($leave->employee->site_id);
        abort_unless($leave->attachment_file, 404);

        return SensitiveFiles::response($leave->attachment_file);
    }
}
