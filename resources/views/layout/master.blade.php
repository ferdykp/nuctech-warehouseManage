<!DOCTYPE html>
<html lang="en">

<head>
    @include('layout.head')
    @stack('head')
</head>
<style>
    [x-cloak] {
        display: none !important;
    }
</style>


<body class="flex h-screen overflow-hidden ">
    {{-- @include('layout.navbar') --}}

    <!-- Sidebar di kiri -->
    @if (!request()->routeIs(['login']))
        @include('layout.aside')
    @endif



    <!-- Bagian kanan (navbar + konten) -->
    <div class="flex flex-col flex-1 min-h-screen transition-all duration-300 ">
        <!-- Navbar -->
        {{-- @if (request()->routeIs('clientWed.dashboard') || request()->routeIs('clientWed.index')) --}}
        {{-- @include('layout.headNavbar') --}}
        {{-- @endif --}}
        {{-- @if (!request()->routeIs('login') && !request()->routeIs('register') && !request()->routeIs('clientWed.dashboard') && !request()->routeIs('clientWed.index'))
            @include('layout.navbar')
        @endif --}}
        @if (!request()->routeIs(['login', 'register', 'password.request']))
            @include('layout.navbar')
        @endif

        <!-- Konten utama -->
        <main class="flex-1 h-full overflow-y-auto">
            @include('layout.notif')
            @yield('content')

            {{-- Footer hanya muncul jika bukan di halaman login atau register --}}
            {{-- @if (!request()->routeIs('login') && !request()->routeIs('register') && !request()->routeIs('clientWed.dashboard') && !request()->routeIs('clientWed.index'))
                @include('layout.footer')
            @endif --}}
        </main>

    </div>

    @include('layout.script')
    @stack('scripts')

</body>

</html>
