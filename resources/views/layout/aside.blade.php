<div id="overlay" x-show="sidebarOpen" @click="sidebarOpen = false" x-cloak
    x-transition:enter="transition opacity duration-300" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100" x-transition:leave="transition opacity duration-200"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-40 bg-black/50 md:hidden">
</div>

<aside id="sidebar" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-50 w-64 px-4 py-6 m-3 space-y-6 transition-transform duration-300 ease-in-out 
    bg-[#1B3C53] rounded-lg md:translate-x-0 md:relative md:block h-[calc(100vh-1.5rem)] overflow-y-auto">


    {{-- LOGO --}}
    <div class="flex items-center justify-center p-3 bg-white border shadow-sm rounded-xl">
        <a href="/" class="flex items-center space-x-4 group">
            <img src="{{ asset('img/logo-txt-removebg.png') }}"
                class="h-8 transition-transform duration-300 group-hover:scale-110">
        </a>
    </div>

    <nav class="mt-10 space-y-1">

        {{-- DASHBOARD --}}
        <a href="{{ route('dashboard') }}"
            class="flex items-center gap-3 px-4 py-2 text-white rounded-lg
        {{ request()->routeIs('dashboard') ? 'bg-[#2d729b]' : 'hover:bg-[#2d729b]' }}">
            <i class="w-5 text-center fa-solid fa-gauge-high"></i>
            <span>Dashboard</span>
        </a>

        {{-- BRANCHES --}}
        <a href="{{ route('branches.index') }}"
            class="flex items-center gap-3 px-4 py-2 text-white rounded-lg
        {{ request()->routeIs('branches.*') ? 'bg-[#2d729b]' : 'hover:bg-[#2d729b]' }}">
            <i class="w-5 text-center fa-solid fa-building"></i>
            <span>Branches</span>
        </a>

        {{-- MACHINE GROUP --}}
        <div x-data="{ open: {{ request()->routeIs('sites.*') || request()->routeIs('spareparts.*') ? 'true' : 'false' }} }">

            <button @click="open = !open"
                class="flex items-center justify-between w-full px-4 py-2 text-white rounded-lg hover:bg-[#2d729b]">

                <div class="flex items-center gap-3">
                    <i class="w-5 text-center fa-solid fa-microchip"></i>
                    <span class="font-medium">Machine</span>
                </div>

                <i class="text-xs transition-transform duration-300 fa-solid fa-chevron-down"
                    :class="{ 'rotate-180': open }"></i>
            </button>

            {{-- SUB MENU --}}
            <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                class="pl-4 mt-1 ml-3 space-y-1 border-l border-gray-500/50">

                @foreach ($sidebarSites as $site)
                    <a href="{{ route('sparepart.index', $site->slug) }}"
                        class="flex items-center gap-3 py-2 pl-3 pr-2 text-sm text-white rounded-lg hover:bg-[#2d729b]/60
                    {{ request()->segment(2) == $site->slug ? 'bg-[#2d729b] font-semibold text-emerald-300 border-l-2 border-emerald-400' : '' }}">

                        <i class="fa-solid fa-circle text-[7px] opacity-70"></i>
                        <span class="truncate">{{ $site->machine_name }}</span>
                    </a>
                @endforeach

                @if (Auth::user()->role === 'admin')
                    <button @click="$dispatch('open-add-machine')"
                        class="flex items-center w-full px-4 py-2 text-sm transition-all rounded-r-lg text-emerald-300 hover:bg-emerald-500/20">
                        <i class="mr-3 fa-solid fa-plus"></i> Add Machine </button>
                @endif

            </div>
        </div>

        {{-- REPORT --}}
        <a href="{{ route('report.index') }}"
            class="flex items-center gap-3 px-4 py-2 text-white rounded-lg
        {{ request()->routeIs('report.*') ? 'bg-[#2d729b]' : 'hover:bg-[#2d729b]' }}">
            <i class="w-5 text-center fa-solid fa-file"></i>
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
