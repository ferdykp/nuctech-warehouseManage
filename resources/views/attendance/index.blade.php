@extends('layout.master')

@section('title', 'Dashboard Rekap Absensi')

@section('content')
    <div class="w-full px-6 py-8 mx-auto space-y-6">

        {{-- ============ FLASH MESSAGES ============ --}}
        @if (session('success'))
            <div
                class="flex items-center gap-2 px-4 py-3 text-sm font-medium text-green-800 border border-green-200 rounded-lg bg-green-50">
                <i class="text-lg bi bi-check-circle-fill"></i> {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div
                class="flex items-center gap-2 px-4 py-3 text-sm font-medium text-red-800 border border-red-200 rounded-lg bg-red-50">
                <i class="text-lg bi bi-exclamation-triangle-fill"></i> {{ session('error') }}
            </div>
        @endif

        {{-- ============ PAGE HEADER ============ --}}
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center text-white bg-blue-600 rounded-lg w-11 h-11 shrink-0">
                    <i class="text-xl bi bi-calendar-check"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Rekap Absensi</h1>
                    <p class="text-sm text-gray-500">Kelola dan pantau kehadiran karyawan per cabang & periode.</p>
                </div>
            </div>
            @if (Auth::user()->role === 'admin_site')
                <span class="px-3 py-1 text-xs font-bold text-blue-700 border border-blue-200 rounded-full bg-blue-50">
                    <i class="mr-1 fa-solid fa-building-user"></i> Site Admin: {{ Auth::user()->site->machine_name ?? '-' }}
                </span>
            @endif
        </div>

        {{-- ============ STEP 1: FILTER / WORKSPACE ============ --}}
        <div class="p-6 bg-white rounded-lg shadow-md">
            <h5 class="flex items-center gap-2 mb-4 text-base font-bold text-gray-800 sm:text-lg">
                <span
                    class="flex items-center justify-center w-6 h-6 text-xs font-bold text-white bg-blue-600 rounded-full shrink-0">1</span>
                Pilih Cabang &amp; Periode
            </h5>
            <form action="{{ route('attendance.index') }}" method="GET" id="mainWorkspaceFilterForm"
                class="grid items-end grid-cols-1 gap-4 md:grid-cols-12">

                <div class="md:col-span-5">
                    <label for="main_branch_select" class="block mb-1.5 text-xs font-medium text-gray-500">
                        Target Kantor Cabang (Branch)
                    </label>

                    @if (Auth::user()->role === 'superadmin')
                        <select name="site_id" id="main_branch_select"
                            class="w-full px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            required onchange="handleFilterChange()">
                            <option value="">-- Pilih Kantor Branch --</option>
                            @foreach ($sites as $site)
                                <option value="{{ $site->id }}"
                                    {{ request('site_id', $siteId) == $site->id ? 'selected' : '' }}>
                                    {{ $site->machine_name }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <input type="hidden" name="site_id" id="main_branch_select" value="{{ Auth::user()->site_id }}">
                        <input type="text" value="{{ Auth::user()->site->machine_name ?? 'Site Terdaftar' }}"
                            class="w-full px-3 py-2 text-sm font-bold text-gray-700 bg-gray-100 border border-gray-300 rounded-lg cursor-not-allowed"
                            readonly>
                    @endif
                </div>

                <div class="md:col-span-4">
                    <label class="block mb-1.5 text-xs font-medium text-gray-500">Periode Rekap</label>
                    <div class="flex gap-2">
                        @php
                            $requestMonthRaw = request('month', date('Y-m'));
                            $selectedMonth = date('m', strtotime($requestMonthRaw . '-01'));
                            $selectedYear = date('Y', strtotime($requestMonthRaw . '-01'));
                            $daftarBulan = [
                                '01' => 'Januari',
                                '02' => 'Februari',
                                '03' => 'Maret',
                                '04' => 'April',
                                '05' => 'Mei',
                                '06' => 'Juni',
                                '07' => 'Juli',
                                '08' => 'Agustus',
                                '09' => 'September',
                                '10' => 'Oktober',
                                '11' => 'November',
                                '12' => 'Desember',
                            ];
                        @endphp
                        <select id="filter_bulan" aria-label="Bulan"
                            class="w-full px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            onchange="handleFilterChange()" required>
                            @foreach ($daftarBulan as $angka => $nama)
                                <option value="{{ $angka }}" {{ $selectedMonth == $angka ? 'selected' : '' }}>
                                    {{ $nama }}</option>
                            @endforeach
                        </select>
                        <select id="filter_tahun" aria-label="Tahun"
                            class="w-full px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            onchange="handleFilterChange()" required>
                            @for ($year = date('Y') - 2; $year <= date('Y') + 2; $year++)
                                <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>
                                    {{ $year }}</option>
                            @endfor
                        </select>
                    </div>
                    <input type="hidden" name="month" id="main_month_hidden" value="{{ $requestMonthRaw }}">
                </div>

                <div class="flex gap-2 md:col-span-3">
                    <button type="submit"
                        class="flex items-center justify-center flex-grow gap-1 px-4 py-2 text-sm font-medium text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700">
                        <i class="bi bi-search"></i> Buka Data
                    </button>
                    @php $activeSiteId = request('site_id', $siteId); @endphp
                    <a id="exportBtn"
                        href="{{ route('attendance.export', ['site_id' => $activeSiteId, 'month' => request('month', date('Y-m'))]) }}"
                        title="Export ke Excel"
                        class="px-4 py-2 rounded-lg border font-medium text-sm flex items-center justify-center transition-colors {{ !$activeSiteId ? 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed pointer-events-none' : 'bg-green-600 hover:bg-green-700 text-white border-green-600' }}">
                        <i class="fa-solid fa-file-excel"></i>
                    </a>
                </div>
            </form>
        </div>

        {{-- ============ KETERANGAN TANGGAL MERAH BULAN INI ============ --}}
        @if (!empty($holidays))
            <div class="p-4 border border-red-100 rounded-lg bg-red-50/60">
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

        {{-- ============ STEP 2: MASS ATTENDANCE INPUT ============ --}}
        @php $currentSiteId = request('site_id', $siteId); @endphp
        @if ($currentSiteId)
            <div class="p-6 bg-white border border-gray-100 rounded-lg shadow-sm">
                <div
                    class="flex flex-col gap-3 pb-3 mb-4 border-b border-gray-100 sm:flex-row sm:justify-between sm:items-center">
                    <div>
                        <h5 class="flex items-center gap-2 text-base font-bold text-gray-800 sm:text-lg">
                            <span
                                class="flex items-center justify-center w-6 h-6 text-xs font-bold text-white bg-blue-600 rounded-full shrink-0">2</span>
                            Isi Kehadiran Karyawan
                        </h5>
                        <small class="text-xs text-gray-500 ms-8">Centang sesi kerja tiap karyawan per tanggal, atau pakai
                            kehadiran otomatis di bawah</small>
                    </div>
                    <div class="flex items-center gap-3 px-3 py-2 border border-gray-200 rounded-lg bg-gray-50">
                        <label for="autoFullAttendance" class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="autoFullAttendance"
                                {{ request('auto_full', 'true') === 'true' ? 'checked' : '' }}
                                onchange="toggleManualInput(this.checked)" class="sr-only peer">
                            <div
                                class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600">
                            </div>
                            <span class="text-xs font-semibold text-gray-500 select-none ms-2">
                                Set Kehadiran Penuh Otomatis
                            </span>
                        </label>
                    </div>
                </div>

                <p class="mb-4 text-xs text-gray-400 ms-8">
                    <i class="fa-solid fa-circle-info"></i>
                    Saat aktif: semua karyawan otomatis dianggap hadir berdasarkan jadwal shift yang dibuat di modul
                    Schedule.
                </p>

                <div class="flex flex-wrap items-center gap-3 mb-4 text-[11px] font-semibold text-gray-500 ms-8">
                    <span class="flex items-center gap-1"><span class="text-blue-600">S1</span> = Shift 1</span>
                    <span class="flex items-center gap-1"><span class="text-yellow-600">S2</span> = Shift 2</span>
                    <span class="flex items-center gap-1"><span class="text-red-600">S3</span> = Shift 3</span>
                    <span class="flex items-center gap-1 text-red-500">
                        <i class="fa-solid fa-circle-exclamation"></i> Tanggal merah / Libur
                    </span>
                </div>

                <form action="{{ route('attendance.store') }}" method="POST" id="massAttendanceForm">
                    @csrf
                    <input type="hidden" name="month" id="form_month_hidden"
                        value="{{ request('month', date('Y-m')) }}">
                    <input type="hidden" name="site_id" value="{{ $currentSiteId }}">
                    <input type="hidden" name="auto_full" id="auto_full_hidden"
                        value="{{ request('auto_full', 'true') }}">

                    <div id="employee_loading"
                        class="flex items-center justify-center gap-2 py-8 text-sm font-medium text-gray-400">
                        <i class="text-lg animate-spin bi bi-arrow-repeat"></i> Memuat data karyawan...
                    </div>

                    <div id="employee_fields" class="space-y-3"></div>

                    <div class="flex items-center justify-between pt-4 mt-6 border-t border-gray-100">
                        <span class="text-xs text-gray-400" id="employeeCountLabel"></span>
                        <button type="submit"
                            class="flex items-center gap-2 px-5 py-2.5 ml-auto text-sm font-semibold text-white transition-colors bg-blue-600 rounded-lg shadow-sm hover:bg-blue-700">
                            <i class="bi bi-cloud-arrow-up"></i> Simpan &amp; Perbarui Absensi
                        </button>
                    </div>
                </form>
            </div>
        @endif

        {{-- ============ MODAL PLOT KALENDER ============ --}}
        <div id="plotCalendarModal" class="fixed inset-0 z-50 items-center justify-center hidden p-4 app-modal-overlay">
            <div class="w-full max-w-md mx-auto app-modal-panel">
                <div class="overflow-hidden bg-white border border-gray-100 shadow-xl rounded-xl">
                    <div class="flex items-center justify-between px-4 py-3 bg-gray-900">
                        <h5 class="flex items-center gap-2 text-sm font-bold text-white">
                            <i class="text-blue-400 bi bi-calendar3"></i> Plot Sesi Absen:
                            <span id="modalEmployeeName" class="text-blue-200"></span>
                        </h5>
                        <button type="button"
                            class="text-xl leading-none text-gray-400 transition-colors hover:text-white focus:outline-none"
                            onclick="closeModal('plotCalendarModal')" aria-label="Close">&times;</button>
                    </div>
                    <div class="p-4 bg-gray-50">
                        <div class="bg-white border border-gray-200 rounded-lg overflow-y-auto max-h-[380px] shadow-sm">
                            <table class="w-full text-sm text-center border-collapse">
                                <thead class="sticky top-0 font-bold text-gray-600 bg-gray-100 border-b border-gray-200">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Tanggal</th>
                                        <th class="px-3 py-2 text-blue-600">S1</th>
                                        <th class="px-3 py-2 text-yellow-600">S2</th>
                                        <th class="px-3 py-2 text-red-600">S3</th>
                                    </tr>
                                </thead>
                                <tbody id="calendarGridBody" class="text-gray-700 divide-y divide-gray-100"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="px-4 py-3 border-t border-gray-100 bg-gray-50">
                        <button type="button"
                            class="flex items-center justify-center w-full gap-2 py-2 text-sm font-semibold text-white transition-colors bg-blue-600 rounded-lg shadow-sm hover:bg-blue-700"
                            onclick="closeModal('plotCalendarModal')">
                            <i class="bi bi-check2-circle"></i> Selesai Plot
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ MODAL EDIT KARYAWAN ============ --}}
        <div id="editEmployeeModal" class="fixed inset-0 z-50 items-center justify-center hidden p-4 app-modal-overlay"
            onclick="if(event.target===this) closeModal('editEmployeeModal')">
            <div class="w-full max-w-md mx-auto app-modal-panel">
                <div class="overflow-hidden bg-white border border-gray-100 shadow-xl rounded-xl">
                    <form id="formEditEmployee" action="" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="flex items-center justify-between px-4 py-3 bg-gray-900">
                            <h5 class="flex items-center gap-2 text-sm font-bold text-white">
                                <i class="text-blue-400 bi bi-pencil-square"></i> Edit Data Karyawan
                            </h5>
                            <button type="button"
                                class="text-xl leading-none text-gray-400 transition-colors hover:text-white focus:outline-none"
                                onclick="closeModal('editEmployeeModal')" aria-label="Close">&times;</button>
                        </div>
                        <div class="p-5 space-y-4">
                            <div>
                                <label class="block mb-1.5 text-xs font-medium text-gray-500">Nama Karyawan</label>
                                <input type="text" name="name" id="edit_employee_name" required
                                    class="w-full px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <input type="hidden" name="site_id" id="edit_employee_site_id">
                        </div>
                        <div class="flex justify-end gap-2 px-4 py-3 border-t border-gray-100 bg-gray-50">
                            <button type="button"
                                class="px-4 py-2 text-sm font-medium text-gray-600 transition-colors bg-white border border-gray-300 rounded-lg hover:bg-gray-100"
                                onclick="closeModal('editEmployeeModal')">Batal</button>
                            <button type="submit"
                                class="flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700">
                                <i class="bi bi-save"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ============ MODAL RIWAYAT PLOT ============ --}}
        <div id="detailPlotModal" class="fixed inset-0 z-50 items-center justify-center hidden p-4 app-modal-overlay"
            onclick="if(event.target===this) closeModal('detailPlotModal')">
            <div class="w-full max-w-md mx-auto app-modal-panel">
                <div class="overflow-hidden bg-white border border-gray-100 shadow-xl rounded-xl">
                    <div class="flex items-center justify-between px-4 py-3 bg-gray-900">
                        <h5 class="flex items-center gap-2 text-sm font-bold text-white">
                            <i class="text-cyan-400 bi bi-clock-history"></i> Riwayat Plot:
                            <span id="detailEmployeeName" class="text-cyan-200"></span>
                        </h5>
                        <button type="button"
                            class="text-xl leading-none text-gray-400 transition-colors hover:text-white focus:outline-none"
                            onclick="closeModal('detailPlotModal')" aria-label="Close">&times;</button>
                    </div>
                    <div class="p-0 bg-gray-50">
                        <ul id="detailActiveDatesList"
                            class="max-h-[400px] overflow-y-auto divide-y divide-gray-100 bg-white"></ul>
                    </div>
                    <div class="px-4 py-3 border-t border-gray-100 bg-gray-50">
                        <button type="button"
                            class="w-full py-2 text-sm font-semibold text-gray-600 transition-colors bg-white border border-gray-300 rounded-lg hover:bg-gray-100"
                            onclick="closeModal('detailPlotModal')">Tutup</button>
                    </div>
                </div>
            </div>
        </div>

        <form id="formDeleteEmployee" action="" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>

        {{-- ============ STEP 3: REPORT TABLE ============ --}}
        <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-2xl">
            <div
                class="flex flex-col gap-4 p-6 border-b border-gray-100 bg-gray-50/50 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-3">
                    <span
                        class="flex items-center justify-center text-xs font-bold text-white bg-blue-600 rounded-full shadow-sm w-7 h-7 shrink-0">
                        3
                    </span>
                    <div>
                        <h5 class="text-base font-bold text-gray-900">Tinjau Rekap Kehadiran</h5>
                        <p class="text-xs text-gray-500">Ringkasan rasio hari kerja dan total kehadiran sesi karyawan.</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="relative min-w-[200px]">
                        <select id="recapMonthFilter" onchange="filterRecapTable()"
                            class="block w-full py-2 pr-8 text-xs font-semibold text-gray-700 transition-all bg-white border border-gray-200 cursor-pointer pl-9 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-2xs">
                            <option value="all">-- Semua Bulan --</option>
                            @php
                                $availableMonths = $attendances->pluck('month')->unique()->sortDesc();
                            @endphp
                            @foreach ($availableMonths as $m)
                                <option value="{{ $m }}"
                                    {{ request('month', date('Y-m')) == $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::parse($m)->translatedFormat('F Y') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <span id="recapCountBadge"
                        class="hidden md:inline-flex items-center px-3 py-1.5 text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-100 rounded-xl">
                        0 Data
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead
                        class="text-[11px] font-bold text-gray-500 uppercase tracking-wider bg-gray-100/70 border-b border-gray-200/80">
                        <tr>
                            <th scope="col" class="px-6 py-3.5">Kantor Branch / Site</th>
                            <th scope="col" class="px-6 py-3.5">Nama Karyawan</th>
                            <th scope="col" class="px-6 py-3.5 text-center">Periode Bulan</th>
                            <th scope="col" class="px-6 py-3.5 text-center">Hari Kerja Efektif</th>
                            <th scope="col" class="px-6 py-3.5 text-center">Total Sesi Hadir</th>
                            <th scope="col" class="px-6 py-3.5 text-right">Rasio Kehadiran</th>
                            <th scope="col" class="px-6 py-3.5 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="recapTableBody" class="text-xs divide-y divide-gray-100">
                        @forelse($attendances as $row)
                            @if (Auth::user()->role === 'superadmin' ||
                                    (Auth::user()->role === 'admin_site' && Auth::user()->site_id === $row->employee->site_id))
                                @php
                                    $percentage =
                                        $row->working_days > 0
                                            ? round(($row->attendance_count / $row->working_days) * 100)
                                            : 0;

                                    $progressBg = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                    if ($percentage < 50) {
                                        $progressBg = 'bg-red-50 text-red-700 border-red-200';
                                    } elseif ($percentage < 80) {
                                        $progressBg = 'bg-amber-50 text-amber-700 border-amber-200';
                                    }
                                @endphp
                                <tr class="transition-colors recap-row hover:bg-gray-50/80"
                                    data-month="{{ $row->month }}">
                                    <td class="px-6 py-4 font-medium text-gray-900">
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-semibold bg-gray-100 text-gray-700 border border-gray-200 rounded-md">
                                            <i class="fa-solid fa-location-dot text-gray-400 text-[10px]"></i>
                                            {{ $row->employee->site->machine_name ?? '-' }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="text-sm font-bold text-gray-900">
                                            {{ $row->employee->name ?? 'Karyawan Terhapus' }}</div>
                                        <div class="text-[10px] text-gray-400">{{ $row->employee->position ?? 'Staff' }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 font-semibold text-center text-gray-600">
                                        {{ \Carbon\Carbon::parse($row->month)->translatedFormat('F Y') }}
                                    </td>

                                    <td class="px-6 py-4 font-medium text-center text-gray-600">
                                        {{ $row->working_days }} Hari
                                    </td>

                                    <td class="px-6 py-4 text-center">
                                        <span class="font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded text-xs">
                                            {{ $row->attendance_count }} Sesi
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 text-right">
                                        <div class="inline-flex items-center gap-2">
                                            <span class="font-extrabold text-gray-800">
                                                {{ $row->attendance_count }}/{{ $row->working_days }}
                                            </span>
                                            <span
                                                class="px-2 py-0.5 text-[10px] font-bold border rounded-full {{ $progressBg }}">
                                                {{ $percentage }}%
                                            </span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 text-center">
                                        <form action="{{ route('attendance.destroy', $row->id) }}" method="POST"
                                            onsubmit="return confirm('Apakah Anda yakin ingin menghapus data rekap absensi bulan ini untuk karyawan ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="p-1.5 text-xs text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                title="Hapus Rekap Absensi">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr id="emptyRecapRow">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <i class="text-3xl text-gray-300 bi bi-inbox"></i>
                                        <p class="text-sm font-medium">Tidak ditemukan data absensi untuk site ini.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse

                        <tr id="noFilteredRecapRow" class="hidden">
                            <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <i class="text-3xl text-gray-300 fa-solid fa-filter-circle-xmark"></i>
                                    <p class="text-sm font-medium">Tidak ada data rekap untuk periode bulan yang dipilih.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .app-modal-overlay {
            background: rgba(15, 23, 42, 0.55);
            animation: appModalFadeIn .15s ease-out;
        }

        .app-modal-panel {
            animation: appModalPopIn .18s cubic-bezier(.34, 1.56, .64, 1);
        }

        @keyframes appModalFadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes appModalPopIn {
            from {
                opacity: 0;
                transform: translateY(12px) scale(.97);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        let attendanceState = {};
        let currentActiveEmployeeId = null;
        let employeeSchedulesState = {};
        let indonesianHolidays = @json($holidays ?? []);

        function getFormattedMonth() {
            let bulan = document.getElementById('filter_bulan') ? document.getElementById('filter_bulan').value : '01';
            let tahun = document.getElementById('filter_tahun') ? document.getElementById('filter_tahun').value : new Date()
                .getFullYear();
            return tahun + '-' + String(bulan).padStart(2, '0');
        }

        function updateHiddenMonth() {
            let monthVal = getFormattedMonth();
            let hiddenInput = document.getElementById('main_month_hidden');
            let formHiddenInput = document.getElementById('form_month_hidden');
            if (hiddenInput) hiddenInput.value = monthVal;
            if (formHiddenInput) formHiddenInput.value = monthVal;
        }

        function handleFilterChange() {
            updateHiddenMonth();
        }

        function openModal(modalId) {
            let el = document.getElementById(modalId);
            if (!el) return;
            el.classList.remove('hidden');
            el.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            let el = document.getElementById(modalId);
            if (!el) return;
            el.classList.add('hidden');
            el.classList.remove('flex');
            document.body.style.overflow = '';
        }

        document.addEventListener('keydown', function(e) {
            if (e.key !== 'Escape') return;
            ['plotCalendarModal', 'editEmployeeModal', 'detailPlotModal'].forEach(id => {
                let el = document.getElementById(id);
                if (el && !el.classList.contains('hidden')) closeModal(id);
            });
        });

        function parseMonthRaw() {
            let monthRaw = getFormattedMonth();
            let parts = (monthRaw || '').split('-').map(Number);
            let year = parts[0] || new Date().getFullYear();
            let month = parts[1] || (new Date().getMonth() + 1);
            let days = new Date(year, month, 0).getDate();
            return {
                year,
                month,
                days
            };
        }

        function getDaysInMonth() {
            return parseMonthRaw().days;
        }

        function isWeekendDay(day) {
            let {
                year,
                month
            } = parseMonthRaw();
            let dow = new Date(year, month - 1, day).getDay();
            return dow === 0 || dow === 6;
        }

        function getHolidayName(day) {
            let {
                year,
                month
            } = parseMonthRaw();
            let dateStr = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            return indonesianHolidays.hasOwnProperty(dateStr) ? indonesianHolidays[dateStr] : null;
        }

        function isHoliday(day) {
            return !!getHolidayName(day);
        }

        document.addEventListener("DOMContentLoaded", function() {
            updateHiddenMonth();
            let activeSiteSelect = document.getElementById('main_branch_select');
            let activeSiteId = activeSiteSelect ? activeSiteSelect.value : null;
            if (activeSiteId) {
                fetchEmployees(activeSiteId);
            }
            filterRecapTable();
        });

        function setLoading(isLoading) {
            let loadingEl = document.getElementById('employee_loading');
            let fieldContainer = document.getElementById('employee_fields');
            if (!loadingEl || !fieldContainer) return;
            loadingEl.classList.toggle('hidden', !isLoading);
            fieldContainer.classList.toggle('hidden', isLoading);
        }

        function toggleManualInput(isChecked) {
            document.getElementById('auto_full_hidden').value = isChecked ? 'true' : 'false';
            let totalDays = getDaysInMonth();
            let {
                year,
                month
            } = parseMonthRaw();

            Object.keys(attendanceState).forEach(empId => {
                let schedules = employeeSchedulesState[empId] || [];

                for (let d = 1; d <= totalDays; d++) {
                    let dateStr = `${year}-${String(month).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                    let sched = schedules.find(s => s.date === dateStr);

                    if (isChecked) {
                        if (sched && sched.shift) {
                            let shiftName = (sched.shift.shift_name || '').toLowerCase();
                            let isOff = sched.shift.is_off;

                            if (isOff) {
                                attendanceState[empId].shifts[d] = {
                                    s1: 0,
                                    s2: 0,
                                    s3: 0
                                };
                            } else if (shiftName.includes('2')) {
                                attendanceState[empId].shifts[d] = {
                                    s1: 0,
                                    s2: 1,
                                    s3: 0
                                };
                            } else if (shiftName.includes('3')) {
                                attendanceState[empId].shifts[d] = {
                                    s1: 0,
                                    s2: 0,
                                    s3: 1
                                };
                            } else {
                                attendanceState[empId].shifts[d] = {
                                    s1: 1,
                                    s2: 0,
                                    s3: 0
                                };
                            }
                        } else {
                            let shouldBeOff = isWeekendDay(d) || isHoliday(d);
                            attendanceState[empId].shifts[d] = {
                                s1: shouldBeOff ? 0 : 1,
                                s2: 0,
                                s3: 0
                            };
                        }
                    } else {
                        attendanceState[empId].shifts[d] = {
                            s1: 0,
                            s2: 0,
                            s3: 0
                        };
                    }
                }
                updateLiveCounters(empId);
                syncCalendarCheckboxesIfOpen(empId);
            });
        }

        function fetchEmployees(siteId) {
            let fieldContainer = document.getElementById('employee_fields');
            if (!fieldContainer) return;

            updateHiddenMonth();
            let monthVal = getFormattedMonth();
            let totalDays = getDaysInMonth();
            let {
                year,
                month
            } = parseMonthRaw();
            let countLabel = document.getElementById('employeeCountLabel');

            setLoading(true);

            fetch('/api/branches/' + siteId + '/employees?month=' + monthVal)
                .then(async response => {
                    if (!response.ok) {
                        let errText = 'Gagal memuat data karyawan (' + response.status + ')';
                        try {
                            let errJson = await response.json();
                            if (errJson.message) errText = errJson.message;
                        } catch (e) {}
                        throw new Error(errText);
                    }
                    return response.json();
                })
                .then(data => {
                    fieldContainer.innerHTML = '';
                    attendanceState = {};
                    employeeSchedulesState = {};

                    if (!data || data.length === 0) {
                        fieldContainer.innerHTML = `
                        <div class="py-10 text-center text-gray-400">
                            <i class="block mb-2 text-4xl bi bi-person-x-fill"></i>
                            <span class="text-sm font-bold">Belum ada karyawan terdaftar di cabang ini.</span>
                        </div>`;
                        if (countLabel) countLabel.innerText = '';
                        return;
                    }

                    let isAutoFull = document.getElementById('autoFullAttendance') ?
                        document.getElementById('autoFullAttendance').checked : false;

                    data.forEach(emp => {
                        employeeSchedulesState[emp.id] = emp.schedules || [];

                        let hasSavedData = emp.attendances && emp.attendances.length > 0 && emp.attendances[0]
                            .matrix_details;
                        let savedShifts = null;

                        if (hasSavedData) {
                            try {
                                savedShifts = typeof emp.attendances[0].matrix_details === 'string' ?
                                    JSON.parse(emp.attendances[0].matrix_details) :
                                    emp.attendances[0].matrix_details;
                            } catch (e) {
                                savedShifts = null;
                            }
                        }

                        attendanceState[emp.id] = {
                            name: emp.name,
                            shifts: {}
                        };

                        for (let d = 1; d <= totalDays; d++) {
                            if (savedShifts && savedShifts[d] !== undefined) {
                                attendanceState[emp.id].shifts[d] = {
                                    s1: savedShifts[d].s1 !== undefined ? parseInt(savedShifts[d].s1) : 0,
                                    s2: savedShifts[d].s2 !== undefined ? parseInt(savedShifts[d].s2) : 0,
                                    s3: savedShifts[d].s3 !== undefined ? parseInt(savedShifts[d].s3) : 0
                                };
                            } else if (isAutoFull) {
                                let dateStr =
                                    `${year}-${String(month).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                                let sched = (emp.schedules || []).find(s => s.date === dateStr);

                                if (sched && sched.shift) {
                                    let shiftName = (sched.shift.shift_name || '').toLowerCase();
                                    let isOff = sched.shift.is_off;

                                    if (isOff) {
                                        attendanceState[emp.id].shifts[d] = {
                                            s1: 0,
                                            s2: 0,
                                            s3: 0
                                        };
                                    } else if (shiftName.includes('2')) {
                                        attendanceState[emp.id].shifts[d] = {
                                            s1: 0,
                                            s2: 1,
                                            s3: 0
                                        };
                                    } else if (shiftName.includes('3')) {
                                        attendanceState[emp.id].shifts[d] = {
                                            s1: 0,
                                            s2: 0,
                                            s3: 1
                                        };
                                    } else {
                                        attendanceState[emp.id].shifts[d] = {
                                            s1: 1,
                                            s2: 0,
                                            s3: 0
                                        };
                                    }
                                } else {
                                    let shouldBeOff = isWeekendDay(d) || isHoliday(d);
                                    attendanceState[emp.id].shifts[d] = {
                                        s1: shouldBeOff ? 0 : 1,
                                        s2: 0,
                                        s3: 0
                                    };
                                }
                            } else {
                                attendanceState[emp.id].shifts[d] = {
                                    s1: 0,
                                    s2: 0,
                                    s3: 0
                                };
                            }
                        }

                        let safeName = String(emp.name).replace(/'/g, "\\'");

                        fieldContainer.insertAdjacentHTML('beforeend', `
                        <div class="grid items-center grid-cols-1 gap-3 p-3 transition-colors border border-gray-100 rounded-lg md:grid-cols-12 hover:border-gray-200">
                            <div class="md:col-span-4">
                                <span class="flex items-center gap-1.5 font-semibold text-gray-800 truncate" title="${emp.name}">
                                    <i class="text-gray-400 bi bi-person-circle"></i>${emp.name}
                                </span>
                                <div class="flex gap-3 mt-1.5 text-xs">
                                    <button type="button" class="text-gray-500 transition-colors hover:text-blue-600"
                                        onclick="openEditModal(${emp.id}, '${safeName}', ${emp.site_id})">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <button type="button" class="text-red-500 transition-colors hover:text-red-700"
                                        onclick="confirmDeleteEmployee(${emp.id}, '${safeName}')">
                                        <i class="bi bi-trash"></i> Hapus
                                    </button>
                                    <button type="button" class="transition-colors text-cyan-600 hover:text-cyan-800"
                                        onclick="openDetailPlotModal(${emp.id})">
                                        <i class="bi bi-eye"></i> Riwayat Plot
                                    </button>
                                </div>
                            </div>
                            <div class="grid items-center grid-cols-4 gap-2 md:col-span-8">
                                <div class="flex rounded-md shadow-sm">
                                    <span class="flex items-center px-2 text-xs font-bold text-gray-500 bg-gray-100 border border-gray-300 rounded-l-md">S1</span>
                                    <input type="text" id="counter_s1_${emp.id}" class="w-full py-1 text-sm font-bold text-center text-blue-600 bg-white border-t border-b border-r border-gray-300 rounded-r-md focus:outline-none" readonly>
                                </div>
                                <div class="flex rounded-md shadow-sm">
                                    <span class="flex items-center px-2 text-xs font-bold text-gray-500 bg-gray-100 border border-gray-300 rounded-l-md">S2</span>
                                    <input type="text" id="counter_s2_${emp.id}" class="w-full py-1 text-sm font-bold text-center text-yellow-600 bg-white border-t border-b border-r border-gray-300 rounded-r-md focus:outline-none" readonly>
                                </div>
                                <div class="flex rounded-md shadow-sm">
                                    <span class="flex items-center px-2 text-xs font-bold text-gray-500 bg-gray-100 border border-gray-300 rounded-l-md">S3</span>
                                    <input type="text" id="counter_s3_${emp.id}" class="w-full py-1 text-sm font-bold text-center text-red-600 bg-white border-t border-b border-r border-gray-300 rounded-r-md focus:outline-none" readonly>
                                </div>
                                <button type="button" class="w-full px-2 py-1 text-xs font-semibold text-blue-600 transition-colors border border-blue-600 rounded-md hover:bg-blue-50" onclick="openPlotCalendar(${emp.id})">
                                    <i class="bi bi-calendar-event"></i> Plot / Edit
                                </button>
                            </div>
                            <input type="hidden" name="calendar_raw_data[${emp.id}]" id="raw_data_${emp.id}">
                        </div>`);

                        updateLiveCounters(emp.id);
                    });

                    if (countLabel) countLabel.innerText = data.length + ' karyawan dimuat';
                })
                .catch(err => {
                    fieldContainer.innerHTML = `
                    <div class="flex items-center gap-2 px-4 py-3 text-sm font-medium text-red-700 border border-red-200 rounded-lg bg-red-50">
                        <i class="bi bi-exclamation-triangle-fill"></i> ${err.message || 'Gagal memuat data karyawan.'}
                    </div>`;
                })
                .finally(() => setLoading(false));
        }

        function updateLiveCounters(employeeId) {
            let empData = attendanceState[employeeId];
            if (!empData) return;
            let totalDays = getDaysInMonth();
            let s1 = 0,
                s2 = 0,
                s3 = 0;
            for (let d = 1; d <= totalDays; d++) {
                if (empData.shifts[d].s1 === 1) s1++;
                if (empData.shifts[d].s2 === 1) s2++;
                if (empData.shifts[d].s3 === 1) s3++;
            }
            let s1El = document.getElementById('counter_s1_' + employeeId);
            let s2El = document.getElementById('counter_s2_' + employeeId);
            let s3El = document.getElementById('counter_s3_' + employeeId);
            let rawEl = document.getElementById('raw_data_' + employeeId);
            if (s1El) s1El.value = s1;
            if (s2El) s2El.value = s2;
            if (s3El) s3El.value = s3;
            if (rawEl) rawEl.value = JSON.stringify(empData.shifts);
        }

        function toggleDateShift(day, shiftKey, isChecked) {
            if (currentActiveEmployeeId && attendanceState[currentActiveEmployeeId]) {
                attendanceState[currentActiveEmployeeId].shifts[day][shiftKey] = isChecked ? 1 : 0;
                updateLiveCounters(currentActiveEmployeeId);
            }
        }

        function syncCalendarCheckboxesIfOpen(employeeId) {
            if (currentActiveEmployeeId !== employeeId) return;
            let modalEl = document.getElementById('plotCalendarModal');
            if (!modalEl || modalEl.classList.contains('hidden')) return;
            openPlotCalendar(employeeId);
        }

        function openPlotCalendar(employeeId) {
            if (!attendanceState[employeeId]) return;
            currentActiveEmployeeId = employeeId;
            document.getElementById('modalEmployeeName').innerText = attendanceState[employeeId].name;
            let gridBody = document.getElementById('calendarGridBody');
            gridBody.innerHTML = '';
            let totalDays = getDaysInMonth();
            let rows = '';
            for (let d = 1; d <= totalDays; d++) {
                let dayData = attendanceState[employeeId].shifts[d];
                let holidayName = getHolidayName(d);
                let isOff = isWeekendDay(d) || holidayName;
                let weekendBadge = isOff ?
                    `<span class="ml-1 text-[10px] text-red-500 font-bold">(Libur${holidayName ? ' - ' + holidayName : ''})</span>` :
                    '';
                rows += `
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="px-3 py-2 text-sm font-medium text-left text-gray-700">Tgl ${d}${weekendBadge}</td>
                    <td class="px-3 py-2"><input type="checkbox" class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500" onchange="toggleDateShift(${d}, 's1', this.checked)" ${dayData.s1 ? 'checked' : ''}></td>
                    <td class="px-3 py-2"><input type="checkbox" class="w-4 h-4 text-yellow-600 rounded focus:ring-yellow-500" onchange="toggleDateShift(${d}, 's2', this.checked)" ${dayData.s2 ? 'checked' : ''}></td>
                    <td class="px-3 py-2"><input type="checkbox" class="w-4 h-4 text-red-600 rounded focus:ring-red-500" onchange="toggleDateShift(${d}, 's3', this.checked)" ${dayData.s3 ? 'checked' : ''}></td>
                </tr>`;
            }
            gridBody.innerHTML = rows;
            openModal('plotCalendarModal');
        }

        function openEditModal(id, name, siteId) {
            document.getElementById('formEditEmployee').action = '/employee/' + id;
            document.getElementById('edit_employee_name').value = name;
            document.getElementById('edit_employee_site_id').value = siteId;
            openModal('editEmployeeModal');
        }

        function openDetailPlotModal(employeeId) {
            let empData = attendanceState[employeeId];
            if (!empData) return;
            document.getElementById('detailEmployeeName').innerText = empData.name;
            let listContainer = document.getElementById('detailActiveDatesList');
            listContainer.innerHTML = '';

            let totalDays = getDaysInMonth();
            let rows = '';
            let count = 0;

            for (let d = 1; d <= totalDays; d++) {
                let dayData = empData.shifts[d];
                if (dayData.s1 === 1 || dayData.s2 === 1 || dayData.s3 === 1) {
                    count++;
                    let activeShifts = [];
                    if (dayData.s1 === 1) activeShifts.push(
                        '<span class="px-2 py-0.5 text-xs font-semibold bg-blue-100 text-blue-800 rounded">S1</span>');
                    if (dayData.s2 === 1) activeShifts.push(
                        '<span class="px-2 py-0.5 text-xs font-semibold bg-yellow-100 text-yellow-800 rounded">S2</span>'
                    );
                    if (dayData.s3 === 1) activeShifts.push(
                        '<span class="px-2 py-0.5 text-xs font-semibold bg-red-100 text-red-800 rounded">S3</span>');

                    rows += `
                        <li class="flex items-center justify-between px-4 py-2.5">
                            <strong class="text-sm text-gray-700">Tanggal ${d}</strong>
                            <div class="flex gap-1.5">${activeShifts.join(' ')}</div>
                        </li>`;
                }
            }

            listContainer.innerHTML = count === 0 ?
                '<li class="py-6 text-sm font-medium text-center text-gray-400">Tidak ada tanggal yang dicentang (Karyawan Libur)</li>' :
                rows;

            openModal('detailPlotModal');
        }

        function confirmDeleteEmployee(id, name) {
            if (confirm("Hapus karyawan '" + name +
                    "' beserta seluruh riwayat absensinya? Tindakan ini tidak dapat dibatalkan.")) {
                let form = document.getElementById('formDeleteEmployee');
                form.action = '/employee/' + id;
                form.submit();
            }
        }

        function filterRecapTable() {
            const selectedMonth = document.getElementById('recapMonthFilter') ? document.getElementById('recapMonthFilter')
                .value : 'all';
            const rows = document.querySelectorAll('.recap-row');
            const noFilteredRow = document.getElementById('noFilteredRecapRow');
            const countBadge = document.getElementById('recapCountBadge');

            let visibleCount = 0;

            rows.forEach(row => {
                const rowMonth = row.getAttribute('data-month');

                if (selectedMonth === 'all' || rowMonth === selectedMonth) {
                    row.classList.remove('hidden');
                    visibleCount++;
                } else {
                    row.classList.add('hidden');
                }
            });

            if (noFilteredRow) {
                if (visibleCount === 0 && rows.length > 0) {
                    noFilteredRow.classList.remove('hidden');
                } else {
                    noFilteredRow.classList.add('hidden');
                }
            }

            if (countBadge) {
                countBadge.textContent = `${visibleCount} Data Ditampilkan`;
            }
        }
    </script>
@endpush
