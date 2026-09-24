<?php

namespace App\Services;

use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class SiteAccess
{
    public static function sites(User $user, bool $scheduleGroup = false): Builder
    {
        $query = Site::query();
        if (in_array($user->role, ['superadmin', 'administration'], true)) {
            return $query;
        }
        if ($scheduleGroup && $user->role === 'team_leader' && $user->site) {
            // Preserve the project's existing machine-family schedule assignment.
            $prefix = substr(str_replace('-', '', explode(' ', trim($user->site->machine_name))[0]), 0, 5);
            if ($prefix !== '') return $query->where('machine_name', 'like', '%'.addcslashes($prefix, '%_\\').'%');
        }
        return $query->whereKey($user->site_id ?? 0);
    }

    public static function authorize($siteId, bool $scheduleGroup = false): void
    {
        abort_unless($siteId && self::sites(auth()->user(), $scheduleGroup)->whereKey($siteId)->exists(), 403, 'Anda tidak memiliki akses ke site ini.');
    }
}
