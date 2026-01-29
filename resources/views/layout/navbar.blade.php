<nav class="relative z-40 mt-4 mr-4 rounded-xl bg-[#1B3C53]">
    <div class="flex items-center justify-between px-4 py-3">

        {{-- BREADCRUMB --}}
        @php
            $segments = request()->segments();
        @endphp

        <nav aria-label="Breadcrumb" class="flex items-center space-x-2 text-sm text-white">
            <a href="{{ url('/dashboard') }}" class="opacity-80 hover:opacity-100">
                Home
            </a>

            @foreach ($segments as $index => $segment)
                <span class="opacity-50">/</span>
                @php
                    $url = url(implode('/', array_slice($segments, 0, $index + 1)));
                    $isLast = $loop->last;
                @endphp

                @if ($isLast)
                    <span class="font-semibold">
                        {{ ucfirst(str_replace('-', ' ', $segment)) }}
                    </span>
                @else
                    <a href="{{ $url }}" class="opacity-70 hover:opacity-100">
                        {{ ucfirst(str_replace('-', ' ', $segment)) }}
                    </a>
                @endif
            @endforeach
        </nav>

        {{-- RIGHT AREA --}}
        <div class="flex items-center gap-4">

            {{-- USER DROPDOWN --}}
            <div x-data="{ open: false }" x-cloak class="relative">
                <button @click="open = !open"
                    class="flex items-center gap-2 px-3 py-2 text-sm font-semibold text-white rounded-lg bg-blue-950 hover:bg-white/10">
                    <svg class="w-4 h-4 opacity-80" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 10a4 4 0 100-8 4 4 0 000 8zm-6 8a6 6 0 1112 0H4z" />
                    </svg>
                    <span>Halo, {{ auth()->user()->username }}</span>
                    <svg class="w-4 h-4 opacity-70" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" />
                    </svg>
                </button>

                {{-- DROPDOWN --}}
                <div x-show="open" @click.outside="open = false" x-transition
                    class="absolute right-0 mt-2 overflow-hidden bg-white shadow-lg w-44 rounded-xl">
                    @if (auth()->user()->role === 'admin')
                        <a href="{{ route('users.show', auth()->id()) }}"
                            class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            Profile
                        </a>
                    @endif

                    <a href="{{ route('auth.logout') }}"
                        class="flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                        Logout
                    </a>
                </div>
            </div>

            {{-- MOBILE SIDEBAR TOGGLE --}}
            <button id="iconNavbarSidenav"
                class="flex flex-col justify-center gap-1 p-2 rounded-lg hover:bg-white/10 xl:hidden">
                <span class="w-5 h-0.5 bg-white"></span>
                <span class="w-5 h-0.5 bg-white"></span>
                <span class="w-5 h-0.5 bg-white"></span>
            </button>
        </div>

    </div>
</nav>
