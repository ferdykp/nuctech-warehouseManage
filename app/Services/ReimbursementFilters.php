<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

class ReimbursementFilters
{
    public static function apply(Builder $query, array $filters): Builder
    {
        $status = $filters['status'] ?? null;
        if ($status === 'waiting') {
            $query->whereIn('status', ['pending', 'pending_leader', 'pending_station', 'pending_manager']);
        } elseif (in_array($status, ['pending', 'pending_leader', 'pending_station', 'pending_manager', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }
        if (!empty($filters['month'])) $query->whereMonth('date', $filters['month']);
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search) {
                foreach (['person_name', 'comment', 'category', 'from_location', 'to_location'] as $index => $field) {
                    $builder->{$index === 0 ? 'where' : 'orWhere'}($field, 'like', '%'.$search.'%');
                }
            });
        }
        return $query;
    }
}
