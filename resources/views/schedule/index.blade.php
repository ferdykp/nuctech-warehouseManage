@extends('layout.master')

@section('title', 'Manajemen Jadwal Kerja')

@section('content')
    <div class="w-full px-6 py-8">

        {{-- ============ HEADER ============ --}}
        <div class="flex flex-col justify-between gap-3 mb-6 md:flex-row md:items-center">
            <div class="flex flex-col gap-1">
                <h1 class="text-3xl font-extrabold tracking-tighter text-black">Jadwal Kerja Karyawan</h1>
                <p class="text-sm text-gray-500">
                    Pantau jadwal, atur pola kerja per site, dan generate jadwal regu dari satu halaman ini.
                </p>
                @if (Auth::user()->role === 'admin_site')
                    <p class="text-xs font-semibold text-blue-600">
                        <i class="mr-1 fa-solid fa-building-user"></i> Mode Akses: Site Admin
                        ({{ Auth::user()->site->machine_name ?? 'Site Terdaftar' }})
                    </p>
                @endif
            </div>

            {{-- Aksi utama --}}
            <div class="flex flex-wrap gap-2">
                <button type="button" onclick="openModal('modal-pattern')"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-gray-700 transition-colors bg-white border border-gray-300 rounded-xl hover:bg-gray-50">
                    <i class="text-gray-400 fa-solid fa-sliders"></i> Atur Pola Kerja Site
                </button>
                <button type="button" onclick="openModal('modal-generate')"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-bold text-white transition-colors shadow-sm bg-amber-500 hover:bg-amber-600 rounded-xl">
                    <i class="fa-solid fa-plus"></i> Generate Jadwal Regu
                </button>

                <!-- TOMBOL EXPORT EXCEL -->
                <a href="{{ route('schedule.export', ['site_id' => $selectedSiteId ?? 'all', 'month' => $month, 'year' => $year]) }}"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-bold text-white transition-colors shadow-sm bg-emerald-600 hover:bg-emerald-700 rounded-xl"
                    title="Export Jadwal ke Excel">
                    <i class="fa-solid fa-file-excel"></i> Export Excel
                </a>

                <!-- TOMBOL HAPUS / RESET JADWAL -->
                <button type="button" onclick="openModal('modal-clear')"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-bold text-red-600 transition-colors border border-red-200 bg-red-50 hover:bg-red-100 rounded-xl">
                    <i class="fa-solid fa-trash-can"></i> Reset Jadwal
                </button>
            </div>
        </div>

        {{-- ============ ALERTS ============ --}}
        @if (session('success'))
            <div
                class="flex items-start gap-2 p-4 mb-6 text-sm text-green-800 border border-green-200 bg-green-50 rounded-xl">
                <i class="mt-0.5 fa-solid fa-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="flex items-start gap-2 p-4 mb-6 text-sm text-red-800 border border-red-200 bg-red-50 rounded-xl">
                <i class="mt-0.5 fa-solid fa-triangle-exclamation"></i>
                <div>
                    <div class="mb-1 font-semibold">Gagal menyimpan, mohon periksa kembali:</div>
                    <ul class="pl-4 list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- ============ FILTER TAMPILAN ============ --}}
        <form action="{{ route('schedule.index') }}" method="GET"
            class="flex flex-wrap items-end gap-3 p-4 mb-6 bg-white border border-gray-200 shadow-sm rounded-2xl">

            @if (Auth::user()->role === 'superadmin')
                <div class="w-52">
                    <label class="block mb-1 text-xs font-medium text-gray-600">Site Location</label>
                    <select name="site_id" class="w-full p-2 text-sm bg-white border border-gray-300 rounded-xl">
                        <option value="all" {{ ($selectedSiteId ?? 'all') == 'all' ? 'selected' : '' }}>
                            -- Semua Site --</option>
                        @foreach ($sites as $st)
                            <option value="{{ $st->id }}" {{ ($selectedSiteId ?? '') == $st->id ? 'selected' : '' }}>
                                {{ $st->machine_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="w-40">
                <label class="block mb-1 text-xs font-medium text-gray-600">Bulan</label>
                <select name="month" class="w-full p-2 text-sm bg-white border border-gray-300 rounded-xl">
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ sprintf('%02d', $m) }}" {{ $month == $m ? 'selected' : '' }}>
                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                    @endfor
                </select>
            </div>

            <div class="w-28">
                <label class="block mb-1 text-xs font-medium text-gray-600">Tahun</label>
                <select name="year" class="w-full p-2 text-sm bg-white border border-gray-300 rounded-xl">
                    @for ($y = date('Y') - 1; $y <= date('Y') + 2; $y++)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}
                        </option>
                    @endfor
                </select>
            </div>

            <button type="submit"
                class="px-5 py-2 text-sm font-bold text-white transition-all shadow-sm bg-emerald-600 hover:bg-emerald-700 rounded-xl">
                <i class="mr-1 fa-solid fa-filter"></i> Tampilkan
            </button>
        </form>

        {{-- ============ KETERANGAN TANGGAL MERAH BULAN INI ============ --}}
        @if (!empty($holidays))
            <div class="p-4 mb-6 border border-red-100 rounded-lg bg-red-50/60">
                <h6 class="flex items-center gap-2 mb-2 text-xs font-bold tracking-wide text-red-600 uppercase">
                    <i class="fa-solid fa-circle-exclamation"></i> Hari Libur Nasional / Tanggal Merah Bulan Ini
                </h6>
                <ul class="grid grid-cols-1 gap-x-6 gap-y-1 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($holidays as $date => $name)
                        <li class="flex items-baseline gap-2 text-sm">
                            <span
                                class="w-6 font-bold text-right text-red-700 shrink-0">{{ \Carbon\Carbon::parse($date)->format('d') }}</span>
                            <span class="text-gray-700">{{ $name }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif


        {{-- ============ PREVIEW KALENDER ============ --}}
        <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-200 rounded-2xl">
            <div class="flex flex-wrap items-center justify-between gap-3 p-6 border-b border-gray-100 bg-gray-50/50">
                <div class="flex items-center gap-2">
                    <h3 class="font-bold text-gray-800 text-md">Jadwal Kerja Bulanan</h3>
                    @if (Auth::user()->role === 'admin_site')
                        <span
                            class="px-2.5 py-0.5 text-xs font-semibold text-amber-800 bg-amber-100 border border-amber-200 rounded-lg">
                            Site: {{ Auth::user()->site->machine_name ?? 'Terdaftar' }}
                        </span>
                    @endif
                    <span
                        class="px-3 py-1 text-xs font-bold text-blue-700 uppercase border border-blue-200 rounded-full bg-blue-50">
                        {{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-3 text-[10px] font-semibold text-gray-500">
                    <span class="flex items-center gap-1"><span
                            class="inline-block w-2.5 h-2.5 rounded-sm bg-blue-50 border border-blue-200"></span>
                        Shift 1</span>
                    <span class="flex items-center gap-1"><span
                            class="inline-block w-2.5 h-2.5 rounded-sm bg-amber-50 border border-amber-200"></span>
                        Shift 2</span>
                    <span class="flex items-center gap-1"><span
                            class="inline-block w-2.5 h-2.5 rounded-sm bg-purple-50 border border-purple-200"></span>
                        Shift 3</span>
                    <span class="flex items-center gap-1"><span
                            class="inline-block w-2.5 h-2.5 rounded-sm bg-emerald-50 border border-emerald-200"></span>
                        Lainnya</span>
                    <span class="flex items-center gap-1"><span
                            class="inline-block w-2.5 h-2.5 rounded-sm bg-red-50 border border-red-200"></span>
                        Libur</span>
                    <span class="flex items-center gap-1"><span
                            class="inline-block w-2.5 h-2.5 rounded-sm bg-white border border-gray-200"></span>
                        Belum diatur</span>
                    <span class="flex items-center gap-1 text-red-500">
                        <i class="fa-solid fa-circle-exclamation"></i> Tanggal Merah / Libur Nasional</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse table-fixed">
                    <thead class="text-[10px] font-bold text-gray-700 bg-gray-100 border-b border-gray-200">
                        <tr>
                            <th
                                class="w-48 px-4 py-3 text-left sticky left-0 top-0 bg-gray-100 z-20 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                                Karyawan / Site</th>

                            {{-- Header Tanggal Bulanan --}}
                            @foreach ($datesInMonth as $date)
                                @php
                                    $holidayName = $holidays[$date->format('Y-m-d')] ?? null;
                                    $isRedDate = $date->isWeekend() || $holidayName;
                                @endphp
                                <th class="w-12 text-center py-2 border-l border-gray-200 {{ $isRedDate ? 'bg-red-50 text-red-600' : '' }}"
                                    @if ($holidayName) title="{{ $holidayName }}" @endif>
                                    <div>{{ $date->format('d') }}</div>
                                    <div class="text-[8px] font-normal uppercase">{{ $date->translatedFormat('D') }}</div>
                                    @if ($holidayName)
                                        <div class="mt-0.5 truncate px-0.5 text-[7px] font-semibold text-red-500"
                                            title="{{ $holidayName }}">
                                            <i class="fa-solid fa-circle-exclamation"></i>
                                        </div>
                                    @endif
                                </th>
                            @endforeach

                            {{-- Header Summary (Kerja & OFF) diletakkan DILUAR perulangan tanggal --}}
                            <th class="py-2 font-bold text-center text-blue-700 border-l border-gray-200 w-14 bg-blue-50">
                                <div>Kerja</div>
                                <div class="text-[7px] text-blue-500 uppercase">(Hari)</div>
                            </th>
                            <th class="py-2 font-bold text-center text-red-700 border-l border-gray-200 w-14 bg-red-50">
                                <div>OFF</div>
                                <div class="text-[7px] text-red-500 uppercase">(Hari)</div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="text-xs divide-y divide-gray-100">
                        @forelse($employees as $emp)
                            @if (Auth::user()->role === 'superadmin' ||
                                    (Auth::user()->role === 'admin_site' && Auth::user()->site_id === $emp->site_id))
                                <tr class="transition-colors hover:bg-gray-50/50">
                                    <td
                                        class="px-4 py-3 sticky left-0 bg-white font-semibold text-gray-900 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                                        <div class="truncate">{{ $emp->name }}</div>
                                        <div class="text-[9px] font-normal text-gray-400 truncate">
                                            {{ $emp->site->machine_name ?? '-' }}</div>
                                    </td>

                                    @php
                                        $totalWorkCount = 0;
                                        $totalOffCount = 0;
                                    @endphp

                                    @foreach ($datesInMonth as $date)
                                        @php
                                            $schedule = $emp->schedules->firstWhere('date', $date->format('Y-m-d'));
                                            $shiftName = $schedule?->shift?->shift_name;

                                            $badgeColor = 'bg-white text-gray-300 border-gray-100';
                                            $label = '-';

                                            if ($schedule && $schedule->shift) {
                                                if ($schedule->shift->is_off) {
                                                    $badgeColor = 'bg-red-50 text-red-700 border-red-100';
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
                                                        $badgeColor = 'bg-blue-50 text-blue-700 border-blue-200';
                                                    } elseif (str_contains(strtolower($shiftName), '2')) {
                                                        $badgeColor = 'bg-amber-50 text-amber-700 border-amber-200';
                                                    } elseif (str_contains(strtolower($shiftName), '3')) {
                                                        $badgeColor = 'bg-purple-50 text-purple-700 border-purple-200';
                                                    } else {
                                                        $badgeColor =
                                                            'bg-emerald-50 text-emerald-700 border-emerald-200';
                                                    }
                                                }
                                            }
                                        @endphp
                                        <td class="p-1 text-center border-l border-gray-100">
                                            <button type="button"
                                                onclick="openEditShiftModal({{ $emp->id }}, '{{ addslashes($emp->name) }}', '{{ $date->format('Y-m-d') }}', '{{ $schedule?->shift_id ?? '' }}')"
                                                class="w-full py-1 text-[9px] font-bold border rounded-md transition-transform active:scale-95 cursor-pointer {{ $badgeColor }}"
                                                title="Klik untuk ubah shift ({{ $shiftName ?? 'Belum Diatur' }})">
                                                {{ $label }}
                                            </button>
                                        </td>
                                    @endforeach

                                    <!-- Tampilkan Total Hari Kerja -->
                                    <td
                                        class="px-2 py-3 font-bold text-center text-blue-700 border-l border-gray-200 bg-blue-50/30">
                                        {{ $totalWorkCount }}
                                    </td>

                                    <!-- Tampilkan Total OFF -->
                                    <td
                                        class="px-2 py-3 font-bold text-center text-red-700 border-l border-gray-200 bg-red-50/30">
                                        {{ $totalOffCount }}
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="{{ count($datesInMonth) + 3 }}"
                                    class="px-6 py-16 text-center text-gray-400">
                                    <i class="block mb-2 text-2xl fa-solid fa-calendar-xmark"></i>
                                    Belum ada data jadwal untuk site dan periode ini.<br>
                                    <button type="button" onclick="openModal('modal-generate')"
                                        class="mt-3 text-xs font-semibold text-amber-600 hover:underline">
                                        Generate jadwal sekarang &rarr;
                                    </button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- =========================================================== --}}
    {{-- MODAL 1: PENGATURAN POLA KERJA SITE                          --}}
    {{-- =========================================================== --}}
    <div id="modal-pattern" class="fixed inset-0 z-50 items-center justify-center hidden p-4 bg-black/50 modal-overlay"
        onclick="if(event.target===this) closeModal('modal-pattern')">
        <div class="w-full max-w-2xl p-6 bg-white rounded-2xl shadow-xl max-h-[85vh] overflow-y-auto">
            <div class="flex items-start justify-between mb-1">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Atur Pola Kerja Site</h3>
                    <p class="text-sm text-gray-500">Pola ini dipakai sebagai dasar perhitungan saat generate jadwal regu.
                    </p>
                </div>
                <button type="button" onclick="closeModal('modal-pattern')"
                    class="p-2 text-gray-400 rounded-lg hover:bg-gray-100 hover:text-gray-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="mt-4 space-y-4">
                @foreach ($sites as $site)
                    @if (Auth::user()->role === 'superadmin' || (Auth::user()->role === 'admin_site' && Auth::user()->site_id === $site->id))
                        <form action="{{ route('schedule.site.update', $site->id) }}" method="POST"
                            class="p-4 border border-gray-100 bg-gray-50/70 rounded-xl">
                            @csrf
                            <div class="mb-3 text-sm font-bold text-gray-800">{{ $site->machine_name }}</div>
                            <div class="space-y-3">
                                <div>
                                    <label class="block mb-1 text-xs font-medium text-gray-600">Tipe Pola</label>
                                    <select name="schedule_type"
                                        class="w-full p-2 text-xs bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500"
                                        onchange="toggleShiftInputs(this, '{{ $site->id }}')">
                                        <option value="office_hour"
                                            {{ ($site->schedulePattern->schedule_type ?? '') == 'office_hour' ? 'selected' : '' }}>
                                            Office Hour (Senin-Jumat)</option>
                                        <option value="shift_rotation"
                                            {{ ($site->schedulePattern->schedule_type ?? '') == 'shift_rotation' ? 'selected' : '' }}>
                                            Rotasi Shift Dinamis</option>
                                    </select>
                                </div>

                                <div id="rotation-fields-{{ $site->id }}"
                                    class="{{ ($site->schedulePattern->schedule_type ?? '') == 'shift_rotation' ? '' : 'hidden' }} grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-[10px] font-semibold text-gray-500 block">Masuk (Hari)</label>
                                        <input type="number" name="work_days"
                                            value="{{ $site->schedulePattern->work_days ?? 6 }}" min="1"
                                            class="w-full p-2 text-xs bg-white border border-gray-300 rounded-lg">
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-semibold text-gray-500 block">Libur (Hari)</label>
                                        <input type="number" name="off_days"
                                            value="{{ $site->schedulePattern->off_days ?? 2 }}" min="1"
                                            class="w-full p-2 text-xs bg-white border border-gray-300 rounded-lg">
                                    </div>
                                </div>

                                <button type="submit"
                                    class="w-full py-2 text-xs font-semibold text-white transition-colors bg-blue-600 rounded-lg shadow-xs hover:bg-blue-700">
                                    Simpan Pola {{ $site->machine_name }}
                                </button>
                            </div>
                        </form>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    {{-- =========================================================== --}}
    {{-- MODAL 2: GENERATE JADWAL REGU                                --}}
    {{-- =========================================================== --}}
    <div id="modal-generate" class="fixed inset-0 z-50 items-center justify-center hidden p-4 bg-black/50 modal-overlay"
        onclick="if(event.target===this) closeModal('modal-generate')">
        <div class="w-full max-w-3xl p-6 bg-white rounded-2xl shadow-xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-start justify-between mb-1">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Generate Jadwal Regu</h3>
                    <p class="text-sm text-gray-500">Ikuti 3 langkah berikut untuk membuat jadwal karyawan secara otomatis.
                    </p>
                </div>
                <button type="button" onclick="closeModal('modal-generate')"
                    class="p-2 text-gray-400 rounded-lg hover:bg-gray-100 hover:text-gray-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="{{ route('schedule.generate') }}" method="POST" class="mt-4 space-y-5">
                @csrf

                {{-- STEP 1 --}}
                <div class="p-4 border border-amber-100 bg-amber-50/40 rounded-xl">
                    <div class="flex items-center gap-2 mb-3">
                        <span
                            class="flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white bg-amber-500 rounded-full">1</span>
                        <span class="text-xs font-bold tracking-wide text-gray-700 uppercase">Tentukan Periode</span>
                    </div>
                    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                        <div>
                            <label class="block mb-1 text-xs font-medium text-gray-600">Bulan</label>
                            <select name="month" class="w-full p-2 text-sm bg-white border border-gray-300 rounded-xl">
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ sprintf('%02d', $m) }}"
                                        {{ old('month', $month) == $m ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label class="block mb-1 text-xs font-medium text-gray-600">Tahun</label>
                            <select name="year" class="w-full p-2 text-sm bg-white border border-gray-300 rounded-xl">
                                @for ($y = date('Y') - 1; $y <= date('Y') + 2; $y++)
                                    <option value="{{ $y }}" {{ old('year', $year) == $y ? 'selected' : '' }}>
                                        {{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label class="block mb-1 text-xs font-bold text-gray-700">Mulai Tgl Masuk</label>
                            <input type="number" name="start_day" value="{{ old('start_day', 1) }}" min="1"
                                max="31" class="w-full p-2 text-sm bg-white border border-gray-300 rounded-xl"
                                required>
                        </div>
                        <div>
                            <label class="block mb-1 text-xs font-medium text-gray-600">Durasi Shift (Hari)</label>
                            <input type="number" name="shift_duration" value="{{ old('shift_duration', 2) }}"
                                min="1" class="w-full p-2 text-sm bg-white border border-gray-300 rounded-xl"
                                required>
                        </div>
                    </div>
                    <p class="mt-2 text-[10px] text-gray-500">"Durasi Shift" menentukan berapa hari berturut-turut seorang
                        karyawan berada di shift yang sama sebelum berpindah shift.</p>
                </div>

                {{-- STEP 2 --}}
                <div class="p-4 border border-blue-100 bg-blue-50/40 rounded-xl">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span
                                class="flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white bg-blue-500 rounded-full">2</span>
                            <span class="text-xs font-bold tracking-wide text-gray-700 uppercase">Pilih Karyawan</span>
                        </div>
                        <label class="flex items-center gap-1.5 text-xs font-medium text-gray-600 cursor-pointer">
                            <input type="checkbox" onchange="toggleAllEmployees(this.checked)"
                                class="w-3.5 h-3.5 text-blue-600 border-gray-300 rounded">
                            Pilih Semua
                        </label>
                    </div>

                    <input type="text" id="employee-search" oninput="filterEmployees()"
                        placeholder="Cari nama karyawan..."
                        class="w-full p-2 mb-3 text-xs bg-white border border-gray-300 rounded-xl">

                    <div
                        class="grid grid-cols-2 md:grid-cols-3 gap-3 p-3 bg-white border border-gray-200 rounded-xl max-h-[180px] overflow-y-auto">
                        @foreach ($employees as $emp)
                            @if (Auth::user()->role === 'superadmin' ||
                                    (Auth::user()->role === 'admin_site' && Auth::user()->site_id === $emp->site_id))
                                <label data-name="{{ strtolower($emp->name) }}"
                                    class="flex items-center gap-2 p-1 text-xs font-medium text-gray-700 rounded cursor-pointer employee-option hover:bg-gray-50">
                                    <input type="checkbox" name="employee_ids[]" value="{{ $emp->id }}"
                                        class="w-4 h-4 text-blue-600 border-gray-300 rounded employee-checkbox"
                                        {{ in_array($emp->id, old('employee_ids', [])) ? 'checked' : '' }}>
                                    <div class="truncate">
                                        <span class="font-bold">{{ $emp->name }}</span>
                                        <span class="text-[10px] block text-gray-400">Site:
                                            {{ $emp->site->machine_name ?? '-' }}</span>
                                    </div>
                                </label>
                            @endif
                        @endforeach
                    </div>
                    <p id="employee-count" class="mt-2 text-[10px] font-semibold text-gray-500"></p>
                </div>

                {{-- STEP 3 --}}
                <div class="p-4 border border-purple-100 bg-purple-50/40 rounded-xl">
                    <div class="flex items-center gap-2 mb-3">
                        <span
                            class="flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white bg-purple-500 rounded-full">3</span>
                        <span class="text-xs font-bold tracking-wide text-gray-700 uppercase">Atur Rotasi Shift</span>
                    </div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="block mb-1 text-xs font-medium text-gray-600">Mulai Dari Shift</label>
                            <select name="start_shift_id"
                                class="w-full p-2 text-sm bg-white border border-gray-300 rounded-xl" required>
                                @foreach (App\Models\Shift::where('is_off', false)->orderBy('start_time', 'asc')->get() as $sf)
                                    <option value="{{ $sf->id }}"
                                        {{ old('start_shift_id') == $sf->id ? 'selected' : '' }}>
                                        {{ $sf->shift_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block mb-1 text-xs font-medium text-gray-600">Shift yang Dipakai dalam
                                Rotasi</label>
                            <div class="flex flex-wrap gap-3 p-2 bg-white border border-gray-200 rounded-xl">
                                @foreach (App\Models\Shift::where('is_off', false)->orderBy('start_time', 'asc')->get() as $sf)
                                    <label
                                        class="flex items-center gap-1.5 text-xs font-medium text-gray-700 cursor-pointer">
                                        <input type="checkbox" name="active_shifts[]" value="{{ $sf->id }}"
                                            {{ old('active_shifts') ? (in_array($sf->id, old('active_shifts')) ? 'checked' : '') : 'checked' }}
                                            class="w-3.5 h-3.5 text-blue-600">
                                        {{ $sf->shift_name }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('modal-generate')"
                        class="px-4 py-2 text-sm font-semibold text-gray-600 rounded-xl hover:bg-gray-100">
                        Batal
                    </button>
                    <button type="submit"
                        class="px-5 py-2 text-sm font-bold text-white transition-all shadow-md bg-amber-500 hover:bg-amber-600 rounded-xl">
                        <i class="mr-1 fa-solid fa-wand-magic-sparkles"></i> Generate Jadwal
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- =========================================================== --}}
    {{-- MODAL 3: EDIT SHIFT INDIVIDUAL PER TANGGAL                   --}}
    {{-- =========================================================== --}}
    <div id="modal-edit-shift" class="fixed inset-0 z-50 items-center justify-center hidden p-4 bg-black/50 modal-overlay"
        onclick="if(event.target===this) closeModal('modal-edit-shift')">
        <div class="w-full max-w-sm p-6 bg-white shadow-xl rounded-2xl">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h3 class="text-base font-bold text-gray-900">Ubah Shift Karyawan</h3>
                    <p id="edit-shift-subtitle" class="text-xs text-gray-500"></p>
                </div>
                <button type="button" onclick="closeModal('modal-edit-shift')"
                    class="text-gray-400 hover:text-gray-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="form-quick-edit-shift" onsubmit="submitQuickEditShift(event)" class="space-y-4">
                @csrf
                <input type="hidden" id="edit_employee_id" name="employee_id">
                <input type="hidden" id="edit_date" name="date">

                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-600">Pilih Shift</label>
                    <select id="edit_shift_id" name="shift_id"
                        class="w-full p-2 text-xs bg-white border border-gray-300 rounded-lg" required>
                        @foreach (App\Models\Shift::orderBy('start_time', 'asc')->get() as $sf)
                            <option value="{{ $sf->id }}">{{ $sf->shift_name }} {{ $sf->is_off ? '(LIBUR)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('modal-edit-shift')"
                        class="px-3 py-1.5 text-xs font-semibold text-gray-600 rounded-lg hover:bg-gray-100">Batal</button>
                    <button type="submit"
                        class="px-4 py-1.5 text-xs font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700">Simpan
                        Shift</button>
                </div>
            </form>
        </div>
    </div>

    {{-- =========================================================== --}}
    {{-- MODAL 4: HAPUS / RESET JADWAL PERIODE                       --}}
    {{-- =========================================================== --}}
    <div id="modal-clear" class="fixed inset-0 z-50 items-center justify-center hidden p-4 bg-black/50 modal-overlay"
        onclick="if(event.target===this) closeModal('modal-clear')">
        <div class="w-full max-w-md p-6 bg-white shadow-xl rounded-2xl">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h3 class="text-base font-bold text-red-600">Reset Jadwal Periode</h3>
                    <p class="text-xs text-gray-500">Tindakan ini akan menghapus seluruh data jadwal pada site dan bulan
                        terpilih.</p>
                </div>
                <button type="button" onclick="closeModal('modal-clear')" class="text-gray-400 hover:text-gray-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="{{ route('schedule.clear') }}" method="POST" class="space-y-4">
                @csrf
                @method('DELETE')

                <input type="hidden" name="month" value="{{ $month }}">
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="site_id" value="{{ $selectedSiteId }}">

                <div class="p-3 text-xs text-red-700 border border-red-100 bg-red-50 rounded-xl">
                    <i class="mr-1 fa-solid fa-triangle-exclamation"></i>
                    Apakah Anda yakin ingin menghapus jadwal bulan
                    <strong>{{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}</strong>?
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('modal-clear')"
                        class="px-4 py-2 text-xs font-semibold text-gray-600 rounded-lg hover:bg-gray-100">Batal</button>
                    <button type="submit"
                        class="px-4 py-2 text-xs font-bold text-white bg-red-600 rounded-lg hover:bg-red-700">Ya, Hapus
                        Jadwal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) {
            const modal = document.getElementById(id);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay').forEach(function(modal) {
                    if (!modal.classList.contains('hidden')) closeModal(modal.id);
                });
            }
        });

        function toggleShiftInputs(selectElement, siteId) {
            const fields = document.getElementById(`rotation-fields-${siteId}`);
            if (selectElement.value === 'shift_rotation') {
                fields.classList.remove('hidden');
            } else {
                fields.classList.add('hidden');
            }
        }

        function filterEmployees() {
            const q = document.getElementById('employee-search').value.toLowerCase();
            document.querySelectorAll('.employee-option').forEach(function(el) {
                el.style.display = el.dataset.name.includes(q) ? '' : 'none';
            });
        }

        function toggleAllEmployees(checked) {
            document.querySelectorAll('.employee-option').forEach(function(el) {
                if (el.style.display !== 'none') {
                    el.querySelector('.employee-checkbox').checked = checked;
                }
            });
            updateEmployeeCount();
        }

        function updateEmployeeCount() {
            const total = document.querySelectorAll('.employee-checkbox:checked').length;
            document.getElementById('employee-count').textContent =
                total > 0 ? `${total} karyawan terpilih` : 'Belum ada karyawan dipilih';
        }

        document.querySelectorAll('.employee-checkbox').forEach(function(cb) {
            cb.addEventListener('change', updateEmployeeCount);
        });
        updateEmployeeCount();

        document.addEventListener('DOMContentLoaded', function() {
            @if (
                $errors->has('employee_ids') ||
                    $errors->has('start_shift_id') ||
                    $errors->has('active_shifts') ||
                    $errors->has('start_day') ||
                    $errors->has('shift_duration'))
                openModal('modal-generate');
            @elseif ($errors->has('schedule_type') || $errors->has('work_days') || $errors->has('off_days'))
                openModal('modal-pattern');
            @endif
        });

        function openEditShiftModal(empId, empName, dateStr, currentShiftId) {
            document.getElementById('edit_employee_id').value = empId;
            document.getElementById('edit_date').value = dateStr;
            document.getElementById('edit-shift-subtitle').innerText = `${empName} (${dateStr})`;

            if (currentShiftId) {
                document.getElementById('edit_shift_id').value = currentShiftId;
            }
            openModal('modal-edit-shift');
        }

        function submitQuickEditShift(e) {
            e.preventDefault();
            const form = document.getElementById('form-quick-edit-shift');
            const formData = new FormData(form);

            fetch('{{ route('schedule.updateSingle') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Gagal mengubah shift');
                    }
                })
                .catch(err => {
                    alert('Terjadi kesalahan jaringan.');
                });
        }
    </script>
@endsection
