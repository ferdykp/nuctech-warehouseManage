<?php

namespace App\Services\Contracts;

use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class ContractRenewalList
{
    public function filters(Request $request): array
    {
        $data = $request->validate([
            'year' => 'nullable|integer|between:2000,2100', 'month' => 'nullable|integer|between:1,12',
            'month_end' => 'nullable|integer|between:1,12', 'branch_id' => 'nullable|integer|exists:branches,id',
            'site_id' => 'nullable|integer|exists:sites,id', 'employee_id' => 'nullable|integer|exists:employees,id',
            'position' => 'nullable|string|max:200', 'search' => 'nullable|string|max:150',
            'is_active' => ['nullable', Rule::in(['active', 'inactive', 'all'])],
            'employment_status' => ['nullable', Rule::in(['Permanent', 'Contract', 'Probation', 'Daily', 'Resigned'])],
            'renewal_state' => ['nullable', Rule::in(['needs', 'approved', 'all'])],
            'status' => ['nullable', Rule::in(['attention', 'needs_verification', 'draft', 'pending_signature', 'active', 'scheduled', 'expired', 'due', 'cancelled'])],
        ]);
        if (! empty($data['site_id']) && ! empty($data['branch_id']) && ! \App\Models\Site::whereKey($data['site_id'])->where('branch_id', $data['branch_id'])->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['site_id' => 'Site machine tidak sesuai branch yang dipilih.']);
        }
        $month = (int) ($data['month'] ?? today()->month);
        $end = (int) ($data['month_end'] ?? $month);
        if ($end < $month) {
            throw \Illuminate\Validation\ValidationException::withMessages(['month_end' => 'Bulan akhir harus sama atau setelah bulan awal.']);
        }
        $data = array_merge(['year' => today()->year, 'is_active' => 'active', 'renewal_state' => 'needs'], array_filter($data, fn ($v) => $v !== null && $v !== ''));
        $data['year'] = (int) $data['year'];
        $data['month'] = $month;
        $data['month_end'] = $end;

        return $data;
    }

    public function rows(array $filters): Collection
    {
        $start = Carbon::create($filters['year'], $filters['month'], 1)->startOfDay();
        $end = Carbon::create($filters['year'], $filters['month_end'], 1)->endOfMonth();
        $query = Employee::with(['branch', 'site', 'contracts' => fn ($q) => $q->withExists(['renewals as has_approved_renewal' => fn ($r) => $r->where('status', 'active')])]);
        foreach (['employee_id' => 'id', 'branch_id' => 'branch_id', 'site_id' => 'site_id', 'position' => 'position', 'employment_status' => 'status'] as $filter => $column) {
            if (! empty($filters[$filter])) {
                $query->where($column, $filters[$filter]);
            }
        }
        if ($filters['is_active'] === 'active') {
            $query->where('is_active', true)->where('status', '!=', 'Resigned');
        } elseif ($filters['is_active'] === 'inactive') {
            $query->where(fn ($q) => $q->where('is_active', false)->orWhere('status', 'Resigned'));
        }
        $query->where(fn ($q) => $q->whereNotNull('pkwt_date')->orWhereHas('contracts', fn ($c) => $c->whereNotNull('end_date')));

        return $query->orderBy('name')->get()->map(function (Employee $e) use ($start, $end, $filters) {
            $verified = $e->contracts->where('status', 'active')->filter(fn ($c) => $c->end_date && $c->start_date);
            $contract = $verified->filter(fn ($c) => $c->end_date->betweenIncluded($start, $end))->sortByDesc('end_date')->first();
            $legacy = false;
            if ($contract) {
                $date = $contract->end_date->copy();
                $approved = (bool) $contract->has_approved_renewal;
            } else {
                // A verified contract supersedes the legacy anniversary, even when it lies outside the chosen month.
                if ($verified->isNotEmpty() || ! $e->pkwt_date || $filters['year'] <= $e->pkwt_date->year) {
                    return null;
                }
                $date = Carbon::create($filters['year'], $e->pkwt_date->month, 1);
                $date->day(min($e->pkwt_date->day, $date->daysInMonth));
                if (! $date->betweenIncluded($start, $end)) {
                    return null;
                }
                $contract = $e->contracts->whereIn('status', ['needs_verification', 'draft', 'pending_signature', 'cancelled'])->sortByDesc('id')->first();
                $approved = false;
                $legacy = true;
            }
            $workflow = ($contract && ! $legacy ? $e->contracts->where('previous_contract_id', $contract->id)->sortByDesc('id')->first() : null) ?? $contract;
            if (($filters['renewal_state'] === 'needs' && ($approved || $workflow?->status === 'cancelled')) || ($filters['renewal_state'] === 'approved' && ! $approved)) {
                return null;
            }
            $status = $filters['status'] ?? '';
            $matches = match ($status) {
                'attention' => $legacy || in_array($workflow?->status, ['draft', 'pending_signature', 'needs_verification']),
                'needs_verification' => $legacy && (! $contract || $contract->status === 'needs_verification'),
                'active' => $contract?->status === 'active' && $contract->start_date->lte(today()) && $contract->end_date->gte(today()),
                'scheduled' => $contract?->status === 'active' && $contract->start_date->gt(today()),
                'expired' => $contract?->status === 'active' && $contract->end_date->lt(today()),
                'due' => ! $approved && $date->betweenIncluded(today(), today()->addDays(60)),
                'draft','pending_signature','cancelled' => $workflow?->status === $status,
                default => true,
            };
            if (! $matches) {
                return null;
            }
            $number = $contract?->contract_number ?? $contract?->snapshot['legacy_contract_number'] ?? $e->pkwt_number;
            if (! empty($filters['search']) && ! str_contains(mb_strtolower($e->name.' '.$number), mb_strtolower($filters['search']))) {
                return null;
            }
            $missing = [];
            foreach (['position' => 'position', 'bank_name' => 'bank', 'bank_account_number' => 'rekening', 'basic_salary' => 'gaji pokok'] as $field => $label) {
                if (blank($e->$field)) {
                    $missing[] = $label;
                }
            }

            return ['employee' => $e, 'contract' => $workflow, 'date' => $date, 'legacy' => $legacy, 'approved' => $approved,
                'number' => $number, 'amount' => (string) $e->basic_salary, 'amount_label' => self::amountLabel($e->basic_salary), 'missing' => $missing,
                'label' => $approved ? 'Perpanjangan Disetujui' : ($legacy ? 'Perlu Verifikasi' : $workflow->display_status)];
        })->filter()->sortBy(fn ($r) => $r['date']->format('Y-m-d').' '.$r['employee']->name)->values();
    }

    public static function amountLabel(mixed $value): string
    {
        if (blank($value)) {
            return '—';
        }
        $cents = (int) round((float) $value * 100);

        return 'Rp'.number_format($cents / 100, $cents % 100 === 0 ? 0 : 2, ',', '.');
    }

    public function period(array $filters, string $locale = 'id'): string
    {
        $start = Carbon::create($filters['year'], $filters['month'], 1)->locale($locale);
        $end = Carbon::create($filters['year'], $filters['month_end'], 1)->locale($locale);

        return $start->translatedFormat('F').($filters['month'] === $filters['month_end'] ? '' : '–'.$end->translatedFormat('F')).' '.$filters['year'];
    }
}
