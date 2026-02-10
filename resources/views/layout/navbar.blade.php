<nav class="relative z-30 mt-4 mr-4 ml-4 md:ml-0 rounded-xl bg-[#1B3C53]">
    <div class="flex items-center justify-between px-4 py-3">

        <div class="flex items-center gap-3">
            {{-- TOMBOL HAMBURGER MOBILE --}}
            <button @click="sidebarOpen = true"
                class="p-2 text-white rounded-lg hover:bg-white/10 md:hidden focus:outline-none">
                <i class="text-xl fa-solid fa-bars"></i>
            </button>

            {{-- BREADCRUMB --}}
            <nav aria-label="Breadcrumb" class="items-center hidden space-x-2 text-sm text-white sm:flex">
                <a href="{{ url('/dashboard') }}"
                    class="flex items-center transition-opacity opacity-80 hover:opacity-100">
                    <i class="fa-solid fa-house mr-2 text-[10px]"></i> Home
                </a>

                @foreach (request()->segments() as $segment)
                    <span class="opacity-50">/</span>
                    <span class="{{ $loop->last ? 'font-semibold' : 'opacity-70' }}">
                        @if ($loop->last && isset($siteData))
                            {{-- Jika ini adalah bagian terakhir URL dan kita punya data site, tampilkan Nama Mesinnya --}}
                            {{ $siteData->machine_name }}
                        @else
                            {{-- Jika bukan, tampilkan teks segment biasa (seperti 'Inventory') --}}
                            {{ ucfirst(str_replace('-', ' ', $segment)) }}
                        @endif
                    </span>
                @endforeach
            </nav>
        </div>

        {{-- RIGHT AREA --}}
        <div class="flex items-center gap-3">
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" @click.outside="open = false"
                    class="flex items-center gap-2 px-3 py-2 text-sm font-semibold text-white transition-colors border rounded-lg bg-blue-950/50 border-white/10 hover:bg-white/10">
                    <i class="text-base fa-solid fa-circle-user"></i>
                    {{-- Ganti xs:block ke sm:block atau block saja --}}
                    <span class="hidden sm:block">Halo, {{ auth()->user()->username }}</span>
                    <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200"
                        :class="open ? 'rotate-180' : ''"></i>
                </button>

                {{-- DROPDOWN --}}
                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    class="absolute right-0 z-50 mt-2 overflow-hidden bg-white border border-gray-100 shadow-xl w-44 rounded-xl">

                    @if (auth()->user()->role === 'admin')
                        <a href="{{ route('users.show', auth()->id()) }}"
                            class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                            <i class="w-4 text-blue-600 fa-solid fa-user-gear"></i> Profile
                        </a>
                    @endif

                    <a href="{{ route('auth.logout') }}"
                        class="flex items-center gap-2 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors">
                        <i class="w-4 fa-solid fa-right-from-bracket"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>
