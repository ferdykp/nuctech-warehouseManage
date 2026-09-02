<header class="sticky top-0 z-30 px-4 py-3 bg-white border-b border-slate-200/80 sm:px-6">
    <div class="flex items-center justify-between gap-4 mx-auto max-w-7xl">

        <!-- LEFT SIDE: MOBILE TOGGLE & BREADCRUMB -->
        <div class="flex items-center min-w-0 gap-3">
            <button @click="sidebarOpen = true" type="button"
                class="p-2 text-slate-600 hover:text-slate-900 rounded-xl hover:bg-slate-100 lg:hidden focus:outline-none">
                <i class="text-lg fa-solid fa-bars-staggered"></i>
            </button>

            <!-- BREADCRUMBS -->
            <nav aria-label="Breadcrumb" class="items-center hidden space-x-2 text-xs sm:flex text-slate-500">
                <a href="{{ url('/dashboard') }}" class="flex items-center transition-colors hover:text-slate-800">
                    <i class="fa-solid fa-house mr-1.5 text-slate-400"></i> Home
                </a>

                @foreach (request()->segments() as $segment)
                    <i class="fa-solid fa-chevron-right text-[10px] text-slate-300"></i>
                    <span
                        class="{{ $loop->last ? 'font-bold text-slate-800 truncate' : 'hover:text-slate-800 truncate' }}">
                        @if ($loop->last && isset($siteData))
                            {{ $siteData->machine_name }}
                        @else
                            {{ ucfirst(str_replace('-', ' ', $segment)) }}
                        @endif
                    </span>
                @endforeach
            </nav>

            <span class="text-sm font-bold truncate sm:hidden text-slate-800">
                {{ ucfirst(str_replace('-', ' ', request()->segment(1) ?? 'Dashboard')) }}
            </span>
        </div>

        <!-- RIGHT SIDE: USER PROFILE DROPDOWN -->
        <div class="flex items-center gap-3 shrink-0">
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" @click.outside="open = false" type="button"
                    class="flex items-center gap-2.5 p-1.5 pl-3 rounded-full hover:bg-slate-100 border border-slate-200 transition-all">

                    <span class="text-xs font-bold text-slate-700 hidden md:inline-block max-w-[120px] truncate">
                        {{ auth()->user()?->username ?? 'User' }}
                    </span>

                    <div
                        class="flex items-center justify-center text-xs font-bold text-white bg-blue-600 rounded-full w-7 h-7">
                        {{ strtoupper(substr(auth()->user()?->username ?? 'U', 0, 1)) }}
                    </div>

                    <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 mr-1 transition-transform duration-200"
                        :class="open ? 'rotate-180' : ''"></i>
                </button>

                <!-- DROPDOWN CONTENT -->
                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                    class="absolute right-0 z-50 py-1 mt-2 bg-white border shadow-xl w-52 rounded-2xl border-slate-100">

                    <div class="px-4 py-2.5 border-b border-slate-100 bg-slate-50">
                        <p class="text-xs font-bold truncate text-slate-800">{{ auth()->user()?->username ?? 'Guest' }}
                        </p>
                        <p class="text-[10px] font-semibold text-slate-500 uppercase">
                            {{ auth()->user()?->role ?? 'User' }}</p>
                    </div>

                    <a href="{{ auth()->check() ? route('profile.profileShow', auth()->id()) : '#' }}"
                        class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition-colors">
                        <i class="w-4 fa-solid fa-user-gear text-slate-400"></i> Profile Settings
                    </a>

                    <div class="my-1 border-t border-slate-100"></div>

                    <form method="POST" action="{{ route('auth.logout') }}">
                        @csrf
                        <button type="submit"
                            class="flex items-center w-full gap-2.5 px-4 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 transition-colors text-left">
                            <i class="w-4 fa-solid fa-arrow-right-from-bracket"></i> Sign Out
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</header>
