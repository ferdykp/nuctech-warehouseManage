<!-- Overlay -->
<div id="overlay" class="fixed inset-0 z-40 hidden bg-black bg-opacity-50 md:hidden"></div>

<!-- Sidebar -->
<aside id="sidebar"
    class="justify-between text-gray-800 fixed inset-y-0 left-0 z-50 w-60 px-4 py-6 m-3 space-y-6 transition-transform duration-300 ease-in-out 
    transform -translate-x-full bg-[#1B3C53] rounded-lg md:translate-x-0 md:relative md:block">
    <div class="flex items-center justify-center p-3 bg-white border shadow-sm py- rounded-xl">
        <a href="" class="flex items-center space-x-4 group">
            <img src="{{ asset('img/logo-txt-removebg.png') }}"
                class="transition-transform duration-300 group-hover:scale-110" alt="ERP Logo">
        </a>
    </div>




    <nav class="mt-10 space-y-1">
        <a href="{{ route('dashboard') }}"
            class="block py-2 px-4 text-md rounded-lg text-white
          {{ request()->routeIs('dashboard') ? 'bg-[#2d729b]' : 'hover:bg-[#2d729b]' }}">
            Dashboard
        </a>
        {{-- MACHINE DROPDOWN --}}
        <div x-data="{ open: {{ request()->routeIs('fsjkt.*', 'fssmg.*', 'fssby.*', 'ebeam.*') ? 'true' : 'false' }} }" class="space-y-1">

            {{-- PARENT --}}
            <button @click="open = !open"
                class="flex items-center justify-between w-full px-4 py-2 text-md text-white rounded-lg hover:bg-[#2d729b] transition-colors duration-200">

                <span>Machine</span>

                {{-- ARROW --}}
                <svg class="w-4 h-4 transition-transform duration-300 ease-in-out" :class="{ 'rotate-180': open }"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            {{-- CHILD --}}
            <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 -translate-y-1 scale-95" class="ml-3 space-y-1 origin-top">
                <a href="{{ route('sparepart.index', ['site' => 'fsjkt']) }}"
                    class="block px-4 py-2 text-sm text-white rounded-lg
   {{ request()->route('site') === 'fsjkt' ? 'bg-[#2d729b]' : 'hover:bg-[#2d729b]' }}">
                    FS6000 Jakarta
                </a>
                <a href="{{ route('sparepart.index', ['site' => 'fssby']) }}"
                    class="block px-4 py-2 text-sm text-white rounded-lg
   {{ request()->route('site') === 'fssby' ? 'bg-[#2d729b]' : 'hover:bg-[#2d729b]' }}">
                    FS6000 Surabaya
                </a>
                <a href="{{ route('sparepart.index', ['site' => 'fssmg']) }}"
                    class="block px-4 py-2 text-sm text-white rounded-lg
   {{ request()->route('site') === 'fssmg' ? 'bg-[#2d729b]' : 'hover:bg-[#2d729b]' }}">
                    FS6000 Semarang
                </a>
                <a href="{{ route('sparepart.index', ['site' => 'ebeam']) }}"
                    class="block px-4 py-2 text-sm text-white rounded-lg
   {{ request()->route('site') === 'ebeam' ? 'bg-[#2d729b]' : 'hover:bg-[#2d729b]' }}">
                    E-Beam
                </a>
            </div>
        </div>

    </nav>
</aside>
