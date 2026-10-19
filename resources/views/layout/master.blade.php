<!DOCTYPE html>
<html lang="id" class="h-full antialiased bg-slate-100">

<head>
    @include('layout.head')
    @stack('head')
</head>

<body class="flex h-dvh overflow-hidden font-sans bg-slate-100 text-slate-800 {{ request()->routeIs(['schedule.index', 'attendance.index', 'sparepart.index', 'sparepart.all', 'employee.index', 'salary.index']) ? 'ui-wide-layout' : '' }}" x-data="{ sidebarOpen: false, desktop: window.innerWidth >= 1024 }"
    x-init="$watch('sidebarOpen', open => $nextTick(() => { const target = open ? document.querySelector('#main-sidebar button') : document.querySelector('[aria-controls=main-sidebar]'); if (target?.getClientRects().length) target.focus(); }))"
    @resize.window="desktop = window.innerWidth >= 1024; if (desktop) sidebarOpen = false" @keydown.escape.window="sidebarOpen = false">

    <a href="#main-content" class="ui-skip-link">Langsung ke konten</a>
    <p id="ui-live-status" class="sr-only" role="status" aria-live="polite" aria-atomic="true"></p>
    <!-- SIDEBAR NAVIGATION -->
    @if (!request()->routeIs(['login', 'register', 'password.*']))
        @include('layout.aside')
    @endif

    <!-- MAIN WRAPPER -->
    <div :inert="sidebarOpen && !desktop" class="flex flex-col flex-1 w-full min-h-0 min-w-0 overflow-hidden">

        <!-- TOP NAVBAR -->
        @if (!request()->routeIs(['login', 'register', 'password.*']))
            @include('layout.navbar')
        @endif

        <!-- TOAST NOTIFICATION CENTER -->
        @include('layout.notif')

        <!-- MAIN CONTENT AREA -->
        <main id="main-content" tabindex="-1"
            class="flex-1 w-full min-h-0 min-w-0 {{ request()->routeIs(['login', 'register', 'password.*']) ? 'overflow-y-auto overflow-x-hidden bg-slate-950 p-0' : 'overflow-y-auto overflow-x-hidden p-4 sm:p-6 lg:p-8' }}">
            @if (request()->routeIs(['login', 'register', 'password.*']))
                @yield('content')
            @else
                <div class="ui-workspace mx-auto w-full max-w-screen-2xl">
                    @if ($errors->any())
                        <section id="validation-summary" role="alert" tabindex="-1"
                            class="p-4 mb-5 text-sm border rounded-2xl border-rose-200 bg-rose-50 text-rose-900">
                            <h2 class="font-bold">Data belum tersimpan</h2>
                            <p class="mt-1">Cek bagian berikut, lalu coba simpan lagi. Pilih ulang lampiran jika diperlukan.</p>
                            <ul class="pl-5 mt-2 list-disc">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </section>
                        <script type="application/json" id="validation-old-input">{!! json_encode(collect(session()->getOldInput())->reject(fn ($value, $key) => preg_match('/password|token|secret|signature/i', $key))->all(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
                        <script type="application/json" id="validation-errors">{!! json_encode($errors->messages(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
                    @endif
                    @yield('content')
                </div>
            @endif
        </main>
    </div>

    @include('layout.script')
    @include('layout.session')
    @stack('scripts')
</body>

</html>
