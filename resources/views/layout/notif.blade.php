@php
    $alerts = [
        'success' => 'bg-green-100 border-green-300 text-green-700',
        'error' => 'bg-red-100 border-red-300 text-red-700',
        'warning' => 'bg-yellow-100 border-yellow-300 text-yellow-800',
    ];
@endphp

<div class="fixed z-50 max-w-sm space-y-3 top-5 right-5 w-80">
    {{-- 1. NOTIFIKASI SESSION (Success, Error, Warning) --}}
    @foreach ($alerts as $type => $classes)
        @if (session($type))
            <div x-data="{ show: false }" x-init="setTimeout(() => show = true, 50);
            setTimeout(() => show = false, 3500)" x-show="show"
                x-transition:enter="transition ease-out duration-500"
                x-transition:enter-start="opacity-0 translate-y-3 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-400"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                class="px-5 py-4 text-sm font-medium border shadow-xl rounded-xl {{ $classes }}">
                {{ session($type) }}
            </div>
        @endif
    @endforeach

    {{-- 2. NOTIFIKASI VALIDATION ERROR (Disamakan dengan gaya di atas) --}}
    @if ($errors->any())
        <div x-data="{ show: false }" x-init="setTimeout(() => show = true, 50);
        setTimeout(() => show = false, 6000)" x-show="show"
            x-transition:enter="transition ease-out duration-500"
            x-transition:enter-start="opacity-0 translate-y-3 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-400"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-2 scale-95"
            class="px-5 py-4 text-sm font-medium text-red-700 bg-red-100 border border-red-300 shadow-xl rounded-xl">
            <ul class="space-y-1 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
