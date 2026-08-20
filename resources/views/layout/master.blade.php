<!DOCTYPE html>
<html lang="id" class="h-full antialiased bg-slate-100">

<head>
    @include('layout.head')
    @stack('head')
</head>

<body class="flex h-full overflow-hidden font-sans bg-slate-100 text-slate-800" x-data="{ sidebarOpen: false }">

    <!-- SIDEBAR NAVIGATION -->
    @if (!request()->routeIs(['login', 'register', 'password.*']))
        @include('layout.aside')
    @endif

    <!-- MAIN WRAPPER -->
    <div class="flex flex-col flex-1 w-full h-full min-w-0 overflow-hidden">

        <!-- TOP NAVBAR -->
        @if (!request()->routeIs(['login', 'register', 'password.*']))
            @include('layout.navbar')
        @endif

        <!-- TOAST NOTIFICATION CENTER -->
        @include('layout.notif')

        <!-- MAIN CONTENT AREA (Kondisi khusus login disesuaikan) -->
        <main id="main-content" up-main
            class="flex-1 w-full h-full min-w-0 {{ request()->routeIs(['login', 'register', 'password.*']) ? 'overflow-hidden p-0' : 'overflow-y-auto overflow-x-hidden p-4 sm:p-6 lg:p-8' }}">
            @if (request()->routeIs(['login', 'register', 'password.*']))
                @yield('content')
            @else
                <div class="mx-auto max-w-7xl">
                    @yield('content')
                </div>
            @endif
        </main>
    </div>

    @include('layout.script')
    @stack('scripts')
</body>

</html>
