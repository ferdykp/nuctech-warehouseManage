<div class="fixed top-4 right-4 z-[100] w-[calc(100%-2rem)] max-w-sm space-y-3 pointer-events-none px-4 sm:px-0">

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
                        'title' => 'Berhasil',
                    ],
                    'error' => [
                        'bg' => 'bg-rose-50/95',
                        'border' => 'border-rose-200',
                        'text' => 'text-rose-900',
                        'icon_bg' => 'bg-rose-500',
                        'icon' => 'fa-xmark',
                        'title' => 'Ada kendala',
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

            <div x-data="{ show: true }" x-show="show" @if ($type === 'success') x-init="setTimeout(() => show = false, 5000)" @endif role="status"
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


</div>
