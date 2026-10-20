<?php

namespace App\Services;

use App\Models\SparepartTransfer;

class DashboardTasks
{
    public function forCurrentUser(): array
    {
        $user = auth()->user();
        $tasks = [];
        $statuses = in_array($user->role, ['superadmin', 'administration'], true)
            ? ['pending', 'pending_leader', 'pending_station', 'pending_manager']
            : [ReimbursementAccess::approvalStatus($user->role)];
        $query = ReimbursementAccess::query()->whereIn('status', $statuses);
        if ($user->role === 'employee_role') $query->where('user_id', $user->id);
        $tasks[] = [
            'label' => $user->role === 'employee_role' ? 'Klaim menunggu tanda tangan Anda' : 'Klaim menunggu tindak lanjut',
            'count' => $query->count(),
            'url' => route('reimbursements.index', ['status' => count($statuses) === 1 ? $statuses[0] : 'waiting']),
            'help' => 'Periksa rincian dan tahap persetujuan sebelum memproses.',
        ];
        if (in_array($user->role, ['superadmin', 'team_leader'], true)) {
            $outgoing = SparepartTransfer::where('status', 'pending');
            $incoming = SparepartTransfer::where('status', 'approved');
            if (!$user->isSuperAdmin()) {
                $outgoing->where('from_site_id', $user->site_id ?? 0);
                $incoming->where('to_site_id', $user->site_id ?? 0);
            }
            $url = $user->site ? route('sparepart.index', $user->site->slug) : route('site.index');
            $tasks[] = ['label' => 'Permintaan mutasi belum disetujui', 'count' => $outgoing->count(), 'url' => $user->isSuperAdmin() ? route('site.index') : $url,
                'help' => 'Buka lokasi asal untuk memeriksa permintaan barang.'];
            $tasks[] = ['label' => 'Mutasi menunggu penerimaan', 'count' => $incoming->count(), 'url' => $user->isSuperAdmin() ? route('site.index') : $url,
                'help' => 'Konfirmasi setelah barang fisik diterima di lokasi tujuan.'];
        }
        if (in_array($user->role, ['superadmin', 'administration'], true)) {
            $tasks[] = ['label'=>'Kontrak berakhir dalam 60 hari',
                'count'=>\App\Models\EmployeeContract::needsRenewal()->whereBetween('end_date',[today()->toDateString(),today()->addDays(60)->toDateString()])->count(),
                'url'=>route('contracts.archive',['status'=>'due']), 'help'=>'Siapkan perpanjangan dan pemberitahuan sebelum kontrak berakhir.'];
            $tasks[] = ['label'=>'Kontrak berakhir tanpa perpanjangan',
                'count'=>\App\Models\EmployeeContract::needsRenewal()->whereDate('end_date','<',today())->count(),
                'url'=>route('contracts.archive',['status'=>'expired']), 'help'=>'Tinjau kontrak berakhir yang belum memiliki penerus disetujui.'];
            $tasks[] = ['label'=>'Kontrak lama perlu verifikasi', 'count'=>\App\Models\EmployeeContract::where('status','needs_verification')->count(),
                'url'=>route('contracts.archive',['status'=>'needs_verification']), 'help'=>'Lengkapi periode dari dokumen asli.'];
        }
        return $tasks;
    }
}
