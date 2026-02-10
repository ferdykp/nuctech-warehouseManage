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


{{-- <body class="flex h-screen overflow-hidden ">

    @if (!request()->routeIs(['login']))
        @include('layout.aside')
    @endif



    <div class="flex flex-col flex-1 min-h-screen transition-all duration-300 ">
        @if (!request()->routeIs(['login', 'register', 'password.request']))
            @include('layout.navbar')
        @endif

        <main class="flex-1 h-full overflow-y-auto">
            @include('layout.notif')
            @yield('content')

        </main>

    </div>

    @include('layout.script')
    @stack('scripts')

</body> --}}

<body class="flex h-screen overflow-hidden" x-data="{ sidebarOpen: false }">
    @if (!request()->routeIs(['login']))
        @include('layout.aside')
    @endif

    <div class="flex flex-col flex-1 min-h-screen transition-all duration-300">
        @if (!request()->routeIs(['login', 'register', 'password.request']))
            @include('layout.navbar')
        @endif

        <main class="flex-1 h-full overflow-y-auto">
            @include('layout.notif')
            @yield('content')
        </main>
    </div>

    @include('layout.script')
    @stack('scripts')
</body>

</html>
