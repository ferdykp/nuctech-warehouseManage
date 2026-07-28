<header class="relative z-30 px-3 pt-3 sm:pt-4 sm:px-4 md:pl-4 md:pr-6 md:pt-4">
    <nav class="w-full rounded-2xl bg-[#1B3C53] shadow-md border border-white/10">
        <div class="flex items-center justify-between px-3 py-2.5 sm:px-5 sm:py-3">

            <div class="flex items-center min-w-0 gap-2 sm:gap-3">
                {{-- TOMBOL HAMBURGER MOBILE & TABLET --}}
                <button @click="sidebarOpen = true" type="button"
                    class="p-2 text-white transition-colors rounded-xl hover:bg-white/10 md:hidden focus:outline-none">
                    <i class="text-lg sm:text-xl fa-solid fa-bars"></i>
                </button>

                {{-- BREADCRUMB RESPONSIV --}}
                <nav aria-label="Breadcrumb"
                    class="items-center hidden min-w-0 space-x-2 text-xs text-white sm:text-sm sm:flex">
                    <a href="{{ url('/dashboard') }}"
                        class="flex items-center transition-opacity opacity-80 hover:opacity-100 shrink-0">
                        <i class="fa-solid fa-house mr-1.5 text-[10px]"></i> Home
                    </a>

                    @foreach (request()->segments() as $segment)
                        <span class="opacity-40 shrink-0">/</span>
                        <span class="{{ $loop->last ? 'font-semibold text-white truncate' : 'opacity-70 truncate' }}">
                            @if ($loop->last && isset($siteData))
                                {{ $siteData->machine_name }}
                            @else
                                {{ ucfirst(str_replace('-', ' ', $segment)) }}
                            @endif
                        </span>
                    @endforeach
                </nav>

                {{-- NAMA HALAMAN KHUSUS MOBILE --}}
                <div class="text-sm font-bold text-white truncate sm:hidden">
                    @if (isset($siteData))
                        {{ $siteData->machine_name }}
                    @else
                        {{ ucfirst(str_replace('-', ' ', request()->segment(1) ?? 'Dashboard')) }}
                    @endif
                </div>
            </div>

            {{-- RIGHT AREA / PROFIL DROPDOWN --}}
            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" @click.outside="open = false" type="button"
                        class="flex items-center gap-2 px-2.5 py-1.5 sm:px-3 sm:py-2 text-xs sm:text-sm font-semibold text-white transition-colors border rounded-xl bg-blue-950/40 border-white/10 hover:bg-white/10">
                        <i class="text-base text-blue-300 sm:text-lg fa-solid fa-circle-user"></i>

                        <span class="hidden md:inline-block max-w-[120px] truncate">
                            Halo, {{ auth()->user()?->username ?? 'Guest' }}
                        </span>

                        <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200 opacity-70"
                            :class="open ? 'rotate-180' : ''"></i>
                    </button>

                    {{-- DROPDOWN MENU --}}
                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        class="absolute right-0 z-50 w-48 mt-2 overflow-hidden bg-white border border-gray-100 shadow-xl rounded-2xl">

                        <div class="px-4 py-2 border-b border-gray-100 bg-slate-50 md:hidden">
                            <p class="text-xs font-bold truncate text-slate-800">
                                {{ auth()->user()?->username ?? 'Guest' }}</p>
                            <p class="text-[10px] text-slate-500 uppercase font-semibold">
                                {{ auth()->user()?->role ?? 'User' }}</p>
                        </div>

                        <a href="{{ auth()->check() ? route('profile.profileShow', auth()->id()) : '#' }}"
                            class="flex items-center gap-2 px-4 py-2.5 text-xs sm:text-sm text-gray-700 hover:bg-slate-50 transition-colors">
                            <i class="w-4 text-blue-600 fa-solid fa-user"></i> Profil Saya
                        </a>

                        <div class="border-t border-gray-100"></div>

                        <a href="{{ route('auth.logout') }}"
                            class="flex items-center gap-2 px-4 py-2.5 text-xs sm:text-sm text-red-600 hover:bg-red-50 transition-colors font-medium">
                            <i class="w-4 fa-solid fa-right-from-bracket"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>
