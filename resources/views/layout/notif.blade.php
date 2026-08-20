<div class="fixed top-5 right-5 z-[100] w-full max-w-sm space-y-3 pointer-events-none px-4 sm:px-0">

    {{-- SESSION FLASH MESSAGES --}}
    @foreach (['success', 'error', 'warning'] as $type)
        @if (session($type))
            @php
                $config = [
                    'success' => [
                        'bg' => 'bg-emerald-50/95',
                        'border' => 'border-emerald-200',
                        'text' => 'text-emerald-900',
                        'icon_bg' => 'bg-emerald-500',
                        'icon' => 'fa-check',
                        'title' => 'Success',
                    ],
                    'error' => [
                        'bg' => 'bg-rose-50/95',
                        'border' => 'border-rose-200',
                        'text' => 'text-rose-900',
                        'icon_bg' => 'bg-rose-500',
                        'icon' => 'fa-xmark',
                        'title' => 'Error',
                    ],
                    'warning' => [
                        'bg' => 'bg-amber-50/95',
                        'border' => 'border-amber-200',
                        'text' => 'text-amber-900',
                        'icon_bg' => 'bg-amber-500',
                        'icon' => 'fa-exclamation',
                        'title' => 'Warning',
                    ],
                ][$type];
            @endphp

            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-[-10px] scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-[-10px] scale-95"
                class="pointer-events-auto flex items-center justify-between gap-3 p-3.5 rounded-2xl border shadow-xl backdrop-blur-md transition-all {{ $config['bg'] }} {{ $config['border'] }}">

                {{-- Left: Icon & Text Container (Vertically Centered) --}}
                <div class="flex items-center min-w-0 gap-3">
                    <div
                        class="flex items-center justify-center w-7 h-7 rounded-xl text-white font-bold text-xs shrink-0 shadow-sm {{ $config['icon_bg'] }}">
                        <i class="fa-solid {{ $config['icon'] }}"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold leading-tight {{ $config['text'] }}">
                            {{ session($type) }}
                        </p>
                    </div>
                </div>

                {{-- Right: Close Button (Vertically Centered) --}}
                <button type="button" @click="show = false"
                    class="flex items-center justify-center w-6 h-6 transition-colors rounded-lg text-slate-400 hover:text-slate-700 hover:bg-black/5 shrink-0 focus:outline-none">
                    <i class="text-xs fa-solid fa-xmark"></i>
                </button>
            </div>
        @endif
    @endforeach

    {{-- VALIDATION ERRORS --}}
    @if ($errors->any())
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-[-10px] scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            class="p-4 border shadow-xl pointer-events-auto rounded-2xl border-rose-200 bg-rose-50/95 backdrop-blur-md text-rose-900">

            <div class="flex items-center justify-between pb-2 mb-2 border-b border-rose-200/60">
                <div class="flex items-center gap-2">
                    <div class="flex items-center justify-center w-6 h-6 text-xs text-white rounded-lg bg-rose-500">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <span class="text-xs font-bold">Please Check Your Inputs:</span>
                </div>
                <button type="button" @click="show = false" class="p-1 rounded-lg text-rose-400 hover:text-rose-700">
                    <i class="text-xs fa-solid fa-xmark"></i>
                </button>
            </div>

            <ul class="pl-4 space-y-1 text-xs font-medium list-disc text-rose-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

</div>
