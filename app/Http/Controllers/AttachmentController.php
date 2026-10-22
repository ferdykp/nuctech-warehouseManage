<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\Reimbursement;
use App\Services\ReimbursementAccess;
use App\Services\SensitiveFiles;
use App\Services\SiteAccess;

class AttachmentController extends Controller
{
    public function receipt(Reimbursement $reimbursement)
    {
        ReimbursementAccess::view($reimbursement);
        abort_unless($reimbursement->receipt_attachment, 404);

        return SensitiveFiles::response($reimbursement->receipt_attachment);
    }

    public function leave(LeaveRequest $leave)
    {
        SiteAccess::authorize($leave->employee->site_id);
        abort_unless($leave->attachment_file, 404);

        return SensitiveFiles::response($leave->attachment_file);
    }
}
