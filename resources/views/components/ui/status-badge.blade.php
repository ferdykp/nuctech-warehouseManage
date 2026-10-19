@props(['status'])
@php
    $tone = match ($status) {
        'approved', 'received', 'active' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
        'rejected', 'damaged' => 'border-rose-200 bg-rose-50 text-rose-800',
        default => 'border-amber-200 bg-amber-50 text-amber-900',
    };
@endphp
<span {{ $attributes->class(['ui-status-badge inline-flex max-w-full items-center rounded-lg border px-2.5 py-1 text-xs font-bold leading-relaxed', $tone]) }}>{{ \App\Support\UiText::label($status) }}</span>
