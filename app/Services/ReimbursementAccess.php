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

        // 1. Superadmin bisa melihat semua data dari seluruh cabang
        if ($user->isSuperAdmin()) {
            return $query;
        }

        // CATATAN: Jika 'administration' adalah admin PUSAT dan boleh melihat seluruh
        // data dari semua site (sama seperti superadmin), buka komentar baris di bawah ini:
        // if ($user->role === 'administration') return $query;

        return $query->where(function ($q) use ($user) {
            // A. Semua user bisa melihat klaim miliknya sendiri
            $q->where('user_id', $user->id);

            // B. Role 'administration' bisa melihat SEMUA klaim di SITE (Cabang) mereka
            // tanpa mempedulikan status approvalnya.
            if ($user->role === 'administration') {
                $q->orWhereHas('user', function ($owner) use ($user) {
                    $owner->where('site_id', $user->site_id ?? 0);
                });
            }

            // C. Role Approver HANYA melihat klaim yang butuh persetujuan mereka
            $status = self::approvalStatus($user->role);
            if (in_array($user->role, ['team_leader', 'station_master', 'manager'])) {
                $q->orWhere(function ($review) use ($user, $status) {
                    $review->where('status', $status);
                    if ($user->role !== 'manager') {
                        // Leader & Station Master difilter per site
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
        // Jika Administration boleh MENGHAPUS / EDIT data orang lain di cabangnya,
        // tambahkan $user->role === 'administration' di sini.
        // Saat ini hanya superadmin dan pembuat klaim yang bisa.
        abort_unless(
            auth()->user()->isSuperAdmin() ||
                auth()->user()->role === 'administration' || // <-- Tambahkan ini jika Admin boleh edit/hapus klaim staf
                (int) $claim->user_id === (int) auth()->id(),
            403
        );
    }

    public static function approve(Reimbursement $claim): void
    {
        self::view($claim);
        $role = auth()->user()->role;
        abort_if(in_array($claim->status, ['approved', 'rejected']), 409, 'Klaim sudah selesai diproses.');

        if ($role !== 'superadmin') {
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
