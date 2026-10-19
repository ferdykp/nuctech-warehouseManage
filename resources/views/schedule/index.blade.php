@extends('layout.master')

@section('title', 'Jadwal Kerja')

@section('content')
    <div class="w-full space-y-6">

        {{-- 1. HEADER CARD --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <nav class="flex items-center gap-2 mb-1.5 text-xs font-bold tracking-wider text-slate-400 uppercase">
                        <span class="transition-colors cursor-pointer hover:text-amber-600">Absensi dan Jadwal</span>
                        <i class="fa-solid fa-chevron-right text-[9px]"></i>
                        <span class="font-extrabold text-amber-600">Jadwal Kerja</span>
                    </nav>
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                        Jadwal Kerja Karyawan
                    </h1>
                    <p class="mt-1 text-xs font-semibold sm:text-sm text-slate-500">
                        Kelola jadwal kerja, atur pola kerja site, dan buat rotasi tim secara otomatis dan efisien.
                    </p>

                    {{-- ACCESS MODE BADGE --}}
                    @if (in_array(Auth::user()?->role, ['superadmin', 'administration']))
                        <p
                            class="mt-2 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200/80 px-3 py-1 rounded-full inline-flex items-center gap-1.5">
                            <i class="fa-solid fa-shield-halved"></i> Akses: Administrator Sistem (Semua Lokasi)
                        </p>
                    @elseif (Auth::user()?->role === 'team_leader')
                        <p
                            class="mt-2 text-xs font-bold text-blue-700 bg-blue-50 border border-blue-200/80 px-3 py-1 rounded-full inline-flex items-center gap-1.5">
                            <i class="fa-solid fa-user-gear"></i> Akses: Ketua Tim
                            ({{ Auth::user()->site->machine_name ?? 'Lokasi Terdaftar' }})
                        </p>
                    @else
                        <p
                            class="mt-2 text-xs font-bold text-slate-700 bg-slate-100 border border-slate-200/80 px-3 py-1 rounded-full inline-flex items-center gap-1.5">
                            <i class="fa-solid fa-location-dot"></i> Lokasi:
                            {{ Auth::user()->site->machine_name ?? 'Lokasi Terdaftar' }}
                        </p>
                    @endif
                </div>

                {{-- ACTION BUTTONS TOOLBAR --}}
                <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                    <button type="button" onclick="openGenerateModal()"
                        class="inline-flex items-center gap-2 px-6 py-2.5 text-xs font-bold text-white transition-all shadow-md bg-amber-600 hover:bg-amber-700 rounded-xl shadow-amber-600/20 active:scale-95 cursor-pointer">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> Buat Jadwal
                    </button>

                    {{-- EXPORT EXCEL BUTTON --}}
                    @if (in_array(Auth::user()?->role, ['superadmin', 'administration']))
                        <button type="button" onclick="openModal('modal-export-excel')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold text-white transition-all shadow-md bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-emerald-600/20 active:scale-95 cursor-pointer"
                            title="Pilihan ekspor jadwal">
                            <i class="fa-solid fa-file-excel"></i> Ekspor Excel
                        </button>
                    @else
                        <a href="{{ route('schedule.export', ['site_id' => Auth::user()->site_id, 'month' => $month, 'year' => $year]) }}"
                            up-follow="false" download
                            class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold text-white transition-all shadow-md bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-emerald-600/20 active:scale-95"
                            title="Ekspor jadwal ke Excel">
                            <i class="fa-solid fa-file-excel"></i> Ekspor Excel
                        </a>
                    @endif

                    <!-- RESET SCHEDULE BUTTON -->
                    <button type="button" onclick="openModal('modal-clear')"
                        class="inline-flex items-center gap-2 px-3.5 py-2.5 text-xs font-bold text-rose-600 transition-all border border-rose-200/80 bg-rose-50 hover:bg-rose-100 rounded-xl active:scale-95 cursor-pointer">
                        <i class="fa-solid fa-trash-can"></i> Hapus Jadwal
                    </button>
                </div>
            </div>
        </div>

        {{-- 2. ALERTS SECTION --}}
        @if (session('success'))
            <div
                class="flex items-center gap-3 p-4 text-xs font-bold border sm:text-sm text-emerald-800 border-emerald-200/80 bg-emerald-50 rounded-2xl shadow-2xs">
                <i class="text-base fa-solid fa-circle-check text-emerald-600 shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div
                class="p-4 space-y-1.5 text-xs sm:text-sm border text-rose-800 border-rose-200/80 bg-rose-50 rounded-2xl shadow-2xs">
                <div class="flex items-center gap-2 font-extrabold">
                    <i class="fa-solid fa-triangle-exclamation text-rose-600 shrink-0"></i> Jadwal gagal diperbarui. Periksa isian berikut:
                </div>
                <ul class="list-disc pl-5 space-y-0.5 font-semibold">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- MODAL EXPORT EXCEL --}}
        @if (in_array(Auth::user()?->role, ['superadmin', 'administration']))
            <div id="modal-export-excel"
                class="fixed inset-0 z-50 items-center justify-center hidden p-4 transition-all duration-200 bg-slate-900/60 backdrop-blur-xs modal-overlay"
                onclick="if(event.target===this) closeModal('modal-export-excel')">
                <div class="w-full max-w-md overflow-hidden bg-white border shadow-2xl border-slate-100 rounded-3xl">
                    <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 bg-slate-50/50">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex items-center justify-center w-10 h-10 border text-emerald-600 bg-emerald-50 border-emerald-100 rounded-2xl shrink-0">
                                <i class="text-lg fa-solid fa-file-excel"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-extrabold text-slate-900">Ekspor Jadwal ke Excel</h3>
                                <p class="text-xs font-medium text-slate-500 mt-0.5">Pilih cakupan site dan periode jadwal
                                    yang ingin diexport.</p>
                            </div>
                        </div>
                        <button type="button" onclick="closeModal('modal-export-excel')"
                            class="flex items-center justify-center w-8 h-8 transition-colors rounded-lg cursor-pointer text-slate-400 hover:text-slate-600 bg-slate-100 hover:bg-slate-200">&times;</button>
                    </div>

                    <form action="{{ route('schedule.export') }}" method="GET" class="p-6 space-y-4">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Pilih Cakupan Lokasi</label>
                            <select name="site_id" id="export_site_id"
                                class="w-full p-2.5 text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all text-slate-800 cursor-pointer">
                                <option value="all" {{ ($selectedSiteId ?? 'all') == 'all' ? 'selected' : '' }}>
                                    🌐 Semua Lokasi (Semua Lokasi)
                                </option>
                                @foreach ($sites as $st)
                                    <option value="{{ $st->id }}"
                                        {{ ($selectedSiteId ?? '') == $st->id ? 'selected' : '' }}>
                                        📍 {{ $st->machine_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Bulan</label>
                                <select name="month"
                                    class="w-full p-2.5 text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all text-slate-800 cursor-pointer">
                                    @for ($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create(2000, $m, 1)->translatedFormat('F') }}
                                        </option>
                                    @endfor
                                </select>
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Tahun</label>
                                <select name="year"
                                    class="w-full p-2.5 text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all text-slate-800 cursor-pointer">
                                    @for ($y = date('Y') - 1; $y <= date('Y') + 2; $y++)
                                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                                            {{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                            <button type="button" onclick="closeModal('modal-export-excel')"
                                class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors cursor-pointer">Batal</button>
                            <button type="submit" onclick="closeModal('modal-export-excel')"
                                class="inline-flex items-center gap-2 px-6 py-2.5 text-xs font-bold text-white transition-all shadow-md bg-emerald-600 rounded-xl hover:bg-emerald-700 shadow-emerald-600/20 active:scale-95 cursor-pointer">
                                <i class="fa-solid fa-download"></i> Unduh Excel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- 3. MAIN WORKSPACE CARD CONTAINER --}}
        <div class="space-y-6 overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">

            {{-- FILTER BAR --}}
            <div class="p-5 border-b sm:p-6 border-slate-100 bg-slate-50/50">
                <form action="{{ route('schedule.index') }}" method="GET" id="mainFilterForm"
                    class="flex flex-wrap items-end gap-3.5">

                    {{-- FILTER SITE LOCATION --}}
                    <div class="w-full sm:w-64">
                        <label class="block mb-1.5 text-xs font-extrabold uppercase tracking-wider text-slate-500">
                            Lokasi Mesin
                        </label>
                        <select name="site_id" id="main_site_select"
                            class="w-full py-2.5 px-3.5 text-xs sm:text-sm font-bold bg-white border border-slate-200 rounded-xl focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 text-slate-800 transition-all outline-none cursor-pointer">
                            <option value="all" {{ ($selectedSiteId ?? 'all') == 'all' ? 'selected' : '' }}>
                                -- {{ Auth::user()?->role === 'team_leader' ? 'Semua Lokasi Penempatan' : 'Semua Lokasi' }} --
                            </option>
                            @foreach ($sites as $st)
                                <option value="{{ $st->id }}"
                                    {{ ($selectedSiteId ?? '') == $st->id ? 'selected' : '' }}>
                                    {{ $st->machine_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="w-1/2 sm:w-44">
                        <label
                            class="block mb-1.5 text-xs font-extrabold uppercase tracking-wider text-slate-500">Bulan</label>
                        <select name="month" id="main_month_select"
                            class="w-full py-2.5 px-3.5 text-xs sm:text-sm font-bold bg-white border border-slate-200 rounded-xl focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 text-slate-800 transition-all outline-none cursor-pointer">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create(2000, $m, 1)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div class="w-1/2 sm:w-36">
                        <label
                            class="block mb-1.5 text-xs font-extrabold uppercase tracking-wider text-slate-500">Tahun</label>
                        <select name="year" id="main_year_select"
                            class="w-full py-2.5 px-3.5 text-xs sm:text-sm font-bold bg-white border border-slate-200 rounded-xl focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 text-slate-800 transition-all outline-none cursor-pointer">
                            @for ($y = date('Y') - 1; $y <= date('Y') + 2; $y++)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                                    {{ $y }}</option>
                            @endfor
                        </select>
                    </div>

                    <button type="submit"
                        class="px-5 py-2.5 text-xs font-bold text-white transition-all shadow-md bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-emerald-600/20 active:scale-95 cursor-pointer">
                        <i class="mr-1.5 fa-solid fa-filter"></i> Apply Filter
                    </button>
                </form>
            </div>

            {{-- NATIONAL HOLIDAYS BANNER --}}
            @if (!empty($holidays))
                <div class="p-4 mx-5 border sm:mx-6 border-rose-200/80 rounded-2xl bg-rose-50/60">
                    <h6 class="flex items-center gap-2 mb-2 text-xs font-extrabold tracking-wider uppercase text-rose-700">
                        <i class="fa-solid fa-circle-exclamation"></i> Hari Libur Nasional dan Tanggal Merah Bulan Ini
                    </h6>
                    <ul class="grid grid-cols-1 gap-x-6 gap-y-1.5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($holidays as $date => $name)
                            <li class="flex items-baseline gap-2 text-xs">
                                <span
                                    class="w-6 font-black text-right text-rose-700 shrink-0">{{ \Carbon\Carbon::parse($date)->format('d') }}</span>
                                <span class="font-semibold text-slate-700">{{ $name }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- ARRAY TAMPUNG KARYAWAN YANG MASUK DI HARI LIBUR NASIONAL --}}
            @php
                $holidayWorkDuty = [];
            @endphp

            {{-- CALENDAR GRID PREVIEW --}}
            <div>
                <div class="flex flex-col justify-between gap-3 px-5 pb-4 sm:px-6 lg:flex-row lg:items-center">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-sm font-extrabold text-slate-900 sm:text-base">Jadwal Bulanan</h3>
                        @if (Auth::user()?->role === 'team_leader')
                            <span
                                class="px-2.5 py-0.5 text-xs font-extrabold text-amber-800 bg-amber-50 border border-amber-200/80 rounded-md uppercase">
                                Ketua Tim: {{ Auth::user()->site->machine_name ?? 'Terdaftar' }}
                            </span>
                        @endif
                        <span
                            class="px-3 py-1 text-xs font-black text-blue-800 uppercase border rounded-full border-blue-200/80 bg-blue-50">
                            {{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}
                        </span>
                    </div>

                    {{-- LEGEND BADGES --}}
                    <div class="flex flex-wrap items-center gap-2.5 text-xs font-bold text-slate-500">
                        <span class="flex items-center gap-1.5"><span
                                class="inline-block w-2.5 h-2.5 rounded-xs bg-blue-50 border border-blue-200"></span> Shift
                            1</span>
                        <span class="flex items-center gap-1.5"><span
                                class="inline-block w-2.5 h-2.5 rounded-xs bg-amber-50 border border-amber-200"></span>
                            Shift 2</span>
                        <span class="flex items-center gap-1.5"><span
                                class="inline-block w-2.5 h-2.5 rounded-xs bg-purple-50 border border-purple-200"></span>
                            Shift 3</span>
                        <span class="flex items-center gap-1.5"><span
                                class="inline-block w-2.5 h-2.5 rounded-xs bg-emerald-50 border border-emerald-200"></span>
                            Lainnya / Jam Kantor</span>
                        <span class="flex items-center gap-1.5"><span
                                class="inline-block w-2.5 h-2.5 rounded-xs bg-rose-50 border border-rose-200"></span>
                            LIBUR</span>
                        <span class="flex items-center gap-1.5"><span
                                class="inline-block w-2.5 h-2.5 rounded-xs bg-slate-100 border border-slate-300"></span>
                            Sudah Berhenti</span>
                        <span class="flex items-center gap-1 font-extrabold text-rose-600"><i
                                class="fa-solid fa-circle-exclamation"></i> Libur Nasional</span>
                    </div>
                </div>

                <div class="overflow-x-auto max-h-[70dvh]">
                    <table class="ui-schedule-table w-full border-collapse table-fixed" style="--schedule-days: {{ count($datesInMonth) }}">
                        <thead>
                            <tr
                                class="text-xs font-extrabold uppercase tracking-wider text-slate-500 bg-slate-50 border-y border-slate-100">
                                <th
                                    class="w-48 px-4 py-3.5 text-left sticky left-0 top-0 bg-slate-50 z-20 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                                    Karyawan / Lokasi
                                </th>

                                @foreach ($datesInMonth as $date)
                                    @php
                                        $holidayName = $holidays[$date->format('Y-m-d')] ?? null;
                                        $isRedDate = $date->isWeekend() || $holidayName;
                                    @endphp
                                    <th class="w-11 text-center py-2.5 border-l border-slate-100 {{ $isRedDate ? 'bg-rose-50/80 text-rose-700' : '' }}"
                                        @if ($holidayName) title="{{ $holidayName }}" @endif>
                                        <div class="font-black">{{ $date->format('d') }}</div>
                                        <div class="text-xs font-bold uppercase mt-0.5">
                                            {{ $date->translatedFormat('D') }}</div>
                                        @if ($holidayName)
                                            <div class="mt-0.5 truncate px-0.5 text-xs font-bold text-rose-600"
                                                title="{{ $holidayName }}">
                                                <i class="fa-solid fa-circle-exclamation"></i>
                                            </div>
                                        @endif
                                    </th>
                                @endforeach

                                <th
                                    class="py-2.5 font-extrabold text-center text-blue-800 border-l border-slate-100 w-14 bg-blue-50/60">
                                    <div>KERJA</div>
                                    <div class="text-xs text-blue-600 uppercase">(Hari)</div>
                                </th>
                                <th
                                    class="py-2.5 font-extrabold text-center border-l text-rose-800 border-slate-100 w-14 bg-rose-50/60">
                                    <div>LIBUR</div>
                                    <div class="text-xs text-rose-600 uppercase">(Hari)</div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="text-xs font-medium divide-y divide-slate-100 text-slate-700">
                            @forelse($employees as $emp)
                                <tr class="transition-colors hover:bg-slate-50/60">
                                    <td
                                        class="px-4 py-3 sticky left-0 bg-white font-extrabold text-slate-900 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-xs truncate">{{ $emp->name }}</span>
                                            @if ($emp->last_date)
                                                <span
                                                    class="px-1.5 py-0.2 text-xs font-black text-rose-700 bg-rose-50 border border-rose-200 rounded-md shrink-0">
                                                    Berhenti Bekerja
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-xs font-semibold text-slate-400 truncate mt-0.5">
                                            {{ $emp->site->machine_name ?? '-' }}
                                        </div>
                                    </td>

                                    @php
                                        $totalWorkCount = 0;
                                        $totalOffCount = 0;
                                        $schedulesByDate = $emp->schedules->keyBy(fn($s) => $s->date->format('Y-m-d'));
                                    @endphp

                                    @foreach ($datesInMonth as $date)
                                        @php
                                            $dateStr = $date->format('Y-m-d');
                                            $schedule = $schedulesByDate->get($dateStr);
                                            $shiftName = $schedule?->shift?->shift_name;
                                            $holidayName = $holidays[$dateStr] ?? null;

                                            $isAfterResign =
                                                $emp->last_date &&
                                                $date->greaterThan(\Carbon\Carbon::parse($emp->last_date)->endOfDay());

                                            $badgeColor = 'bg-white text-slate-300 border-slate-200';
                                            $label = '-';

                                            if (!$isAfterResign && $schedule && $schedule->shift) {
                                                if ($schedule->shift->is_off) {
                                                    $badgeColor = 'bg-rose-50 text-rose-800 border-rose-200';
                                                    $label = 'OFF';
                                                    $totalOffCount++;
                                                } else {
                                                    $totalWorkCount++;
                                                    $label = '';
                                                    foreach (explode(' ', $shiftName) as $word) {
                                                        $label .= strtoupper(substr($word, 0, 1));
                                                    }

                                                    if (
                                                        str_contains(strtolower($shiftName), '1') ||
                                                        str_contains(strtolower($shiftName), 'hour')
                                                    ) {
                                                        $badgeColor = 'bg-blue-50 text-blue-800 border-blue-200';
                                                    } elseif (str_contains(strtolower($shiftName), '2')) {
                                                        $badgeColor = 'bg-amber-50 text-amber-800 border-amber-200';
                                                    } elseif (str_contains(strtolower($shiftName), '3')) {
                                                        $badgeColor = 'bg-purple-50 text-purple-800 border-purple-200';
                                                    } else {
                                                        $badgeColor =
                                                            'bg-emerald-50 text-emerald-800 border-emerald-200';
                                                    }

                                                    if ($holidayName) {
                                                        $holidayWorkDuty[] = [
                                                            'employee_name' => $emp->name,
                                                            'site_name' => $emp->site->machine_name ?? '-',
                                                            'date' => $date->format('d/m/Y'),
                                                            'day_name' => $date->translatedFormat('l'),
                                                            'holiday_name' => $holidayName,
                                                            'shift_name' => $shiftName,
                                                        ];
                                                    }
                                                }
                                            }
                                        @endphp
                                        <td
                                            class="p-1 text-center border-l border-slate-100 {{ $isAfterResign ? 'bg-slate-100/70' : '' }}">
                                            @if ($isAfterResign)
                                                <span class="text-xs font-bold text-slate-300 select-none"
                                                    title="Hari terakhir bekerja: {{ \Carbon\Carbon::parse($emp->last_date)->translatedFormat('d M Y') }}">
                                                    N/A
                                                </span>
                                            @else
                                                <button type="button"
                                                    onclick="openEditShiftModal({{ $emp->id }}, '{{ addslashes($emp->name) }}', '{{ $date->format('Y-m-d') }}', '{{ $schedule?->shift_id ?? '' }}')"
                                                    class="w-full py-1 text-[9px] font-black border rounded-md transition-transform active:scale-95 cursor-pointer {{ $badgeColor }}"
                                                    title="Klik untuk mengubah shift ({{ $shiftName ?? 'Belum Ditentukan' }})">
                                                    {{ $label }}
                                                </button>
                                            @endif
                                        </td>
                                    @endforeach

                                    <td
                                        class="px-2 py-3 font-black text-center text-blue-800 border-l border-slate-100 bg-blue-50/30">
                                        {{ $totalWorkCount }}
                                    </td>
                                    <td
                                        class="px-2 py-3 font-black text-center border-l text-rose-800 border-slate-100 bg-rose-50/30">
                                        {{ $totalOffCount }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($datesInMonth) + 3 }}" class="p-12 text-center text-slate-400">
                                        <div
                                            class="flex items-center justify-center w-12 h-12 mx-auto mb-3 text-xl rounded-2xl bg-slate-100 text-slate-400">
                                            <i class="fa-solid fa-calendar-xmark"></i>
                                        </div>
                                        <p class="text-sm font-bold text-slate-800">Jadwal Tidak Ditemukan</p>
                                        <p class="mt-1 text-xs text-slate-400">Belum ada jadwal untuk lokasi dan periode ini.
                                        </p>
                                        <button type="button" onclick="openGenerateModal()"
                                            class="inline-flex items-center gap-1 mt-3 text-xs font-bold cursor-pointer text-amber-600 hover:underline">
                                            Buat jadwal sekarang &rarr;
                                        </button>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- 4. HOLIDAY WORK DUTY RECAP CARD --}}
        <div class="overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">
            <div class="flex items-center justify-between p-5 border-b sm:p-6 border-slate-100 bg-slate-50/50">
                <div class="flex items-center gap-3">
                    <div
                        class="flex items-center justify-center w-10 h-10 border text-rose-600 bg-rose-50 border-rose-100 rounded-2xl shrink-0">
                        <i class="text-lg fa-solid fa-calendar-day"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Jadwal Tugas Hari Libur Nasional</h3>
                        <p class="text-xs font-medium text-slate-500 mt-0.5">Daftar karyawan yang memiliki jadwal
                            piket/masuk kerja khusus pada tanggal merah & hari libur nasional.</p>
                    </div>
                </div>
                <span
                    class="px-3 py-1 text-xs font-black uppercase border rounded-full text-rose-800 border-rose-200/80 bg-rose-50">
                    Total: {{ count($holidayWorkDuty) }} Duty Logs
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr
                            class="text-xs font-extrabold uppercase tracking-wider text-slate-500 bg-slate-50 border-b border-slate-100">
                            <th class="px-6 py-3.5">Nama Karyawan</th>
                            <th class="px-6 py-3.5">Lokasi Mesin</th>
                            <th class="px-6 py-3.5">Tanggal dan Hari</th>
                            <th class="px-6 py-3.5">Hari Libur Nasional</th>
                            <th class="px-6 py-3.5">Assigned Shift</th>
                            <th class="px-6 py-3.5 text-center">Label Status</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs font-medium divide-y divide-slate-100 text-slate-700">
                        @if (count($holidayWorkDuty) > 0)
                            @foreach ($holidayWorkDuty as $duty)
                                <tr class="transition-colors hover:bg-slate-50/60">
                                    <td class="px-6 py-3.5 font-extrabold text-slate-900">
                                        {{ $duty['employee_name'] }}
                                    </td>
                                    <td class="px-6 py-3.5 font-semibold text-slate-500">
                                        {{ $duty['site_name'] }}
                                    </td>
                                    <td class="px-6 py-3.5 font-bold text-slate-800">
                                        {{ $duty['date'] }} <span
                                            class="text-xs font-normal text-slate-400">({{ $duty['day_name'] }})</span>
                                    </td>
                                    <td class="px-6 py-3.5 font-bold text-rose-600">
                                        <i class="mr-1 fa-solid fa-flag text-rose-600"></i>
                                        {{ $duty['holiday_name'] }}
                                    </td>
                                    <td class="px-6 py-3.5">
                                        <span
                                            class="px-2.5 py-1 text-xs font-black rounded-lg border bg-slate-100 border-slate-200 text-slate-700">
                                            {{ $duty['shift_name'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3.5 text-center">
                                        <span
                                            class="px-2.5 py-0.5 text-xs font-extrabold text-rose-800 bg-rose-50 border border-rose-200/80 rounded-full">
                                            Tugas Hari Libur Nasional
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-400">
                                    <div
                                        class="flex items-center justify-center w-10 h-10 mx-auto mb-2 text-lg rounded-xl bg-slate-100 text-slate-400">
                                        <i class="fa-solid fa-user-check"></i>
                                    </div>
                                    <p class="text-xs font-bold text-slate-700">Tidak ada karyawan yang bertugas pada
                                        tanggal merah / hari libur nasional di periode ini.</p>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- MODAL GENERATE TEAM ROTAS --}}
    <div id="modal-generate"
        class="fixed inset-0 z-50 items-center justify-center hidden p-3 transition-all duration-200 sm:p-6 bg-slate-900/60 backdrop-blur-xs modal-overlay"
        onclick="if(event.target===this) closeModal('modal-generate')">
        <div
            class="w-full max-w-6xl bg-white border border-slate-100 shadow-2xl rounded-3xl overflow-hidden max-h-[94vh] flex flex-col">

            <div
                class="flex items-center justify-between px-6 py-5 border-b border-slate-100 bg-slate-50/50 shrink-0 sm:px-8">
                <div>
                    <h3 class="text-lg font-extrabold text-slate-900">Buat Jadwal Rotasi Tim</h3>
                    <p class="mt-1 text-xs font-medium sm:text-sm text-slate-500">Atur pola kerja site dan generate jadwal
                        dalam satu alur secara terpadu.</p>
                </div>
                <button type="button" onclick="closeModal('modal-generate')"
                    class="flex items-center justify-center transition-colors rounded-lg cursor-pointer w-9 h-9 text-slate-400 hover:text-slate-600 bg-slate-100 hover:bg-slate-200 shrink-0">&times;</button>
            </div>

            <form action="{{ route('schedule.generate') }}" method="POST" id="generateForm"
                class="flex flex-col flex-1 overflow-hidden">
                @csrf

                <div class="flex-1 px-6 py-6 space-y-6 overflow-y-auto sm:px-8">
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                        <div class="space-y-6 lg:col-span-7">

                            {{-- STEP 1: MULTI-SITE & PERIOD --}}
                            <div class="p-5 border border-teal-200/80 bg-teal-50/40 rounded-2xl sm:p-6">
                                <div class="flex items-center gap-2.5 mb-4">
                                    <span
                                        class="flex items-center justify-center w-6 h-6 text-xs font-black text-white bg-teal-500 rounded-full shrink-0">1</span>
                                    <span
                                        class="text-xs font-extrabold tracking-wider uppercase text-slate-800 sm:text-sm">Lokasi dan Periode</span>
                                </div>

                                <div class="space-y-4">
                                    {{-- MULTI-SITE CHECKBOXES --}}
                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <label
                                                class="block text-xs font-bold text-slate-500 uppercase tracking-wider">
                                                Pilih Lokasi (Bisa Lebih dari Satu)
                                            </label>
                                            <label
                                                class="inline-flex items-center gap-1.5 text-xs font-bold text-teal-700 cursor-pointer">
                                                <input type="checkbox" id="check-all-sites"
                                                    onchange="toggleAllGenSites(this.checked)"
                                                    class="w-3.5 h-3.5 text-teal-600 border-slate-300 rounded focus:ring-teal-500 cursor-pointer">
                                                Pilih Semua
                                            </label>
                                        </div>

                                        <div
                                            class="grid grid-cols-1 gap-2 p-3 overflow-y-auto bg-white border sm:grid-cols-2 border-slate-200 rounded-2xl max-h-44">
                                            @foreach ($sites as $st)
                                                <label
                                                    class="flex items-center gap-2.5 p-2 text-xs font-bold border border-slate-100 rounded-xl hover:bg-teal-50/50 cursor-pointer text-slate-800">
                                                    <input type="checkbox" name="target_site_ids[]"
                                                        value="{{ $st->id }}" onchange="onGenSiteChange()"
                                                        class="w-4 h-4 text-teal-600 rounded border-slate-300 gen-site-checkbox focus:ring-teal-500 shrink-0"
                                                        {{ count($sites) === 1 || ($selectedSiteId ?? 'all') == 'all' || ($selectedSiteId ?? '') == $st->id || Auth::user()?->site_id == $st->id ? 'checked' : '' }}>
                                                    <span class="truncate">📍 {{ $st->machine_name }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- PERIODE BULAN & TAHUN --}}
                                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                        <div class="col-span-1">
                                            <label
                                                class="block mb-1.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Hari Mulai</label>
                                            <input type="number" name="start_day" value="{{ old('start_day', 1) }}"
                                                min="1" max="31"
                                                class="w-full p-2.5 text-xs font-bold bg-white border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 text-slate-800 transition-all"
                                                required>
                                        </div>

                                        <div class="col-span-1 sm:col-span-2">
                                            <label
                                                class="block mb-1.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Bulan</label>
                                            <select name="month" id="gen_month"
                                                class="w-full p-2.5 text-xs font-bold bg-white border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 text-slate-800 transition-all cursor-pointer">
                                                @for ($m = 1; $m <= 12; $m++)
                                                    <option value="{{ $m }}"
                                                        {{ $month == $m ? 'selected' : '' }}>
                                                        {{ \Carbon\Carbon::create(2000, $m, 1)->translatedFormat('F') }}
                                                    </option>
                                                @endfor
                                            </select>
                                        </div>

                                        <div class="col-span-1">
                                            <label
                                                class="block mb-1.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Tahun</label>
                                            <select name="year" id="gen_year"
                                                class="w-full p-2.5 text-xs font-bold bg-white border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 text-slate-800 transition-all cursor-pointer">
                                                @for ($y = date('Y') - 1; $y <= date('Y') + 2; $y++)
                                                    <option value="{{ $y }}"
                                                        {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- STEP 2: POLA KERJA SITE --}}
                            <div class="p-5 border border-amber-200/80 bg-amber-50/40 rounded-2xl sm:p-6">
                                <div class="flex items-center gap-2.5 mb-1.5">
                                    <span
                                        class="flex items-center justify-center w-6 h-6 text-xs font-black text-white rounded-full bg-amber-500 shrink-0">2</span>
                                    <span
                                        class="text-xs font-extrabold tracking-wider uppercase text-slate-800 sm:text-sm">Pola
                                        Kerja Lokasi</span>
                                </div>
                                <p class="mb-4 text-xs font-medium leading-relaxed text-slate-500 ml-[34px]">
                                    Pola ini akan otomatis disimpan untuk site yang dipilih saat Anda menekan Buat.
                                </p>

                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div class="sm:col-span-1">
                                        <label
                                            class="block mb-1.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Jenis Pola</label>
                                        <select name="schedule_type" id="gen_schedule_type"
                                            onchange="onScheduleTypeChange()"
                                            class="w-full p-2.5 text-xs font-bold bg-white border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 text-slate-800 transition-all cursor-pointer">
                                            <option value="office_hour">Jam Kantor (Senin–Jumat)</option>
                                            <option value="shift_rotation">Rotasi Shift</option>
                                        </select>
                                    </div>
                                    <div id="gen_work_days_wrapper">
                                        <label
                                            class="block mb-1.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Hari Kerja</label>
                                        <input type="number" name="work_days" id="gen_work_days" value="6"
                                            min="1"
                                            class="w-full p-2.5 text-xs font-bold bg-white border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 text-slate-800 transition-all">
                                    </div>
                                    <div id="gen_off_days_wrapper">
                                        <label
                                            class="block mb-1.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Hari Libur</label>
                                        <input type="number" name="off_days" id="gen_off_days" value="2"
                                            min="1"
                                            class="w-full p-2.5 text-xs font-bold bg-white border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 text-slate-800 transition-all">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- STEP 3: SELECT STAFF --}}
                        <div class="lg:col-span-5">
                            <div
                                class="flex flex-col h-full p-5 border border-blue-200/80 bg-blue-50/40 rounded-2xl sm:p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-2.5">
                                        <span
                                            class="flex items-center justify-center w-6 h-6 text-xs font-black text-white bg-blue-500 rounded-full shrink-0">3</span>
                                        <span
                                            class="text-xs font-extrabold tracking-wider uppercase text-slate-800 sm:text-sm">Pilih
                                            Staff</span>
                                    </div>
                                    <label
                                        class="flex items-center gap-1.5 text-xs font-bold text-slate-600 cursor-pointer">
                                        <input type="checkbox" onchange="toggleAllEmployees(this.checked)"
                                            class="w-3.5 h-3.5 text-blue-600 border-slate-300 rounded focus:ring-blue-500 cursor-pointer">
                                        Pilih Semua
                                    </label>
                                </div>

                                <input type="text" id="employee-search" oninput="filterEmployees()"
                                    placeholder="Cari nama karyawan..."
                                    class="w-full p-2.5 mb-3 text-xs font-medium bg-white border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 text-slate-800 placeholder-slate-400 transition-all">

                                <div id="employee-empty-notice"
                                    class="hidden p-3 mb-3 text-xs font-semibold text-center border border-dashed text-slate-400 border-slate-300 rounded-xl">
                                    Pilih minimal satu lokasi pada tahap 1 untuk menampilkan daftar karyawan.
                                </div>

                                <div
                                    class="grid flex-1 grid-cols-1 content-start sm:grid-cols-2 gap-2.5 p-3 bg-white border border-slate-200 rounded-2xl min-h-[220px] max-h-[340px] lg:max-h-none overflow-y-auto">
                                    @foreach ($employees as $emp)
                                        <label data-name="{{ strtolower($emp->name) }}"
                                            data-site-id="{{ $emp->site_id }}"
                                            class="flex items-center gap-2 p-2 text-xs font-medium border border-transparent cursor-pointer text-slate-700 rounded-xl employee-option hover:bg-slate-50 hover:border-slate-100">
                                            <input type="checkbox" name="employee_ids[]" value="{{ $emp->id }}"
                                                class="w-4 h-4 text-blue-600 rounded cursor-pointer border-slate-300 employee-checkbox focus:ring-blue-500 shrink-0"
                                                {{ in_array($emp->id, old('employee_ids', [])) ? 'checked' : '' }}>
                                            <div class="truncate">
                                                <div class="flex items-center gap-1.5">
                                                    <span
                                                        class="block text-xs font-bold text-slate-800">{{ $emp->name }}</span>
                                                    @if ($emp->last_date)
                                                        <span
                                                            class="px-1.5 py-0.2 text-xs font-black text-rose-700 bg-rose-50 border border-rose-200 rounded-md shrink-0">
                                                            Terakhir:
                                                            {{ \Carbon\Carbon::parse($emp->last_date)->format('d M') }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <span class="text-xs block text-slate-400 font-semibold">Lokasi:
                                                    {{ $emp->site->machine_name ?? '-' }}</span>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- STEP 4: SHIFT ROTATION & SEQUENCE --}}
                    <div id="gen_step4" class="p-5 border border-purple-200/80 bg-purple-50/40 rounded-2xl sm:p-6">
                        <div class="flex items-center gap-2.5 mb-4">
                            <span
                                class="flex items-center justify-center w-6 h-6 text-xs font-black text-white bg-purple-500 rounded-full shrink-0">4</span>
                            <span class="text-xs font-extrabold tracking-wider uppercase text-slate-800 sm:text-sm">
                                Atur Rotasi dan Urutan Shift
                            </span>
                        </div>

                        <div id="oh-notice-banner"
                            class="hidden p-4 mb-4 text-xs font-medium leading-relaxed text-blue-800 border border-blue-200/80 bg-blue-50 rounded-xl">
                            <i class="mr-1.5 fa-solid fa-circle-info"></i>
                            Pola dikonfigurasi untuk <strong>Jam Kantor</strong>. Jadwal akan otomatis dibuat untuk hari
                            kerja normal (Senin–Jumat) tanpa rotasi shift.
                        </div>

                        <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
                            <div class="lg:col-span-8">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Urutan Shift Aktif</label>
                                    <span class="text-xs text-slate-400 font-medium">Gunakan tombol <i
                                            class="fa-solid fa-arrow-up text-slate-500"></i> <i
                                            class="fa-solid fa-arrow-down text-slate-500"></i> untuk mengatur urutan
                                        rotasi</span>
                                </div>

                                <div id="shift-sequence-container"
                                    class="space-y-2 p-2.5 bg-white border border-slate-200 rounded-2xl min-h-[200px] max-h-[280px] overflow-y-auto">
                                    @foreach (App\Models\Shift::orderBy('is_off', 'asc')->orderBy('start_time', 'asc')->get() as $sf)
                                        @php
                                            $sfNameLower = strtolower($sf->shift_name);
                                            $isOfficeHour =
                                                str_contains($sfNameLower, 'office') ||
                                                str_contains($sfNameLower, 'oh') ||
                                                str_contains($sfNameLower, 'normal');
                                        @endphp
                                        <div class="flex items-center justify-between p-2.5 border bg-slate-50 border-slate-200/80 rounded-xl shift-item-row transition-shadow duration-200"
                                            data-shift-id="{{ $sf->id }}" data-shift-name="{{ $sf->shift_name }}"
                                            data-is-oh="{{ $isOfficeHour ? 'true' : 'false' }}"
                                            data-is-off="{{ $sf->is_off ? 'true' : 'false' }}">

                                            <label
                                                class="flex items-center gap-2 text-xs font-bold cursor-pointer text-slate-700">
                                                <input type="checkbox" name="active_shifts[]"
                                                    value="{{ $sf->id }}" onchange="updateStartingShift()"
                                                    class="w-4 h-4 text-purple-600 rounded cursor-pointer active-shift-checkbox focus:ring-purple-500"
                                                    {{ old('active_shifts') ? (in_array($sf->id, old('active_shifts')) ? 'checked' : '') : 'checked' }}>
                                                <span>{{ $sf->shift_name }} {{ $sf->is_off ? '(OFF/Libur)' : '' }}</span>
                                                <span
                                                    class="start-badge hidden items-center gap-1 px-1.5 py-0.5 text-xs font-black tracking-wider text-white uppercase bg-purple-600 rounded-full">
                                                    <i class="fa-solid fa-flag text-xs"></i> Mulai
                                                </span>
                                            </label>

                                            <div class="flex items-center gap-1">
                                                <button type="button" onclick="moveShiftItem(this, 'up')"
                                                    class="p-1.5 transition-colors rounded-lg text-slate-400 hover:text-purple-600 hover:bg-slate-200 cursor-pointer"
                                                    title="Geser ke Atas">
                                                    <i class="text-xs fa-solid fa-arrow-up"></i>
                                                </button>
                                                <button type="button" onclick="moveShiftItem(this, 'down')"
                                                    class="p-1.5 transition-colors rounded-lg text-slate-400 hover:text-purple-600 hover:bg-slate-200 cursor-pointer"
                                                    title="Geser ke Bawah">
                                                    <i class="text-xs fa-solid fa-arrow-down"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="flex flex-col gap-5 lg:col-span-4">
                                <div>
                                    <label
                                        class="block mb-2 text-xs font-bold tracking-wider uppercase text-slate-700">Rotasi Dimulai dari</label>
                                    <input type="hidden" name="start_shift_id" id="gen_start_shift_id">
                                    <div id="start_shift_display"
                                        class="flex items-center gap-2 w-full p-2.5 text-xs font-bold bg-purple-50 border border-purple-200 rounded-xl text-purple-800 transition-colors">
                                        <i class="text-purple-500 fa-solid fa-flag"></i>
                                        <span id="start_shift_display_text">—</span>
                                    </div>
                                    <p class="mt-2.5 text-xs text-slate-400 leading-relaxed font-medium">
                                        Otomatis mengikuti shift paling atas di Urutan Shift Aktif.
                                    </p>
                                </div>

                                <div id="gen_shift_duration_wrapper">
                                    <label
                                        class="block mb-2 text-xs font-bold tracking-wider uppercase text-slate-700">Durasi Shift (Hari)</label>
                                    <input type="number" name="shift_duration" id="gen_shift_duration"
                                        value="{{ old('shift_duration', 2) }}" min="1"
                                        class="w-full p-2.5 text-xs font-bold bg-white border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 text-slate-800 transition-all">
                                    <p class="mt-2.5 text-xs text-slate-400 font-medium leading-relaxed">
                                        Durasi hari berturut-turut pada shift yang sama.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- STICKY FOOTER --}}
                <div
                    class="flex flex-col-reverse items-center justify-between gap-3 px-6 py-4 border-t sm:flex-row border-slate-100 bg-slate-50/70 shrink-0 sm:px-8">
                    <p id="employee-count" class="text-xs font-bold text-slate-500">Belum ada karyawan yang dipilih</p>
                    <div class="flex items-center gap-3">
                        <button type="button" onclick="closeModal('modal-generate')"
                            class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors cursor-pointer">Batal</button>
                        <button type="submit" id="btn-submit-generate"
                            class="px-6 py-2.5 text-xs font-bold text-white transition-all shadow-md bg-amber-600 hover:bg-amber-700 rounded-xl shadow-amber-600/20 active:scale-95 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="mr-1.5 fa-solid fa-wand-magic-sparkles"></i> Simpan Pola dan Buat Jadwal
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL EDIT INDIVIDUAL SHIFT --}}
    <div id="modal-edit-shift"
        class="fixed inset-0 z-50 items-center justify-center hidden p-4 transition-all duration-200 bg-slate-900/60 backdrop-blur-xs modal-overlay"
        onclick="if(event.target===this) closeModal('modal-edit-shift')">
        <div class="w-full max-w-sm overflow-hidden bg-white border shadow-2xl border-slate-100 rounded-3xl">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 bg-slate-50/50">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Ubah Shift Karyawan</h3>
                    <p id="edit-shift-subtitle" class="text-xs font-medium text-slate-500 mt-0.5"></p>
                </div>
                <button type="button" onclick="closeModal('modal-edit-shift')"
                    class="flex items-center justify-center w-8 h-8 transition-colors rounded-lg cursor-pointer text-slate-400 hover:text-slate-600 bg-slate-100 hover:bg-slate-200">&times;</button>
            </div>

            <form id="form-quick-edit-shift" onsubmit="submitQuickEditShift(event)" class="p-6 space-y-4">
                @csrf
                <input type="hidden" id="edit_employee_id" name="employee_id">
                <input type="hidden" id="edit_date" name="date">

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Pilih Shift</label>
                    <select id="edit_shift_id" name="shift_id"
                        class="w-full p-2.5 text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all text-slate-800 cursor-pointer"
                        required>
                        @foreach (App\Models\Shift::orderBy('start_time', 'asc')->get() as $sf)
                            <option value="{{ $sf->id }}">
                                {{ $sf->shift_name }} {{ $sf->is_off ? '(OFF DAY)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-edit-shift')"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors cursor-pointer">Batal</button>
                    <button type="submit"
                        class="px-6 py-2.5 text-xs font-bold text-white transition-all bg-blue-600 shadow-md rounded-xl hover:bg-blue-700 shadow-blue-600/20 active:scale-95 cursor-pointer">Simpan
                        Shift</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL RESET / CLEAR SCHEDULE --}}
    <div id="modal-clear"
        class="fixed inset-0 z-50 items-center justify-center hidden p-4 transition-all duration-200 bg-slate-900/60 backdrop-blur-xs modal-overlay"
        onclick="if(event.target===this) closeModal('modal-clear')">
        <div class="w-full max-w-md p-6 overflow-hidden bg-white border shadow-2xl border-slate-100 rounded-3xl">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div
                        class="flex items-center justify-center w-10 h-10 border text-rose-600 bg-rose-50 border-rose-100 rounded-2xl shrink-0">
                        <i class="text-base fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Hapus Jadwal Periode</h3>
                        <p class="text-xs font-medium text-slate-500 mt-0.5">Aksi ini akan menghapus log jadwal pada
                            site terpilih.</p>
                    </div>
                </div>
                <button type="button" onclick="closeModal('modal-clear')"
                    class="flex items-center justify-center w-8 h-8 transition-colors rounded-lg cursor-pointer text-slate-400 hover:text-slate-600 bg-slate-100 hover:bg-slate-200">&times;</button>
            </div>

            <form action="{{ route('schedule.clear') }}" method="POST" class="space-y-4">
                @csrf
                @method('DELETE')

                <input type="hidden" name="month" value="{{ $month }}">
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="site_id" value="{{ $selectedSiteId }}">

                <div
                    class="p-3.5 text-xs text-rose-800 border border-rose-200/80 bg-rose-50/80 rounded-2xl font-semibold leading-relaxed">
                    Yakin ingin menghapus seluruh log jadwal untuk periode
                    <strong>{{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}</strong>?
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-clear')"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors cursor-pointer">Batal</button>
                    <button type="submit"
                        class="px-6 py-2.5 text-xs font-bold text-white transition-all shadow-md bg-rose-600 rounded-xl hover:bg-rose-700 shadow-rose-600/20 active:scale-95 cursor-pointer">Ya, Hapus Jadwal</button>
                </div>
            </form>
        </div>
    </div>

    @php
        $sitePatternsForJs = $sites->mapWithKeys(function ($s) {
            return [
                $s->id => [
                    'schedule_type' => $s->schedulePattern->schedule_type ?? 'office_hour',
                    'work_days' => $s->schedulePattern->work_days ?? 6,
                    'off_days' => $s->schedulePattern->off_days ?? 2,
                ],
            ];
        });
    @endphp

    <script type="application/json" id="schedule-config">{!! json_encode(['sitePatterns' => $sitePatternsForJs, 'updateUrl' => route('schedule.updateSingle')], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    @vite('resources/js/pages/schedule.js')
@endsection
