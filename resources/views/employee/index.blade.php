@extends('layout.master')

@section('title', 'Manajemen Karyawan')

@section('content')
    <div class="w-full pb-6 space-y-5">

        {{-- ========================================================= --}}
        {{-- ALERT MESSAGES --}}
        {{-- ========================================================= --}}
        @if (session('success'))
            <div
                class="flex items-start justify-between gap-3 px-4 py-3 text-sm border rounded-2xl border-emerald-200 bg-emerald-50 text-emerald-800">
                <div class="flex items-start gap-2.5">
                    <i class="fa-solid fa-circle-check mt-0.5 text-emerald-600"></i>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()"
                    class="cursor-pointer shrink-0 text-emerald-600 hover:text-emerald-900" aria-label="Tutup notifikasi">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        @endif

        @if (session('error'))
            <div
                class="flex items-start justify-between gap-3 px-4 py-3 text-sm border rounded-2xl border-rose-200 bg-rose-50 text-rose-800">
                <div class="flex items-start gap-2.5">
                    <i class="fa-solid fa-triangle-exclamation mt-0.5 text-rose-600"></i>
                    <span class="font-medium">{{ session('error') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()"
                    class="cursor-pointer shrink-0 text-rose-600 hover:text-rose-900" aria-label="Tutup notifikasi">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        @endif

        {{-- ========================================================= --}}
        {{-- PAGE HEADER --}}
        {{-- ========================================================= --}}
        <div class="p-5 bg-white border shadow-xs rounded-3xl border-slate-200 sm:p-7">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">

                <div class="min-w-0">
                    <div
                        class="mb-3 inline-flex items-center gap-2 rounded-lg bg-blue-50 px-2.5 py-1 text-[11px] font-bold text-blue-700">
                        <i class="fa-solid fa-users"></i>
                        Human Resources
                    </div>

                    <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
                        Manajemen Karyawan
                    </h1>

                    <p class="mt-1.5 text-sm text-slate-500">
                        Kelola informasi, penempatan, dan kontrak karyawan.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">

                    <button type="button" onclick="openImportModal()"
                        class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-xs font-bold text-emerald-700 transition-colors hover:border-emerald-600 hover:bg-emerald-600 hover:text-white">
                        <i class="fa-solid fa-file-import"></i>
                        Impor Excel
                    </button>

                    <a id="btn-export-excel" href="{{ route('employee.export', request()->query()) }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-100">
                        <i class="fa-solid fa-file-excel"></i>
                        Ekspor Excel
                    </a>

                    <a href="{{ route('employee.create') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition-colors hover:bg-blue-700">
                        <i class="fa-solid fa-plus"></i>
                        Tambah Karyawan
                    </a>

                </div>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- MONTHLY PKWT OVERVIEW --}}
        {{-- ========================================================= --}}
        <section class="overflow-hidden bg-white border shadow-xs rounded-3xl border-amber-200">

            <div
                class="flex flex-col gap-4 px-5 py-4 border-b border-amber-100 bg-amber-50/70 sm:flex-row sm:items-center sm:justify-between sm:px-6">

                <div class="flex items-center min-w-0 gap-3">
                    <div class="flex items-center justify-center w-10 h-10 text-white shrink-0 rounded-xl bg-amber-500">
                        <i class="fa-solid fa-file-contract"></i>
                    </div>

                    <div class="min-w-0">
                        <h2 class="text-sm font-extrabold text-slate-900">
                            PKWT Bulan {{ $monthsList[$bannerMonth] }}
                        </h2>

                        <p class="mt-0.5 text-xs text-slate-500">
                            Daftar karyawan berdasarkan bulan tanggal PKWT.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">

                    <form action="{{ route('employee.index') }}" method="GET" class="flex items-center">

                        @foreach (request()->except('banner_month', 'page') as $key => $value)
                            @if (is_array($value))
                                @foreach ($value as $v)
                                    @if (!is_array($v))
                                        <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                    @endif
                                @endforeach
                            @else
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach

                        <div class="relative">
                            <i
                                class="absolute text-xs -translate-y-1/2 fa-regular fa-calendar left-3 top-1/2 text-amber-600"></i>

                            <select name="banner_month" onchange="this.form.submit()" aria-label="Pilih bulan PKWT"
                                class="min-w-36 cursor-pointer appearance-none rounded-xl border border-amber-200 bg-white py-2.5 pl-9 pr-9 text-xs font-bold text-slate-700 outline-none transition-colors focus:border-amber-500 focus:ring-2 focus:ring-amber-500/10">

                                @foreach ($monthsList as $mNum => $mName)
                                    <option value="{{ $mNum }}" {{ $bannerMonth == $mNum ? 'selected' : '' }}>
                                        {{ $mName }}
                                    </option>
                                @endforeach
                            </select>

                            <i
                                class="fa-solid fa-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-400"></i>
                        </div>
                    </form>

                    <span
                        class="inline-flex items-center gap-1.5 rounded-xl border border-amber-200 bg-amber-100 px-3 py-2.5 text-xs font-extrabold text-amber-800">
                        <i class="fa-solid fa-users text-[10px]"></i>
                        {{ \App\Support\UiText::number($upcomingEmployees->count()) }}
                        <span class="hidden sm:inline">Karyawan</span>
                    </span>
                </div>
            </div>

            <div class="p-4 sm:p-5">
                @if ($upcomingEmployees->isNotEmpty())

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">

                        @foreach ($upcomingEmployees as $emp)
                            <button type="button" onclick="showEmployeeDetail({{ $emp->id }})"
                                class="w-full p-4 text-left transition-all bg-white border cursor-pointer group rounded-2xl border-slate-200 hover:border-amber-300 hover:bg-amber-50/40 hover:shadow-sm">

                                <div class="flex items-start justify-between gap-3">

                                    <div class="flex-1 min-w-0">
                                        <p
                                            class="text-xs font-extrabold truncate text-slate-900 group-hover:text-amber-800">
                                            {{ $emp->name }}
                                        </p>

                                        <p class="mt-1 truncate text-[11px] font-medium text-slate-500">
                                            {{ $emp->site->machine_name ?? 'Lokasi belum ditentukan' }}
                                            &middot;
                                            {{ $emp->position ?? 'Karyawan' }}
                                        </p>
                                    </div>

                                    <span
                                        class="shrink-0 rounded-lg bg-amber-100 px-2 py-1 text-[11px] font-extrabold text-amber-800">
                                        {{ \Carbon\Carbon::parse($emp->pkwt_date)->translatedFormat('d M Y') }}
                                    </span>
                                </div>

                                @if ($emp->pkwt_number)
                                    <div class="mt-3 border-t border-slate-100 pt-2.5">
                                        <p class="truncate text-[10px] font-semibold text-slate-400"
                                            title="{{ $emp->pkwt_number }}">
                                            <i class="mr-1 fa-regular fa-file-lines"></i>
                                            {{ $emp->pkwt_number }}
                                        </p>
                                    </div>
                                @endif

                            </button>
                        @endforeach

                    </div>
                @else
                    <div
                        class="flex flex-col items-center px-5 py-8 text-center border border-dashed rounded-2xl border-slate-200 bg-slate-50/50">

                        <div class="flex items-center justify-center bg-white h-11 w-11 rounded-xl text-slate-400">
                            <i class="text-lg fa-regular fa-calendar-xmark"></i>
                        </div>

                        <p class="mt-3 text-sm font-bold text-slate-700">
                            Tidak ada PKWT pada bulan ini
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            Tidak ada data tanggal PKWT untuk bulan
                            {{ $monthsList[$bannerMonth] }}.
                        </p>

                    </div>
                @endif
            </div>

        </section>

        {{-- ========================================================= --}}
        {{-- UNIFIED FILTER + EMPLOYEE TABLE --}}
        {{-- ========================================================= --}}
        <section class="overflow-hidden bg-white border shadow-xs rounded-3xl border-slate-200">

            {{-- SECTION HEADER --}}
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 sm:px-6">
                <div>
                    <h2 class="text-sm font-extrabold text-slate-900">
                        Daftar Karyawan
                    </h2>
                    <p class="mt-0.5 text-xs text-slate-400">
                        Cari dan filter data karyawan.
                    </p>
                </div>

                <div class="items-center hidden gap-2 text-xs font-medium text-slate-400 sm:flex">
                    <i class="fa-solid fa-database"></i>
                    Data Karyawan
                </div>
            </div>

            <form id="filter-form" action="{{ route('employee.index') }}" method="GET" onsubmit="return false;">

                <div class="p-4 space-y-4 border-b border-slate-100 sm:p-5">

                    {{-- MAIN FILTER TOOLBAR --}}
                    <div class="flex flex-col gap-3 md:flex-row md:items-center">

                        {{-- SEARCH --}}
                        <div class="relative flex-1 min-w-0">

                            <i
                                class="absolute text-sm -translate-y-1/2 pointer-events-none fa-solid fa-magnifying-glass left-4 top-1/2 text-slate-400"></i>

                            <input type="search" name="search" id="search" autocomplete="off"
                                value="{{ request('search') }}" placeholder="Cari nama, NIK, jabatan, nomor PKWT..."
                                class="w-full py-3 pr-10 text-sm font-medium transition-all border outline-none rounded-xl border-slate-200 bg-slate-50 pl-11 text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10">

                            <button type="button" id="clearSearchBtn" onclick="clearSearchInput()"
                                aria-label="Hapus pencarian"
                                class="absolute hidden -translate-y-1/2 cursor-pointer right-3 top-1/2 text-slate-400 hover:text-slate-700">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        {{-- TOOLBAR ACTIONS --}}
                        <div class="flex items-center gap-2">

                            <button type="button" id="btn-toggle-filter" aria-expanded="false"
                                aria-controls="employee-filter-panel"
                                class="inline-flex items-center justify-center flex-1 gap-2 px-4 py-3 text-xs font-bold transition-colors bg-white border cursor-pointer rounded-xl border-slate-200 text-slate-700 hover:bg-slate-50 md:flex-none">

                                <i class="fa-solid fa-sliders text-slate-500"></i>
                                <span>Filter</span>

                                <span id="filter-count"
                                    class="hidden rounded-md bg-blue-600 px-1.5 py-0.5 text-[10px] font-extrabold text-white">
                                    0
                                </span>

                                <i id="filter-chevron"
                                    class="fa-solid fa-chevron-down text-[10px] text-slate-400 transition-transform"></i>
                            </button>

                            <button type="button" id="btn-reset-filter" title="Atur ulang filter"
                                class="inline-flex items-center justify-center gap-2 px-4 py-3 text-xs font-bold transition-colors bg-white border cursor-pointer rounded-xl border-slate-200 text-slate-600 hover:bg-slate-50">

                                <i class="fa-solid fa-rotate-left"></i>
                                <span class="hidden sm:inline">Reset</span>
                            </button>
                        </div>
                    </div>

                    {{-- ACTIVE FILTER CHIPS --}}
                    <div id="active-filter-container" class="flex-wrap items-center hidden gap-2">

                        <span class="text-[11px] font-semibold text-slate-400">
                            Aktif:
                        </span>

                        <div id="active-filter-list" class="flex flex-wrap items-center gap-1.5"></div>
                    </div>

                    {{-- ADVANCED FILTER PANEL --}}
                    <div id="employee-filter-panel" class="hidden pt-4 border-t border-slate-100">

                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-xs font-extrabold text-slate-800">
                                    Filter Lanjutan
                                </h3>
                                <p class="mt-1 text-[11px] text-slate-400">
                                    Pilih satu atau beberapa kategori.
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">

                            {{-- BULAN BERGABUNG --}}
                            <div class="space-y-1.5">
                                <label for="filter_join_month" class="block text-[11px] font-bold text-slate-600">
                                    Bulan Bergabung
                                </label>

                                <select name="join_month" id="filter_join_month"
                                    class="block w-full cursor-pointer rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs font-semibold text-slate-700 outline-none focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/10">

                                    <option value="">Semua Bulan</option>

                                    @for ($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}"
                                            {{ request('join_month') == $m ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create(2000, $m, 1)->translatedFormat('F') }}
                                        </option>
                                    @endfor
                                </select>
                            </div>

                            {{-- TANGGAL BERGABUNG --}}
                            <div class="space-y-1.5">
                                <label for="filter_join_day" class="block text-[11px] font-bold text-slate-600">
                                    Tanggal Bergabung
                                </label>

                                <select name="join_day" id="filter_join_day"
                                    class="block w-full cursor-pointer rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs font-semibold text-slate-700 outline-none focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/10">

                                    <option value="">Semua Tanggal</option>

                                    @for ($d = 1; $d <= 31; $d++)
                                        <option value="{{ $d }}"
                                            {{ request('join_day') == $d ? 'selected' : '' }}>
                                            Tanggal {{ $d }}
                                        </option>
                                    @endfor
                                </select>
                            </div>

                            {{-- STATUS --}}
                            <div class="space-y-1.5">
                                <label for="filter_status" class="block text-[11px] font-bold text-slate-600">
                                    Status Kepegawaian
                                </label>

                                <select name="status" id="filter_status"
                                    class="block w-full cursor-pointer rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs font-semibold text-slate-700 outline-none focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/10">

                                    <option value="">Semua Status</option>

                                    @foreach ([
            'Permanent' => 'Tetap',
            'Contract' => 'Kontrak',
            'Probation' => 'Masa Percobaan',
            'Daily' => 'Harian',
            'Resigned' => 'Berhenti Bekerja',
        ] as $value => $label)
                                        <option value="{{ $value }}"
                                            {{ request('status') == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- SITE --}}
                            <div class="space-y-1.5">
                                <label for="filter_site" class="block text-[11px] font-bold text-slate-600">
                                    Lokasi Mesin
                                </label>

                                <select name="site_id" id="filter_site"
                                    class="block w-full cursor-pointer rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs font-semibold text-slate-700 outline-none focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/10">

                                    <option value="">Semua Lokasi</option>

                                    @foreach ($sites as $site)
                                        <option value="{{ $site->id }}"
                                            {{ request('site_id') == $site->id ? 'selected' : '' }}>
                                            {{ $site->machine_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- CABANG --}}
                            <div class="space-y-1.5">
                                <label for="filter_branch" class="block text-[11px] font-bold text-slate-600">
                                    Cabang
                                </label>

                                <select name="branch_id" id="filter_branch"
                                    class="block w-full cursor-pointer rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs font-semibold text-slate-700 outline-none focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/10">

                                    <option value="">Semua Cabang</option>

                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}"
                                            {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->branch_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- MCU --}}
                            <div class="space-y-1.5">
                                <label for="filter_mcu" class="block text-[11px] font-bold text-slate-600">
                                    Status MCU
                                </label>

                                <select name="mcu" id="filter_mcu"
                                    class="block w-full cursor-pointer rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs font-semibold text-slate-700 outline-none focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/10">

                                    <option value="">Semua Status</option>
                                    <option value="yes" {{ request('mcu') == 'yes' ? 'selected' : '' }}>
                                        Ya
                                    </option>
                                    <option value="no" {{ request('mcu') == 'no' ? 'selected' : '' }}>
                                        Tidak
                                    </option>
                                </select>
                            </div>

                            {{-- TLD --}}
                            <div class="space-y-1.5">
                                <label for="filter_tld" class="block text-[11px] font-bold text-slate-600">
                                    Kartu TLD
                                </label>

                                <select name="tld" id="filter_tld"
                                    class="block w-full cursor-pointer rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs font-semibold text-slate-700 outline-none focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/10">

                                    <option value="">Semua Status</option>
                                    <option value="yes" {{ request('tld') == 'yes' ? 'selected' : '' }}>
                                        Ya
                                    </option>
                                    <option value="no" {{ request('tld') == 'no' ? 'selected' : '' }}>
                                        Tidak
                                    </option>
                                </select>
                            </div>

                            {{-- BANK --}}
                            <div class="space-y-1.5">
                                <label for="filter_bank" class="block text-[11px] font-bold text-slate-600">
                                    Nama Bank
                                </label>

                                <select name="bank_name" id="filter_bank"
                                    class="block w-full cursor-pointer rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs font-semibold text-slate-700 outline-none focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/10">

                                    <option value="">Semua Bank</option>

                                    @php
                                        $bankList = [
                                            'BCA',
                                            'Bank Mandiri',
                                            'BRI',
                                            'BNI',
                                            'BSI',
                                            'CIMB Niaga',
                                            'Bank Permata',
                                            'Bank Danamon',
                                            'BTN',
                                            'Maybank',
                                            'Panin Bank',
                                            'OCBC NISP',
                                            'Bank BJB',
                                            'Bank DKI',
                                            'Bank Jatim',
                                            'Bank Jateng',
                                            'Seabank',
                                            'Bank Jago',
                                            'Bank Neo Commerce',
                                        ];
                                    @endphp

                                    @foreach ($bankList as $bank)
                                        <option value="{{ $bank }}"
                                            {{ request('bank_name') == $bank ? 'selected' : '' }}>
                                            {{ $bank }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                        </div>
                    </div>

                </div>
            </form>

            {{-- FILTER ERROR --}}
            <div id="filter-error" role="alert"
                class="hidden px-5 py-3 text-xs font-semibold border-b border-rose-100 bg-rose-50 text-rose-700">
            </div>

            {{-- AJAX TABLE --}}
            <div id="table-container" class="transition-opacity duration-200">
                @include('employee.table', ['employees' => $employees])
            </div>

        </section>

    </div>

    {{-- ========================================================= --}}
    {{-- IMPORT EXCEL MODAL --}}
    {{-- ========================================================= --}}
    <div id="importModal" onclick="if(event.target === this) closeImportModal()" role="dialog" aria-modal="true"
        aria-label="Impor karyawan dari Excel"
        class="fixed inset-0 z-50 items-center justify-center hidden p-4 bg-slate-900/60 backdrop-blur-xs">

        <div class="w-full max-w-md mx-auto overflow-hidden bg-white border shadow-2xl rounded-3xl border-slate-100">

            <div class="flex items-center justify-between px-6 py-5 bg-slate-900">

                <div>
                    <h3 class="flex items-center gap-2 text-sm font-extrabold text-white">
                        <i class="fa-solid fa-file-excel text-emerald-400"></i>
                        Impor Excel
                    </h3>
                    <p class="mt-1 text-xs text-slate-400">
                        Tambahkan atau perbarui data karyawan.
                    </p>
                </div>

                <button type="button" onclick="closeImportModal()" aria-label="Tutup modal import"
                    class="flex items-center justify-center w-8 h-8 rounded-lg cursor-pointer text-slate-400 hover:bg-slate-800 hover:text-white">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="{{ route('employee.import') }}" method="POST" enctype="multipart/form-data"
                class="p-6 space-y-5">
                @csrf

                <div class="rounded-xl border border-blue-100 bg-blue-50 p-3.5">
                    <p class="text-xs font-bold text-blue-800">
                        <i class="mr-1 fa-solid fa-circle-info"></i>
                        Partial Import
                    </p>
                    <p class="mt-1 text-xs leading-relaxed text-blue-700">
                        Hanya kolom yang tersedia dan berisi nilai pada Excel
                        yang akan diperbarui. Gunakan nama atau identitas
                        karyawan untuk mencocokkan data.
                    </p>
                </div>

                <div class="space-y-2">
                    <label for="employee-import-file" class="block text-xs font-bold text-slate-700">
                        File Excel
                    </label>

                    <input type="file" id="employee-import-file" name="file" required accept=".xlsx,.xls,.csv"
                        class="block w-full text-xs bg-white border rounded-xl border-slate-200 text-slate-500 file:mr-3 file:cursor-pointer file:border-0 file:bg-slate-100 file:px-4 file:py-3 file:text-xs file:font-bold file:text-slate-700 hover:file:bg-slate-200">

                    <p class="text-[11px] text-slate-400">
                        Format didukung: XLSX, XLS, CSV. Maksimal 5 MB.
                    </p>
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeImportModal()"
                        class="cursor-pointer rounded-xl px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100">
                        Batal
                    </button>

                    <button type="submit"
                        class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white transition-colors hover:bg-emerald-700">
                        <i class="fa-solid fa-upload"></i>
                        Unggah & Impor
                    </button>
                </div>

            </form>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- EMPLOYEE DETAIL MODAL --}}
    {{-- ========================================================= --}}
    <div id="employeeDetailModal" onclick="if(event.target === this) closeEmployeeModal()" role="dialog"
        aria-modal="true" aria-label="Rincian karyawan"
        class="fixed inset-0 z-50 items-center justify-center hidden p-3 bg-slate-900/60 backdrop-blur-xs sm:p-4">

        <div
            class="mx-auto flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-2xl">

            {{-- MODAL HEADER --}}
            <div class="flex items-center justify-between px-5 py-4 shrink-0 bg-slate-900 sm:px-6">

                <h3 class="flex items-center gap-2 text-sm font-extrabold text-white">
                    <i class="text-blue-400 fa-solid fa-id-card"></i>
                    Rincian Karyawan
                </h3>

                <button type="button" onclick="closeEmployeeModal()" aria-label="Tutup rincian karyawan"
                    class="flex items-center justify-center w-8 h-8 rounded-lg cursor-pointer text-slate-400 hover:bg-slate-800 hover:text-white">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            {{-- MODAL BODY --}}
            <div class="flex-1 min-h-0 p-5 space-y-5 overflow-y-auto sm:p-6">

                {{-- EMPLOYEE IDENTITY --}}
                <div class="flex items-center gap-3 pb-4 border-b border-slate-100">

                    <div class="flex items-center justify-center w-12 h-12 text-blue-600 shrink-0 rounded-2xl bg-blue-50">
                        <i class="text-lg fa-solid fa-user"></i>
                    </div>

                    <div class="min-w-0">
                        <h3 id="detail_name" class="text-base font-extrabold text-slate-900 sm:text-lg">
                            -
                        </h3>

                        <p id="detail_position" class="mt-0.5 text-xs font-medium text-slate-500">
                            -
                        </p>
                    </div>
                </div>

                {{-- IDENTITY GRID --}}
                <div class="grid grid-cols-2 gap-3 md:grid-cols-3">

                    <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-3.5">
                        <span class="block text-[10px] font-bold uppercase text-slate-400">NIK</span>
                        <strong id="detail_nik" class="block mt-1 font-mono text-xs break-all text-slate-800">-</strong>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-3.5">
                        <span class="block text-[10px] font-bold uppercase text-slate-400">Email</span>
                        <strong id="detail_email" class="block mt-1 text-xs break-all text-slate-800">-</strong>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-3.5">
                        <span class="block text-[10px] font-bold uppercase text-slate-400">Telepon</span>
                        <strong id="detail_phone" class="block mt-1 text-xs break-all text-slate-800">-</strong>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-3.5">
                        <span class="block text-[10px] font-bold uppercase text-slate-400">Lokasi Mesin</span>
                        <strong id="detail_site" class="block mt-1 text-xs text-slate-800">-</strong>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-3.5">
                        <span class="block text-[10px] font-bold uppercase text-slate-400">Cabang</span>
                        <strong id="detail_branch" class="block mt-1 text-xs text-slate-800">-</strong>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-3.5">
                        <span class="block text-[10px] font-bold uppercase text-slate-400">Status</span>
                        <div id="detail_status_badge" class="mt-1.5">-</div>
                    </div>

                    {{-- SALARY --}}
                    <div class="col-span-2 rounded-xl border border-emerald-200 bg-emerald-50/50 p-3.5 md:col-span-1">

                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[10px] font-bold uppercase text-emerald-700">
                                Gaji Pokok
                            </span>

                            <button type="button" onclick="toggleSalaryVisibility()"
                                aria-label="Tampilkan atau sembunyikan gaji"
                                class="cursor-pointer text-emerald-700 hover:text-emerald-900">
                                <i id="salary_toggle_icon" class="text-xs fa-solid fa-eye-slash"></i>
                            </button>
                        </div>

                        <strong id="detail_basic_salary" class="block mt-1 text-sm font-extrabold text-emerald-700">
                            ••••••••
                        </strong>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-3.5">
                        <span class="block text-[10px] font-bold uppercase text-slate-400">Rekening Bank</span>
                        <strong id="detail_bank" class="block mt-1 text-xs break-words text-slate-800">-</strong>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-3.5">
                        <span class="block text-[10px] font-bold uppercase text-slate-400">MCU</span>
                        <div id="detail_mcu_badge" class="mt-1">-</div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-3.5">
                        <span class="block text-[10px] font-bold uppercase text-slate-400">Kartu TLD</span>
                        <div id="detail_tld_badge" class="mt-1">-</div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-3.5">
                        <span class="block text-[10px] font-bold uppercase text-slate-400">Masa Kerja</span>
                        <strong id="detail_tenure" class="block mt-1 text-xs font-bold text-blue-600">-</strong>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-3.5">
                        <span class="block text-[10px] font-bold uppercase text-slate-400">Tanggal Bergabung</span>
                        <strong id="detail_join_date" class="block mt-1 text-xs text-slate-800">-</strong>
                    </div>

                    {{-- PKWT --}}
                    <div class="rounded-xl border border-blue-100 bg-blue-50/40 p-3.5">
                        <span class="block text-[10px] font-bold uppercase text-blue-600">Tanggal PKWT</span>
                        <strong id="detail_pkwt_date" class="block mt-1 text-xs text-slate-800">-</strong>
                    </div>

                    <div class="col-span-2 rounded-xl border border-blue-100 bg-blue-50/40 p-3.5 md:col-span-1">
                        <span class="block text-[10px] font-bold uppercase text-blue-600">Nomor PKWT</span>
                        <strong id="detail_pkwt_number" class="block mt-1 text-xs break-all text-slate-800">-</strong>
                    </div>

                    <div class="min-w-0 rounded-xl border border-slate-200 bg-slate-50/50 p-3.5"><span class="block text-[10px] font-bold uppercase text-slate-400">Tanggal Mulai Kontrak</span><strong id="detail_contract_start" class="block mt-1 text-xs break-words whitespace-pre-line text-slate-800">-</strong></div>
                    <div class="min-w-0 rounded-xl border border-slate-200 bg-slate-50/50 p-3.5"><span class="block text-[10px] font-bold uppercase text-slate-400">Keaktifan Karyawan</span><strong id="detail_active" class="block mt-1 text-xs break-words whitespace-pre-line text-slate-800">-</strong></div>
                    <div class="min-w-0 rounded-xl border border-slate-200 bg-slate-50/50 p-3.5"><span class="block text-[10px] font-bold uppercase text-slate-400">Nama Bank</span><strong id="detail_bank_name" class="block mt-1 text-xs break-words whitespace-pre-line text-slate-800">-</strong></div>
                    <div class="min-w-0 rounded-xl border border-slate-200 bg-slate-50/50 p-3.5"><span class="block text-[10px] font-bold uppercase text-slate-400">Nomor Rekening</span><strong id="detail_account_number" class="block mt-1 text-xs break-words whitespace-pre-line text-slate-800">-</strong></div>
                    <div class="min-w-0 rounded-xl border border-slate-200 bg-slate-50/50 p-3.5"><span class="block text-[10px] font-bold uppercase text-slate-400">Alamat / Lokasi Penempatan</span><strong id="detail_site_location" class="block mt-1 text-xs break-words whitespace-pre-line text-slate-800">-</strong></div>
                    <div class="min-w-0 rounded-xl border border-slate-200 bg-slate-50/50 p-3.5"><span class="block text-[10px] font-bold uppercase text-slate-400">Keterangan Tambahan</span><strong id="detail_more_information" class="block mt-1 text-xs break-words whitespace-pre-line text-slate-800">-</strong></div>

                    {{-- RESIGN DATE --}}
                    <div id="detail_last_date_wrapper" class="rounded-xl border border-slate-200 bg-slate-50/50 p-3.5">
                        <span class="block text-[10px] font-bold uppercase text-slate-400">
                            Hari Terakhir Bekerja
                        </span>
                        <strong id="detail_last_date" class="block mt-1 text-xs text-slate-800">-</strong>
                    </div>

                </div>

                <section class="p-4 space-y-3 border border-emerald-200 rounded-2xl bg-emerald-50/40" aria-labelledby="employee-allowances-title">
                    <h4 id="employee-allowances-title" class="text-sm font-extrabold text-slate-800">Tunjangan Bulanan</h4>
                    <p class="text-xs text-slate-500">Rincian semua tunjangan. Total hanya menghitung tunjangan yang berlaku pada <span id="detail_allowance_period">bulan ini</span>.</p>
                    <div id="detail_employee_allowances" class="space-y-3"></div>
                    <div class="flex flex-wrap justify-between gap-2 pt-3 border-t border-emerald-200 text-sm"><span>Total Tunjangan Aktif</span><strong id="detail_allowance_total">••••••••</strong></div>
                    <div class="flex flex-wrap justify-between gap-2 text-sm text-emerald-800"><span>Gaji Pokok + Tunjangan / Bulan</span><strong id="detail_monthly_total">••••••••</strong></div>
                    <p class="text-xs text-slate-500">Sebelum lembur dan potongan. Gunakan tombol mata pada Gaji Pokok untuk menampilkan atau menyembunyikan seluruh nominal.</p>
                </section>

                {{-- SALARY HISTORY --}}
                <div class="pt-4 space-y-3 border-t border-slate-100">

                    <h4 class="text-xs font-extrabold text-slate-800">
                        Histori Perubahan Gaji
                    </h4>

                    <div class="overflow-x-auto border rounded-xl border-slate-200">

                        <table class="w-full min-w-[450px] text-left text-xs">
                            <thead class="bg-slate-50 text-[10px] font-bold uppercase text-slate-500">
                                <tr>
                                    <th class="px-3 py-3">Tanggal</th>
                                    <th class="px-3 py-3">Gaji Lama</th>
                                    <th class="px-3 py-3">Gaji Baru</th>
                                    <th class="px-3 py-3">Alasan / Status</th>
                                    <th class="px-3 py-3">Aksi</th>
                                </tr>
                            </thead>

                            <tbody id="detail_salary_history_body" class="divide-y divide-slate-100 text-slate-700">
                                <tr>
                                    <td colspan="4" class="px-3 py-6 text-center text-slate-400">
                                        Belum ada riwayat perubahan.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            {{-- MODAL FOOTER --}}
            <div class="flex flex-wrap justify-end gap-2 px-5 py-4 border-t shrink-0 border-slate-100 bg-slate-50 sm:px-6">
                @if(in_array(auth()->user()->role, ['superadmin','administration']))
                <a id="detail_contract_link" href="{{ route('contracts.index') }}" class="inline-flex items-center px-4 py-2.5 text-xs font-bold text-blue-700 bg-white border rounded-xl">Riwayat Kontrak</a>
                @endif
                <button type="button" onclick="closeEmployeeModal()"
                    class="cursor-pointer rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-100">
                    Tutup
                </button>
            </div>

        </div>
    </div>

    <script>
        let isTableSalaryVisible = false;
        let isSalaryVisible = false;
        let currentEmployeeData = null;

        // =========================================================
        // UNIFIED AJAX FILTER
        // =========================================================
        (function() {
            const form = document.getElementById('filter-form');
            const panel = document.getElementById('employee-filter-panel');
            const toggleButton = document.getElementById('btn-toggle-filter');
            const chevron = document.getElementById('filter-chevron');
            const table = document.getElementById('table-container');
            const search = document.getElementById('search');
            const clearSearch = document.getElementById('clearSearchBtn');
            const counter = document.getElementById('filter-count');
            const activeContainer = document.getElementById('active-filter-container');
            const activeList = document.getElementById('active-filter-list');
            const exportButton = document.getElementById('btn-export-excel');
            const errorContainer = document.getElementById('filter-error');
            const resetButton = document.getElementById('btn-reset-filter');

            if (!form || !panel || !table) return;

            const indexUrl = @json(route('employee.index'));
            const exportUrl = @json(route('employee.export'));

            const filterNames = [
                'join_month',
                'join_day',
                'status',
                'site_id',
                'branch_id',
                'mcu',
                'tld',
                'bank_name'
            ];

            let debounceTimer = null;
            let activeRequest = null;
            let requestSequence = 0;

            function getParams() {
                const params = new URLSearchParams();

                new FormData(form).forEach((value, key) => {
                    const text = String(value).trim();

                    if (text !== '') {
                        params.set(key, text);
                    }
                });

                const current = new URL(window.location.href);
                const bannerMonth = current.searchParams.get('banner_month');

                if (bannerMonth) {
                    params.set('banner_month', bannerMonth);
                }

                return params;
            }

            function buildUrl(base, params) {
                const query = params.toString();
                return query ? `${base}?${query}` : base;
            }

            function setPanelOpen(open) {
                panel.classList.toggle('hidden', !open);

                if (chevron) {
                    chevron.classList.toggle('rotate-180', open);
                }

                toggleButton?.setAttribute('aria-expanded', String(open));
            }

            function updateFilterUI() {
                const params = getParams();

                const activeFilters = filterNames.filter(name => {
                    return params.has(name);
                });

                const count = activeFilters.length;

                clearSearch?.classList.toggle(
                    'hidden',
                    !search.value.trim()
                );

                if (counter) {
                    counter.textContent = count;
                    counter.classList.toggle('hidden', count === 0);
                }

                if (activeList) {
                    activeList.replaceChildren();

                    activeFilters.forEach(name => {
                        const select = form.elements.namedItem(name);
                        if (!select) return;

                        const label = form.querySelector(
                            `label[for="${select.id}"]`
                        );

                        const optionText =
                            select.selectedOptions?.[0]?.textContent.trim();

                        const chip = document.createElement('button');
                        chip.type = 'button';

                        chip.className =
                            'inline-flex cursor-pointer items-center gap-2 ' +
                            'rounded-lg border border-blue-100 bg-blue-50 ' +
                            'px-2.5 py-1.5 text-[11px] font-semibold ' +
                            'text-blue-700 transition-colors hover:bg-blue-100';

                        const labelSpan = document.createElement('span');

                        labelSpan.textContent =
                            `${label?.textContent.trim() || name}: ${optionText || select.value}`;

                        const closeIcon = document.createElement('i');
                        closeIcon.className =
                            'fa-solid fa-xmark text-[10px]';

                        chip.append(labelSpan, closeIcon);

                        chip.addEventListener('click', () => {
                            select.value = '';
                            clearTimeout(debounceTimer);
                            updateFilterUI();
                            submitFilterForm();
                        });

                        activeList.appendChild(chip);
                    });
                }

                if (activeContainer) {
                    activeContainer.classList.toggle('hidden', count === 0);
                    activeContainer.classList.toggle('flex', count > 0);
                }

                if (exportButton) {
                    exportButton.href = buildUrl(exportUrl, params);
                }
            }

            function restoreFormFromUrl() {
                const params = new URLSearchParams(window.location.search);

                form.reset();

                ['search', ...filterNames].forEach(name => {
                    const field = form.elements.namedItem(name);

                    if (field && params.has(name)) {
                        field.value = params.get(name);
                    }
                });

                updateFilterUI();

                setPanelOpen(
                    filterNames.some(name => params.has(name))
                );
            }

            async function submitFilterForm(
                targetUrl = null,
                updateHistory = true
            ) {
                const params = getParams();
                const url = targetUrl || buildUrl(indexUrl, params);

                if (activeRequest) {
                    activeRequest.abort();
                }

                const controller = new AbortController();
                activeRequest = controller;

                const sequence = ++requestSequence;

                table.style.opacity = '0.4';
                table.setAttribute('aria-busy', 'true');

                errorContainer?.classList.add('hidden');

                updateFilterUI();

                try {
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html'
                        },
                        signal: controller.signal
                    });

                    if (!response.ok) {
                        throw new Error('Gagal memuat data karyawan.');
                    }

                    const html = await response.text();

                    if (sequence !== requestSequence) return;

                    table.innerHTML = html;

                    if (updateHistory) {
                        window.history.pushState({}, '', url);
                    }

                    updateTableSalaryUI();

                } catch (error) {
                    if (error.name !== 'AbortError') {
                        console.error('Employee Filter Error:', error);

                        if (errorContainer) {
                            errorContainer.textContent =
                                'Data gagal dimuat. Periksa koneksi dan coba lagi.';
                            errorContainer.classList.remove('hidden');
                        }
                    }

                } finally {
                    if (sequence === requestSequence) {
                        table.style.opacity = '1';
                        table.removeAttribute('aria-busy');
                        activeRequest = null;
                    }
                }
            }

            toggleButton?.addEventListener('click', () => {
                const isOpen = !panel.classList.contains('hidden');
                setPanelOpen(!isOpen);
            });

            form.addEventListener('input', event => {
                if (event.target !== search) return;

                clearTimeout(debounceTimer);
                updateFilterUI();

                debounceTimer = setTimeout(() => {
                    submitFilterForm();
                }, 350);
            });

            form.addEventListener('change', event => {
                if (!event.target.matches('select')) return;

                clearTimeout(debounceTimer);
                submitFilterForm();
            });

            resetButton?.addEventListener('click', () => {
                clearTimeout(debounceTimer);
                form.reset();

                updateFilterUI();
                submitFilterForm();
            });

            document.addEventListener('click', event => {
                const link = event.target.closest(
                    '#table-container .pagination a, ' +
                    '#table-container a[rel="prev"], ' +
                    '#table-container a[rel="next"]'
                );

                if (!link) return;

                event.preventDefault();
                clearTimeout(debounceTimer);

                submitFilterForm(link.href);
            });

            window.addEventListener('popstate', () => {
                clearTimeout(debounceTimer);
                restoreFormFromUrl();
                submitFilterForm(window.location.href, false);
            });

            restoreFormFromUrl();

            // Expose only to existing clear-search function if needed.
            window.refreshEmployeeFilter = function() {
                clearTimeout(debounceTimer);
                updateFilterUI();
                submitFilterForm();
            };
        })();

        // =========================================================
        // CLEAR SEARCH
        // =========================================================
        function clearSearchInput() {
            const search = document.getElementById('search');
            if (!search) return;

            search.value = '';

            search.dispatchEvent(
                new Event('input', {
                    bubbles: true
                })
            );

            search.focus();
        }

        // =========================================================
        // TABLE SALARY PRIVACY
        // =========================================================
        function toggleTableSalaryVisibility() {
            isTableSalaryVisible = !isTableSalaryVisible;
            updateTableSalaryUI();
        }

        function updateTableSalaryUI() {
            const icon = document.getElementById('table_salary_toggle_icon');

            if (icon) {
                icon.className = isTableSalaryVisible ?
                    'text-xs fa-solid fa-eye' :
                    'text-xs fa-solid fa-eye-slash';
            }

            document.querySelectorAll('.table-salary-text')
                .forEach(element => {
                    const actualSalary = element.getAttribute('data-salary');

                    element.textContent = isTableSalaryVisible ?
                        actualSalary :
                        '••••••••';
                });
        }

        // =========================================================
        // IMPORT MODAL
        // =========================================================
        function openImportModal() {
            const modal = document.getElementById('importModal');

            if (!modal) return;

            modal.classList.remove('hidden');
            modal.classList.add('flex');

            document.body.classList.add('overflow-hidden');
        }

        function closeImportModal() {
            const modal = document.getElementById('importModal');

            if (!modal) return;

            modal.classList.add('hidden');
            modal.classList.remove('flex');

            document.body.classList.remove('overflow-hidden');
        }

        // =========================================================
        // DETAIL SALARY PRIVACY
        // =========================================================
        function toggleSalaryVisibility() {
            isSalaryVisible = !isSalaryVisible;

            const icon = document.getElementById('salary_toggle_icon');

            if (icon) {
                icon.className = isSalaryVisible ?
                    'fa-solid fa-eye text-xs' :
                    'fa-solid fa-eye-slash text-xs';
            }

            renderSalaryInfo();
        }

        function renderSalaryInfo() {
            if (!currentEmployeeData) return;

            const salaryElement = document.getElementById(
                'detail_basic_salary'
            );

            if (salaryElement) {
                salaryElement.textContent = isSalaryVisible ?
                    (currentEmployeeData.basic_salary_formatted || 'Rp 0') :
                    '••••••••';
            }

            for (const [id, value] of [['detail_allowance_total', currentEmployeeData.allowance_total_formatted], ['detail_monthly_total', currentEmployeeData.monthly_total_formatted]]) {
                const element = document.getElementById(id);
                if (element) element.textContent = isSalaryVisible ? (value || 'Rp 0') : '••••••••';
            }
            const allowanceHolder = document.getElementById('detail_employee_allowances');
            if (allowanceHolder) {
                allowanceHolder.replaceChildren();
                const allowances = currentEmployeeData.allowance_details || [];
                if (!allowances.length) allowanceHolder.textContent = 'Belum ada tunjangan bulanan.';
                for (const allowance of allowances) {
                    const card = document.createElement('article');
                    card.className = 'min-w-0 p-3 space-y-2 bg-white border rounded-xl text-xs break-words';
                    const title = document.createElement('h5');
                    title.className = 'font-bold text-slate-800';
                    title.textContent = allowance.name;
                    const amount = document.createElement('p');
                    amount.className = 'font-semibold text-emerald-700';
                    amount.textContent = isSalaryVisible ? allowance.amount_formatted + ' / bulan' : '•••••••• / bulan';
                    const period = document.createElement('p');
                    period.textContent = allowance.start_month + ' – ' + allowance.end_month + ' · ' + allowance.status;
                    const notes = document.createElement('p');
                    notes.className = 'whitespace-pre-line';
                    notes.textContent = allowance.notes || 'Tanpa keterangan tambahan.';
                    card.append(title, amount, period, notes);
                    if (allowance.certificate_url) {
                        const link = document.createElement('a');
                        link.href = allowance.certificate_url;
                        link.className = 'inline-block py-2 font-semibold text-blue-700';
                        link.textContent = 'Unduh sertifikat';
                        card.append(link);
                    }
                    allowanceHolder.append(card);
                }
            }

            const historyBody = document.getElementById(
                'detail_salary_history_body'
            );

            if (!historyBody) return;

            historyBody.replaceChildren();

            const histories = currentEmployeeData.salary_histories || [];

            if (histories.length === 0) {
                const row = document.createElement('tr');
                const cell = document.createElement('td');

                cell.colSpan = 5;
                cell.className =
                    'px-3 py-6 text-center text-slate-400';

                cell.textContent = 'Belum ada riwayat perubahan.';

                row.appendChild(cell);
                historyBody.appendChild(row);
                return;
            }

            const money = value => {
                return 'Rp ' + new Intl.NumberFormat('id-ID')
                    .format(Number(value) || 0);
            };

            histories.forEach(history => {
                const row = document.createElement('tr');

                const date = document.createElement('td');
                date.className = 'px-3 py-3';
                date.textContent = history.created_at ?
                    new Date(history.created_at).toLocaleDateString('id-ID') :
                    '-';

                const oldSalary = document.createElement('td');
                oldSalary.className =
                    'px-3 py-3 font-semibold text-slate-500';
                oldSalary.textContent = isSalaryVisible ?
                    money(history.old_salary) :
                    '••••••••';

                const newSalary = document.createElement('td');
                newSalary.className =
                    'px-3 py-3 font-bold text-emerald-600';
                newSalary.textContent = isSalaryVisible ?
                    money(history.new_salary) :
                    '••••••••';

                const reason = document.createElement('td');
                reason.className = 'px-3 py-3';
                reason.textContent = (history.reason || '-') + (history.cancelled_at ? ' · Dibatalkan' : (history.audits_count > 0 ? ' · Dikoreksi' : ''));
                if (history.cancelled_at) row.className = 'bg-slate-50 text-slate-400';

                const actions = document.createElement('td');
                actions.className = 'px-3 py-3';
                const link = document.createElement('a');
                link.href = @json(url('/employee')) + '/' + encodeURIComponent(currentEmployeeData.id) + '/salary-history/' + encodeURIComponent(history.id) + '/edit';
                link.className = 'inline-block py-2 font-semibold text-blue-700 whitespace-nowrap';
                link.textContent = history.cancelled_at ? 'Lihat Jejak' : 'Koreksi / Batalkan';
                actions.append(link);
                row.append(date, oldSalary, newSalary, reason, actions);
                historyBody.appendChild(row);
            });
        }

        // =========================================================
        // DETAIL EMPLOYEE
        // =========================================================
        async function showEmployeeDetail(id) {
            isSalaryVisible = false;
            currentEmployeeData = null;

            const icon = document.getElementById('salary_toggle_icon');

            if (icon) {
                icon.className = 'fa-solid fa-eye-slash text-xs';
            }

            try {
                const response = await fetch(
                    @json(url('/employee')) + '/' + encodeURIComponent(id), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    }
                );

                if (!response.ok) {
                    throw new Error('Gagal mengambil rincian karyawan.');
                }

                const data = await response.json();

                currentEmployeeData = data;
                const contractLink = document.getElementById('detail_contract_link');
                if (contractLink) contractLink.href = @json(route('contracts.archive')) + '?employee_id=' + encodeURIComponent(data.id);

                const setText = (elementId, value) => {
                    const element = document.getElementById(elementId);

                    if (element) {
                        element.textContent =
                            value === null || value === undefined || value === '' ?
                            '-' :
                            value;
                    }
                };

                setText('detail_name', data.name);
                setText('detail_nik', data.nik);
                setText('detail_email', data.email);
                setText('detail_phone', data.phone_number);
                setText('detail_position', data.position || 'Karyawan');
                setText('detail_site', data.site?.machine_name);
                setText('detail_branch', data.branch?.branch_name || data.site?.branch?.branch_name);
                setText('detail_contract_start', data.contract_start_formatted);
                setText('detail_active', Number(data.is_active) === 1 ? 'Aktif' : 'Nonaktif');
                setText('detail_bank_name', data.bank_name);
                setText('detail_account_number', data.bank_account_number);
                setText('detail_site_location', data.site?.location);
                setText('detail_more_information', data.more_information);
                setText('detail_allowance_period', data.allowance_period);
                setText('detail_tenure', data.tenure_formatted);
                setText('detail_join_date', data.join_date_formatted);
                setText('detail_pkwt_date', data.pkwt_date_formatted);
                setText('detail_pkwt_number', data.pkwt_number);

                const bank = data.bank_name && data.bank_account_number ?
                    `${data.bank_name} - ${data.bank_account_number}` :
                    (data.bank_name || data.bank_account_number || '-');

                setText('detail_bank', bank);

                // Resign information
                const lastDate = document.getElementById('detail_last_date');
                const lastWrapper = document.getElementById(
                    'detail_last_date_wrapper'
                );

                setText('detail_last_date', data.resign_date_formatted);

                if (lastDate && lastWrapper) {
                    const resigned = data.status === 'Resigned';

                    lastWrapper.className = resigned ?
                        'rounded-xl border border-rose-200 bg-rose-50 p-3.5' :
                        'rounded-xl border border-slate-200 bg-slate-50/50 p-3.5';

                    lastDate.className = resigned ?
                        'mt-1 block text-xs font-extrabold text-rose-700' :
                        'mt-1 block text-xs text-slate-800';
                }

                // Status badge
                let statusClass =
                    'border-slate-200 bg-slate-100 text-slate-700';

                if (data.status === 'Resigned') {
                    statusClass =
                        'border-rose-200 bg-rose-50 text-rose-700';
                } else if (data.status === 'Permanent') {
                    statusClass =
                        'border-emerald-200 bg-emerald-50 text-emerald-700';
                } else if (data.status === 'Contract') {
                    statusClass =
                        'border-blue-200 bg-blue-50 text-blue-700';
                } else if (data.status === 'Probation') {
                    statusClass =
                        'border-amber-200 bg-amber-50 text-amber-700';
                }

                const statusHolder = document.getElementById(
                    'detail_status_badge'
                );

                if (statusHolder) {
                    statusHolder.replaceChildren();

                    const badge = document.createElement('span');

                    badge.className =
                        'inline-flex rounded-full border px-2.5 py-1 ' +
                        'text-[10px] font-extrabold uppercase ' +
                        statusClass;

                    badge.textContent = typeof window.uiLabel === 'function' ?
                        window.uiLabel(data.status || 'Active') :
                        (data.status || 'Active');

                    statusHolder.appendChild(badge);
                }

                // MCU / TLD badges
                const renderYesNoBadge = (elementId, value, color) => {
                    const holder = document.getElementById(elementId);
                    if (!holder) return;

                    holder.replaceChildren();

                    const badge = document.createElement('span');
                    const yes = value === 'yes';

                    badge.className = yes ?
                        `inline-flex rounded-md border px-2 py-0.5 text-[10px] font-extrabold ${color}` :
                        'inline-flex rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-[10px] font-extrabold text-slate-500';

                    badge.textContent = yes ? 'YA' : 'TIDAK';

                    holder.appendChild(badge);
                };

                renderYesNoBadge(
                    'detail_mcu_badge',
                    data.mcu,
                    'border-emerald-200 bg-emerald-50 text-emerald-700'
                );

                renderYesNoBadge(
                    'detail_tld_badge',
                    data.tld,
                    'border-blue-200 bg-blue-50 text-blue-700'
                );

                renderSalaryInfo();

                const modal = document.getElementById('employeeDetailModal');

                if (modal) {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.classList.add('overflow-hidden');
                }

            } catch (error) {
                console.error('Rincian karyawan gagal dimuat:', error);
                alert('Rincian karyawan gagal dimuat. Silakan coba lagi.');
            }
        }

        function closeEmployeeModal() {
            const modal = document.getElementById('employeeDetailModal');

            if (!modal) return;

            modal.classList.add('hidden');
            modal.classList.remove('flex');

            document.body.classList.remove('overflow-hidden');
        }

        // =========================================================
        // GLOBAL KEYBOARD ESCAPE
        // =========================================================
        document.addEventListener('keydown', function(event) {
            if (event.key !== 'Escape') return;

            const detailModal = document.getElementById('employeeDetailModal');
            const importModal = document.getElementById('importModal');

            if (detailModal && !detailModal.classList.contains('hidden')) {
                closeEmployeeModal();
            }

            if (importModal && !importModal.classList.contains('hidden')) {
                closeImportModal();
            }
        });
    </script>
@endsection

{{-- ========================================================= --}}
{{-- PAGE JAVASCRIPT --}}
{{-- ========================================================= --}}
