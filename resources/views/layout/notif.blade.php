@php
    $alerts = [
        'success' => 'bg-green-100 border-green-300 text-green-800',
        'error' => 'bg-red-100 border-red-300 text-red-800',
        'warning' => 'bg-yellow-100 border-yellow-300 text-yellow-900',
    ];
@endphp

<div class="fixed z-50 space-y-2 pointer-events-none top-4 right-4 left-4 sm:left-auto sm:w-80">
    {{-- 1. NOTIFIKASI SESSION --}}
    @foreach ($alerts as $type => $classes)
        @if (session($type))
            <div x-data="{ show: false }" x-init="setTimeout(() => show = true, 50);
            setTimeout(() => show = false, 3500)" x-show="show"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-3 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-300"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                class="pointer-events-auto p-4 text-xs sm:text-sm font-semibold border shadow-xl rounded-2xl backdrop-blur-md {{ $classes }}">
                <div class="flex items-start gap-2">
                    <i
                        class="mt-0.5 fa-solid {{ $type === 'success' ? 'fa-circle-check' : ($type === 'error' ? 'fa-circle-xmark' : 'fa-triangle-exclamation') }}"></i>
                    <span>{{ session($type) }}</span>
                </div>
            </div>
        @endif
    @endforeach

    {{-- 2. NOTIFIKASI VALIDATION ERROR --}}
    @if ($errors->any())
        <div x-data="{ show: false }" x-init="setTimeout(() => show = true, 50);
        setTimeout(() => show = false, 6000)" x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-3 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-2 scale-95"
            class="p-4 text-xs font-semibold text-red-800 bg-red-100 border border-red-300 shadow-xl pointer-events-auto sm:text-sm rounded-2xl">
            <div class="mb-1 font-bold flex items-center gap-1.5">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Periksa Kembali Input:</span>
            </div>
            <ul class="space-y-0.5 list-disc list-inside font-normal pl-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
