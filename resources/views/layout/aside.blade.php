{{-- OVERLAY --}}
<div id="overlay" x-show="sidebarOpen" @click="sidebarOpen = false" x-cloak
    x-transition:enter="transition opacity duration-300" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100" x-transition:leave="transition opacity duration-200"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm md:hidden">
</div>

{{-- SIDEBAR --}}
<aside id="sidebar" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-50 w-72 px-4 py-6 m-3 space-y-6 transition-all duration-300 ease-in-out bg-[#0F172A] shadow-2xl rounded-2xl md:translate-x-0 md:relative md:block h-[calc(100vh-1.5rem)] overflow-y-auto border border-white/10">

    {{-- LOGO SECTION --}}
    {{-- <div class="flex items-center justify-center py-4 mb-6 bg-white">
        <a href="/" class="flex items-center transition-transform hover:scale-105">
            <img src="{{ asset('img/logo-txt-removebg.png') }}" class="h-9 ">
        </a>
    </div> --}}
    <div class="flex items-center justify-center p-3 bg-white border shadow-sm rounded-xl">
        <a href="/" class="flex items-center space-x-4 group">
            <img src="{{ asset('img/logo-txt-removebg.png') }}"
                class="h-8 transition-transform duration-300 group-hover:scale-110">
        </a>
    </div>

    {{-- NAV MENU --}}
    <nav class="space-y-1.5">
        <p class="px-4 pb-2 text-xs font-bold tracking-widest uppercase text-slate-500">Main Menu</p>

        {{-- Helper Macro for Links (Conceptually) --}}
        @php
            $navItemClass =
                'flex items-center gap-3 px-4 py-2.5 text-slate-300 rounded-xl transition-all duration-200 group hover:bg-white/10 hover:text-white';
            $activeClass = 'bg-blue-600 !text-white shadow-lg shadow-blue-600/30 font-medium';
        @endphp

        <a href="{{ route('dashboard') }}"
            class="{{ $navItemClass }} {{ request()->routeIs('dashboard') ? $activeClass : '' }}">
            {{-- <i class="w-5 fa-solid fa-grid-2"></i> --}}
            <i class="w-5 text-center fa-solid fa-gauge-high"></i>
            <span>Dashboard</span>
        </a>

        <a href="{{ route('branches.index') }}"
            class="{{ $navItemClass }} {{ request()->routeIs('branches.*') ? $activeClass : '' }}">
            <i class="w-5 fa-solid fa-building-user"></i>
            <span>Branches</span>
        </a>

        {{-- MACHINE DROPDOWN GROUP --}}
        <div x-data="{ open: {{ request()->routeIs('sites.*') || request()->routeIs('sparepart.index') ? 'true' : 'false' }} }" class="pt-2">
            <button @click="open = !open"
                class="flex items-center justify-between w-full px-4 py-2.5 text-slate-300 rounded-xl hover:bg-white/10 transition-all">
                <div class="flex items-center gap-3">
                    <i class="w-5 fa-solid fa-microchip"></i>
                    <span class="font-medium">Machine Sites</span>
                </div>
                <i class="text-[10px] transition-transform duration-300 fa-solid fa-chevron-down"
                    :class="{ 'rotate-180': open }"></i>
            </button>

            <div x-show="open" x-cloak x-collapse class="relative mt-1 ml-6 space-y-1 border-l-2 border-slate-700">
                @foreach ($sidebarSites as $site)
                    <a href="{{ route('sparepart.index', $site->slug) }}"
                        class="flex items-center gap-3 py-2 pl-4 pr-2 text-sm transition-all rounded-r-lg group
                        {{ request()->segment(2) == $site->slug ? 'text-blue-400 border-l-2 border-blue-400 -ml-[2px] bg-blue-400/10' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                        <span class="truncate">{{ $site->machine_name }}</span>
                    </a>
                @endforeach

                @if (Auth::user()->role === 'superadmin')
                    <button @click="$dispatch('open-add-machine')"
                        class="flex items-center w-full px-4 py-2 mt-2 text-xs font-bold tracking-wide uppercase transition-all rounded-lg text-emerald-400 hover:bg-emerald-400/10">
                        <i class="mr-2 fa-solid fa-plus-circle"></i> New Machine
                    </button>
                @endif
            </div>
        </div>

        <div class="pt-4">
            <p class="px-4 pb-2 text-xs font-bold tracking-widest uppercase text-slate-500">Inventory & Logistics</p>
            <a href="{{ route('categories.index') }}"
                class="{{ $navItemClass }} {{ request()->routeIs('categories.*') ? $activeClass : '' }}">
                <i class="w-5 fa-solid fa-tags"></i>
                <span>Categories</span>
            </a>
            <a href="{{ route('sparepart.all') }}"
                class="{{ $navItemClass }} {{ request()->routeIs('sparepart.all') ? $activeClass : '' }}">
                <i class="w-5 fa-solid fa-boxes-stacked"></i>
                <span>All Spareparts</span>
            </a>
            <a href="{{ route('report.index') }}"
                class="{{ $navItemClass }} {{ request()->routeIs('report.*') ? $activeClass : '' }}">
                <i class="w-5 fa-solid fa-clipboard-list"></i>
                <span>Failure Reports</span>
            </a>
        </div>
    </nav>
</aside>
{{-- MODAL ADD MACHINE --}}
<div x-data="{ show: false }" x-on:open-add-machine.window="show = true" x-show="show" x-cloak
    class="fixed inset-0 z-[70] flex items-center justify-center p-4">

    {{-- Backdrop --}}
    <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" class="fixed inset-0 bg-slate-900/80 backdrop-blur-md"></div>

    {{-- MODAL BOX --}}
    <div x-show="show" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-8 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" @click.away="show = false"
        class="relative w-full max-w-lg overflow-hidden bg-white shadow-2xl rounded-2xl">

        <div class="absolute top-0 left-0 w-full h-1.5 bg-blue-600"></div>

        <div class="flex items-center justify-between px-8 py-6 border-b border-slate-100">
            <div>
                <h3 class="text-xl font-bold text-slate-800">New Machine Site</h3>
                <p class="text-sm text-slate-500">Register a new unit to the tracking system.</p>
            </div>
            <button @click="show = false"
                class="p-2 transition-colors rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                <i class="text-lg fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('sites.store') }}" method="POST" class="px-8 py-6 space-y-5">
            @csrf
            <div class="grid gap-5">
                <div class="space-y-1">
                    <label class="text-sm font-bold text-slate-700">Machine Name</label>
                    <input type="text" name="machine_name" required
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none transition-all"
                        placeholder="e.g., FS6000 Jakarta Main">
                </div>

                <div class="space-y-1">
                    <label class="text-sm font-bold text-slate-700">Branch Location</label>
                    <select name="branch_id" required
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none transition-all">
                        <option value="" disabled selected>Choose a branch...</option>
                        @foreach (\App\Models\Branch::all() as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="text-sm font-bold text-slate-700">Detailed Address</label>
                    <textarea name="location" rows="3"
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none transition-all"
                        placeholder="Street name, Floor, or specific coordinates..."></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4">
                <button type="button" @click="show = false"
                    class="px-5 py-2.5 text-sm font-semibold text-slate-600 hover:text-slate-800 transition-colors">
                    Cancel
                </button>
                <button type="submit"
                    class="px-8 py-2.5 text-sm font-bold text-white bg-blue-600 rounded-xl shadow-lg shadow-blue-600/20 hover:bg-blue-700 active:scale-95 transition-all">
                    Register Machine
                </button>
            </div>
        </form>
    </div>
</div>
