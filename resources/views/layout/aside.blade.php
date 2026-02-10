<div id="overlay" x-show="sidebarOpen" @click="sidebarOpen = false" x-cloak
    x-transition:enter="transition opacity duration-300" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100" x-transition:leave="transition opacity duration-200"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-40 bg-black/50 md:hidden">
</div>

<aside id="sidebar" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-50 w-64 px-4 py-6 m-3 space-y-6 transition-transform duration-300 ease-in-out 
    bg-[#1B3C53] rounded-lg md:translate-x-0 md:relative md:block h-[calc(100vh-1.5rem)] overflow-y-auto">

    <div class="flex items-center justify-center p-3 bg-white border shadow-sm rounded-xl">
        <a href="/" class="flex items-center space-x-4 group">
            <img src="{{ asset('img/logo-txt-removebg.png') }}"
                class="h-8 transition-transform duration-300 group-hover:scale-110" alt="ERP Logo">
        </a>
    </div>

    <nav class="mt-10 space-y-1">
        <a href="{{ route('dashboard') }}"
            class="flex items-center px-4 py-2 text-md rounded-lg text-white transition-colors duration-200
            {{ request()->routeIs('dashboard') ? 'bg-[#2d729b]' : 'hover:bg-[#2d729b]' }}">
            <i class="w-6 fa-solid fa-gauge-high"></i>
            <span>Dashboard</span>
        </a>

        {{-- MACHINE DROPDOWN --}}
        {{-- <div x-data="{ open: {{ request()->routeIs('sparepart.*') ? 'true' : 'false' }} }" class="space-y-1">
            <button @click="open = !open"
                class="flex items-center justify-between w-full px-4 py-2 text-md text-white rounded-lg hover:bg-[#2d729b] transition-colors duration-200">
                <div class="flex items-center">
                    <i class="w-6 fa-solid fa-microchip"></i>
                    <span>Machine</span>
                </div>
                <i class="text-xs transition-transform duration-300 fa-solid fa-chevron-down"
                    :class="{ 'rotate-180': open }"></i>
            </button>

            <div x-show="open" x-cloak x-transition class="ml-6 space-y-1 border-l border-gray-500/50">
                @foreach (['fsjkt' => 'FS6000 Jakarta', 'fssby' => 'FS6000 Surabaya', 'fssmg' => 'FS6000 Semarang', 'ebeam' => 'E-Beam'] as $site => $label)
                    <a href="{{ route('sparepart.index', ['site' => $site]) }}"
                        class="flex items-center px-4 py-2 text-sm text-white rounded-r-lg hover:bg-[#2d729b]/50 transition-all
                        {{ request()->route('site') === $site ? 'bg-[#2d729b] border-l-2 border-white' : '' }}">
                        <i
                            class="fa-solid {{ $site === 'ebeam' ? 'fa-bolt' : 'fa-location-dot' }} mr-3 text-[10px]"></i>
                        {{ $label }}
                    </a>
                @endforeach
            </div>

        </div> --}}

        {{-- MACHINE DROPDOWN --}}
        <div x-data="{ open: {{ request()->routeIs('sites.*') ? 'true' : 'false' }} }" class="space-y-1">
            <button @click="open = !open"
                class="flex items-center justify-between w-full px-4 py-2 text-md text-white rounded-lg hover:bg-[#2d729b] transition-colors duration-200">
                <div class="flex items-center">
                    <i class="w-6 fa-solid fa-microchip"></i>
                    <span>Machine</span>
                </div>
                <i class="text-xs transition-transform duration-300 fa-solid fa-chevron-down"
                    :class="{ 'rotate-180': open }"></i>
            </button>

            <div x-show="open" x-cloak x-transition class="ml-6 space-y-1 border-l border-gray-500/50">
                @foreach (\App\Models\Site::with('branch')->get() as $site)
                    <a href="{{ route('sparepart.index', $site->slug) }}"
                        class="flex items-center px-4 py-2 text-sm text-white rounded-r-lg hover:bg-[#2d729b]/50 transition-all">
                        <i class="fa-solid fa-location-dot  mr-3 text-[10px]"></i>
                        {{ $site->machine_name }}
                    </a>
                @endforeach

                @if (Auth::user()->role === 'admin')
                    <button @click="$dispatch('open-add-machine')"
                        class="flex items-center w-full px-4 py-2 text-sm transition-all rounded-r-lg text-emerald-300 hover:bg-emerald-500/20">
                        <i class="mr-3 fa-solid fa-plus"></i> Add Machine </button>
                @endif
            </div>

        </div>
        <a href="{{ route('branches.index') }}"
            class="flex items-center px-4 py-2 text-md rounded-lg text-white transition-colors duration-200
            {{ request()->routeIs('report') ? 'bg-[#2d729b]' : 'hover:bg-[#2d729b]' }}">
            <i class="w-6 fa-solid fa fa-file"></i>
            <span>Branches</span>
        </a>
        <a href="{{ route('report.index') }}"
            class="flex items-center px-4 py-2 text-md rounded-lg text-white transition-colors duration-200
            {{ request()->routeIs('report') ? 'bg-[#2d729b]' : 'hover:bg-[#2d729b]' }}">
            <i class="w-6 fa-solid fa fa-file"></i>
            <span>Failure Report</span>
        </a>
    </nav>
</aside>


{{-- ADD MACHINE MODAL --}}

<div x-data="{ show: false }" x-on:open-add-machine.window="show = true" x-show="show" x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">

    ```
    {{-- MODAL BOX --}}
    <div @click.away="show=false" x-transition class="w-full max-w-xl mx-4 bg-white shadow-2xl rounded-2xl">

        {{-- HEADER --}}
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-bold">Add Machine</h3>
            <button @click="show=false" class="text-2xl text-gray-400 hover:text-red-500">&times;</button>
        </div>

        {{-- FORM --}}
        <form action="{{ route('sites.store') }}" method="POST" class="px-6 py-5 space-y-4">
            @csrf

            <div>
                <label class="block mb-1 text-sm font-semibold">Machine Name</label>
                <input type="text" name="machine_name"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 focus:outline-none"
                    placeholder="FS6000 Jakarta" required>
            </div>

            <div>
                <label class="block mb-1 text-sm font-semibold">Branch</label>
                <select name="branch_id"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 focus:outline-none"
                    required>
                    <option value="">-- Select Branch --</option>
                    @foreach (\App\Models\Branch::all() as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block mb-1 text-sm font-semibold">Location / Address</label>
                <textarea name="location" rows="3"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 focus:outline-none"></textarea>
            </div>

            {{-- ACTION --}}
            <div class="flex justify-end gap-3 pt-3">
                <button type="button" @click="show=false"
                    class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                    Cancel
                </button>

                <button type="submit"
                    class="px-5 py-2 font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                    Save Machine
                </button>
            </div>
        </form>

    </div>
    ```

</div>
