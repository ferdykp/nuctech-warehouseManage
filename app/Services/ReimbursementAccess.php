<?php

namespace App\Services;

use App\Models\Reimbursement;
use Illuminate\Database\Eloquent\Builder;

class ReimbursementAccess
{
    public static function query(): Builder
    {
        $user = auth()->user();
        $query = Reimbursement::query();

        // 🟢 UBAH DI SINI:
        // Jika Superadmin ATAU Administration, langsung kembalikan query (Tampilkan SEMUA data tanpa filter)
        if ($user->isSuperAdmin() || $user->role === 'administration') {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id);
            $status = self::approvalStatus($user->role);
            if (in_array($user->role, ['team_leader', 'station_master', 'manager'])) {
                $q->orWhere(function ($review) use ($user, $status) {
                    $review->where('status', $status);
                    if ($user->role !== 'manager') {
                        $review->whereHas('user', fn($owner) => $owner->where('site_id', $user->site_id ?? 0));
                    }
                });
            }
        });
    }

    public static function view(Reimbursement $claim): void
    {
        abort_unless(self::query()->whereKey($claim->id)->exists(), 403);
    }

    public static function manage(Reimbursement $claim): void
    {
        // 🟢 UBAH DI SINI JUGA:
        // Supaya role administration juga bisa edit/delete data klaim lain jika diperlukan
        abort_unless(
            auth()->user()->isSuperAdmin() ||
                auth()->user()->role === 'administration' ||
                (int) $claim->user_id === (int) auth()->id(),
            403
        );
    }

    public static function approve(Reimbursement $claim): void
    {
        self::view($claim);
        $role = auth()->user()->role;
        abort_if(in_array($claim->status, ['approved', 'rejected']), 409, 'Klaim sudah selesai diproses.');

        // 🟢 UBAH DI SINI:
        // Menggunakan !in_array agar superadmin & administration bebas melakukan aksi
        if (!in_array($role, ['superadmin', 'administration'])) {
            abort_unless($claim->status === self::approvalStatus($role), 403, 'Klaim belum berada pada tahap persetujuan Anda.');
        }
    }

    public static function approvalStatus(string $role): string
    {
        return match ($role) {
            'team_leader' => 'pending_leader',
            'station_master' => 'pending_station',
            'manager' => 'pending_manager',
            default => 'pending',
        };
    }
}
