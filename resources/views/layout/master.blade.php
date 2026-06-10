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

<body class="flex w-screen h-screen overflow-hidden bg-slate-50" x-data="{ sidebarOpen: false }">
    @if (!request()->routeIs(['login']))
        @include('layout.aside')
    @endif

    {{-- PERBAIKAN: Ditambahkan min-w-0 dan w-full agar content flexbox menjadi super responsive --}}
    <div class="flex flex-col flex-1 w-full h-screen min-w-0 transition-all duration-300">
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
