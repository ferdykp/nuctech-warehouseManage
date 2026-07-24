<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">

<head>
    @include('layout.head')
    @stack('head')
</head>
<style>
    [x-cloak] {
        display: none !important;
    }
</style>

<body class="flex w-screen h-full min-h-screen overflow-hidden font-sans antialiased bg-slate-50 text-slate-800"
    x-data="{ sidebarOpen: false }">

    @if (!request()->routeIs(['login']))
        @include('layout.aside')
    @endif

    {{-- CONTAINER UTAMA --}}
    <div class="flex flex-col flex-1 w-full h-full min-w-0 overflow-hidden transition-all duration-300">
        @if (!request()->routeIs(['login', 'register', 'password.request']))
            @include('layout.navbar')
        @endif

        {{-- AREA KONTEN UTAMA --}}
        <main
            class="flex-1 w-full h-full min-w-0 {{ request()->routeIs(['login']) ? 'overflow-hidden p-0' : 'overflow-x-hidden overflow-y-auto p-4 sm:p-6 lg:p-8' }}">
            @include('layout.notif')
            @yield('content')
        </main>
    </div>

    @include('layout.script')
    @stack('scripts')
</body>

</html>
