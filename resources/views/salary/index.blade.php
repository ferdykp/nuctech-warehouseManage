@extends('layout.master')

@section('title', 'Penggajian')

@section('content')
    <div class="w-full space-y-6">

        {{-- 1. HEADER CARD --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <nav class="flex items-center gap-2 mb-1.5 text-xs font-bold tracking-wider text-slate-400 uppercase">
                        <span class="transition-colors cursor-pointer hover:text-emerald-600">Keuangan dan Penggajian</span>
                        <i class="fa-solid fa-chevron-right text-[9px]"></i>
                        <span class="font-extrabold text-emerald-600">Manajemen Penggajian</span>
                    </nav>
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                        Manajemen Penggajian
                    </h1>
                    <p class="mt-1 text-xs font-semibold sm:text-sm text-slate-500">
                        Kelola dan pantau penggajian karyawan.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                    <button type="button" onclick="exportExcel()"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200/80 rounded-xl hover:bg-emerald-100 active:scale-95 transition-all shadow-2xs cursor-pointer">
                        <i class="fa-solid fa-file-excel text-emerald-600"></i> Ekspor Excel
                    </button>

                    <button type="button" onclick="openResetModal()"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-rose-700 bg-rose-50 border border-rose-200/80 rounded-xl hover:bg-rose-100 active:scale-95 transition-all shadow-2xs cursor-pointer">
                        <i class="fa-solid fa-rotate-left text-rose-600"></i> Hapus Penggajian
                    </button>

                    <button type="button" onclick="openGenerateModal()"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-xs font-bold text-white transition-all bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md shadow-emerald-600/20 active:scale-95 cursor-pointer">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> Buat Penggajian
                    </button>

                    <a href="{{ route('salary.create') }}"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-slate-700 transition-all bg-white border border-slate-200 rounded-xl hover:bg-slate-50 active:scale-95 shadow-2xs">
                        <i class="fa-solid fa-plus text-slate-400"></i> Tambah Manual
                    </a>
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

        @if (session('info'))
            <div
                class="flex items-center gap-3 p-4 text-xs font-bold text-blue-800 border sm:text-sm border-blue-200/80 bg-blue-50 rounded-2xl shadow-2xs">
                <i class="text-base text-blue-600 fa-solid fa-circle-info shrink-0"></i>
                <span>{{ session('info') }}</span>
            </div>
        @endif

        {{-- 3. MAIN CARD CONTAINER --}}
        <div class="overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">
            <!-- FILTER TOOLBAR -->
            <div class="p-5 space-y-4 border-b sm:p-6 border-slate-100 bg-slate-50/50">
                @php
                    $monthList = [
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

                <!-- Row 1: Period & Search Bar -->
                <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
                    <div class="flex gap-2 md:col-span-4">
                        <select id="filter-month"
                            class="w-3/5 px-3.5 py-2.5 text-xs font-bold transition-all bg-white border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 text-slate-800 shadow-2xs cursor-pointer">
                            @foreach ($monthList as $key => $val)
                                <option value="{{ $key }}" {{ sprintf('%02d', $month) == $key ? 'selected' : '' }}>
                                    {{ $val }}</option>
                            @endforeach
                        </select>

                        <select id="filter-year"
                            class="w-2/5 px-3.5 py-2.5 text-xs font-bold transition-all bg-white border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 text-slate-800 shadow-2xs cursor-pointer">
                            @for ($y = date('Y') - 2; $y <= date('Y') + 2; $y++)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div class="relative md:col-span-8">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <i class="text-xs fa-solid fa-magnifying-glass"></i>
                        </div>
                        <input type="text" id="filter-search"
                            placeholder="Cari nama karyawan, jabatan, atau nomor rekening..."
                            class="block w-full py-2.5 pl-9 pr-3.5 text-xs font-medium transition-all bg-white border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 shadow-2xs text-slate-800 placeholder-slate-400">
                    </div>
                </div>

                <!-- Row 2: Secondary Filters -->
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <!-- Filter Branch -->
                    <div>
                        <select id="filter-branch"
                            class="block w-full px-3.5 py-2 text-xs font-semibold transition-all bg-white border outline-none border-slate-200 rounded-xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 text-slate-700 shadow-2xs cursor-pointer">
                            <option value="">Semua Cabang</option>
                            @foreach ($branches as $br)
                                <option value="{{ $br->id }}">{{ $br->branch_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Information -->
                    <div>
                        <select id="filter-information"
                            class="block w-full px-3.5 py-2 text-xs font-semibold transition-all bg-white border outline-none border-slate-200 rounded-xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 text-slate-700 shadow-2xs cursor-pointer">
                            <option value="">Semua Status Gaji</option>
                            <option value="1st probation">Masa Percobaan 1</option>
                            <option value="2nd probation">Masa Percobaan 2</option>
                            <option value="3rd probation">Masa Percobaan 3</option>
                            <option value="regular salary">Gaji Reguler</option>
                        </select>
                    </div>

                    <!-- Filter Bank -->
                    <div>
                        <select id="filter-bank"
                            class="block w-full px-3.5 py-2 text-xs font-semibold transition-all bg-white border outline-none border-slate-200 rounded-xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 text-slate-700 shadow-2xs cursor-pointer">
                            <option value="">Semua Bank</option>
                            @foreach ($banks as $b)
                                <option value="{{ $b }}">{{ $b }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- TABLE CONTAINER (LOADED VIA AJAX) -->
            <div id="table-container" class="transition-opacity duration-200">
                @include('salary.table', ['salaries' => $salaries])
            </div>
        </div>
    </div>

    {{-- MODAL GENERATE GAJI BULANAN --}}
    <div id="modalGenerateSalary" onclick="if(event.target===this) closeGenerateModal()"
        class="fixed inset-0 z-50 items-center justify-center hidden p-4 transition-all duration-200 bg-slate-900/60 backdrop-blur-xs">
        <div class="flex flex-col w-full max-w-md overflow-hidden bg-white border shadow-2xl border-slate-100 rounded-3xl">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 bg-slate-50/50">
                <div class="flex items-center gap-3">
                    <div
                        class="flex items-center justify-center w-10 h-10 border text-emerald-600 bg-emerald-50 border-emerald-100 rounded-2xl shrink-0">
                        <i class="text-base fa-solid fa-wand-magic-sparkles"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Buat / Perbarui Penggajian Bulanan</h3>
                        <p class="text-xs font-medium text-slate-500">Pilih periode untuk membuat penggajian karyawan.</p>
                    </div>
                </div>
                <button type="button" onclick="closeGenerateModal()"
                    class="flex items-center justify-center w-8 h-8 transition-colors rounded-lg cursor-pointer text-slate-400 hover:text-slate-600 bg-slate-100 hover:bg-slate-200">&times;</button>
            </div>

            <form action="{{ route('salary.generateMonthly') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label
                            class="block mb-1.5 text-xs font-bold tracking-wider uppercase text-slate-600">Bulan</label>
                        <select name="month" id="modal_gen_month"
                            class="w-full p-2.5 text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 text-slate-800 cursor-pointer">
                            @foreach ($monthList as $k => $v)
                                <option value="{{ $k }}">{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label
                            class="block mb-1.5 text-xs font-bold tracking-wider uppercase text-slate-600">Tahun</label>
                        <select name="year" id="modal_gen_year"
                            class="w-full p-2.5 text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 text-slate-800 cursor-pointer">
                            @for ($y = date('Y') - 2; $y <= date('Y') + 2; $y++)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div
                    class="p-3.5 text-xs leading-relaxed text-amber-800 bg-amber-50/80 border border-amber-200/80 rounded-2xl flex items-start gap-2.5 font-medium">
                    <i class="fa-solid fa-circle-info text-amber-600 mt-0.5 shrink-0"></i>
                    <span>Sistem membuat penggajian untuk karyawan yang memenuhi periode terpilih. Gaji pokok yang sudah tersimpan dipertahankan. Penggajian periode lampau tidak ditimpa, dan data karyawan yang berhenti bekerja tidak dihapus otomatis.</span>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeGenerateModal()"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                        class="px-5 py-2.5 text-xs font-bold text-white transition-all shadow-md bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-emerald-600/20 active:scale-95 cursor-pointer">
                        <i class="mr-1.5 fa-solid fa-wand-magic-sparkles"></i> Proses Penggajian
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL RESET PAYROLL --}}
    <div id="modalResetSalary" onclick="if(event.target===this) closeResetModal()"
        class="fixed inset-0 z-50 items-center justify-center hidden p-4 transition-all duration-200 bg-slate-900/60 backdrop-blur-xs">
        <div class="flex flex-col w-full max-w-md overflow-hidden bg-white border shadow-2xl border-slate-100 rounded-3xl">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 bg-slate-50/50">
                <div class="flex items-center gap-3">
                    <div
                        class="flex items-center justify-center w-10 h-10 border text-rose-600 bg-rose-50 border-rose-100 rounded-2xl shrink-0">
                        <i class="text-base fa-solid fa-rotate-left"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Hapus Penggajian Periode</h3>
                        <p class="text-xs font-medium text-slate-500">Kosongkan seluruh data gaji pada bulan terpilih.
                        </p>
                    </div>
                </div>
                <button type="button" onclick="closeResetModal()"
                    class="flex items-center justify-center w-8 h-8 transition-colors rounded-lg cursor-pointer text-slate-400 hover:text-slate-600 bg-slate-100 hover:bg-slate-200">&times;</button>
            </div>

            <form action="{{ route('salary.resetMonthly') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label
                            class="block mb-1.5 text-xs font-bold tracking-wider uppercase text-slate-600">Bulan</label>
                        <select name="month" id="modal_reset_month"
                            class="w-full p-2.5 text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 text-slate-800 cursor-pointer">
                            @foreach ($monthList as $k => $v)
                                <option value="{{ $k }}">{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label
                            class="block mb-1.5 text-xs font-bold tracking-wider uppercase text-slate-600">Tahun</label>
                        <select name="year" id="modal_reset_year"
                            class="w-full p-2.5 text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 text-slate-800 cursor-pointer">
                            @for ($y = date('Y') - 2; $y <= date('Y') + 2; $y++)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div
                    class="p-3.5 text-xs leading-relaxed text-rose-800 bg-rose-50/80 border border-rose-200/80 rounded-2xl flex items-start gap-2.5 font-medium">
                    <i class="fa-solid fa-triangle-exclamation text-rose-600 mt-0.5 shrink-0"></i>
                    <span>Aksi ini akan <strong>menghapus seluruh data penggajian</strong> pada bulan & tahun yang
                        dipilih agar Anda dapat membuat ulang data dari awal.</span>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeResetModal()"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                        class="px-5 py-2.5 text-xs font-bold text-white transition-all shadow-md bg-rose-600 hover:bg-rose-700 rounded-xl shadow-rose-600/20 active:scale-95 cursor-pointer">
                        <i class="mr-1.5 fa-solid fa-rotate-left"></i> Hapus Penggajian Periode Ini
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL DETAIL GAJI --}}
    <div id="salaryDetailModal" onclick="if(event.target===this) closeSalaryModal()"
        class="fixed inset-0 z-50 items-center justify-center hidden p-4 transition-all duration-200 bg-slate-900/60 backdrop-blur-xs">
        <div
            class="w-full max-w-2xl bg-white border border-slate-100 rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh]">
            <div class="flex items-center justify-between px-6 py-5 text-white border-b border-slate-800 bg-slate-900">
                <h5 class="flex items-center gap-2.5 text-sm font-extrabold sm:text-base">
                    <i class="text-emerald-400 fa-solid fa-money-check-dollar"></i> Rincian Gaji Karyawan
                </h5>
                <button type="button" onclick="closeSalaryModal()"
                    class="flex items-center justify-center w-8 h-8 transition-colors rounded-lg cursor-pointer text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700">&times;</button>
            </div>

            <div class="p-6 space-y-4 overflow-y-auto">
                <!-- Employee Header -->
                <div class="flex items-center gap-4 pb-4 border-b border-slate-100">
                    <div
                        class="flex items-center justify-center w-12 h-12 text-xl font-bold border rounded-2xl text-emerald-600 border-emerald-200 bg-emerald-50 shrink-0">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold sm:text-lg text-slate-900" id="detail_name">-</h3>
                        <p class="text-xs font-semibold text-slate-500" id="detail_position">-</p>
                    </div>
                </div>

                <!-- Salary Grid Details -->
                <div class="grid grid-cols-1 gap-3 text-xs md:grid-cols-2">
                    <div class="p-3.5 border border-slate-100 rounded-2xl bg-slate-50/60">
                        <span
                            class="block mb-1 text-xs font-extrabold text-slate-400 uppercase tracking-wider">Penempatan / Cabang</span>
                        <strong class="text-xs text-slate-800" id="detail_placement">-</strong>
                    </div>

                    <div class="p-3.5 border border-slate-100 rounded-2xl bg-slate-50/60">
                        <span class="block mb-1 text-xs font-extrabold text-slate-400 uppercase tracking-wider">Jenis Gaji</span>
                        <span id="detail_information_badge">-</span>
                    </div>

                    <div class="p-3.5 border border-slate-100 rounded-2xl bg-slate-50/60">
                        <span class="block mb-1 text-xs font-extrabold text-slate-400 uppercase tracking-wider">Nama Bank</span>
                        <strong class="text-xs text-slate-800" id="detail_bank">-</strong>
                    </div>

                    <div class="p-3.5 border border-slate-100 rounded-2xl bg-slate-50/60">
                        <span class="block mb-1 text-xs font-extrabold text-slate-400 uppercase tracking-wider">Nomor Rekening</span>
                        <strong class="font-mono text-xs text-slate-900" id="detail_account_no">-</strong>
                    </div>

                    <div class="p-3.5 border border-emerald-100 rounded-2xl bg-emerald-50/40">
                        <span
                            class="block mb-1 text-xs font-extrabold text-emerald-600 uppercase tracking-wider">Gaji Pokok Bulanan</span>
                        <strong class="text-sm font-extrabold text-emerald-700" id="detail_amount">-</strong>
                    </div>

                    <div class="p-3.5 border border-blue-100 rounded-2xl bg-blue-50/40">
                        <span class="block mb-1 text-xs font-extrabold text-blue-600 uppercase tracking-wider">Sebelum / Sesudah (Penyesuaian Lembur)</span>
                        <strong class="text-xs font-bold text-blue-800" id="detail_before_after">-</strong>
                    </div>
                </div>

                <!-- OVERTIME CALCULATION SUMMARY -->
                <div class="p-4 space-y-2 border border-rose-100 rounded-2xl bg-rose-50/50">
                    <h6 class="text-xs font-extrabold text-rose-700 flex items-center gap-1.5 uppercase tracking-wider">
                        <i class="fa-solid fa-circle-exclamation"></i> Rincian Lembur Hari Libur Nasional (<span
                            id="detail_period_label">Periode</span>)
                    </h6>
                    <div class="grid grid-cols-3 gap-2 pt-1 text-xs">
                        <div>
                            <span class="block text-xs font-semibold text-slate-400">Hari Kerja Efektif:</span>
                            <strong class="text-slate-800" id="detail_effective_days">0 Hari</strong>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-slate-400">Lembur Hari Libur:</span>
                            <strong class="text-rose-600" id="detail_holiday_days">0 Hari</strong>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-slate-400">Tambahan Lembur:</span>
                            <strong class="text-emerald-600" id="detail_holiday_pay">Rp 0</strong>
                        </div>
                    </div>
                    <div class="flex items-center justify-between pt-2.5 border-t border-rose-200/60">
                        <span class="text-xs font-bold text-slate-700">Total Gaji Bersih:</span>
                        <strong class="text-sm font-black text-blue-700" id="detail_total_pay">Rp 0</strong>
                    </div>
                </div>

                <!-- More Info & Get Info -->
                <div class="pt-1 space-y-3 text-xs">
                    <div class="p-3.5 border border-slate-100 rounded-2xl bg-slate-50/60">
                        <span class="block mb-1 font-extrabold text-slate-400 uppercase text-xs tracking-wider">Informasi Tambahan:</span>
                        <p class="font-medium leading-relaxed whitespace-pre-line text-slate-700"
                            id="detail_more_information">-</p>
                    </div>

                    <div class="p-3.5 border border-slate-100 rounded-2xl bg-slate-50/60">
                        <span class="block mb-1 font-extrabold text-slate-400 uppercase text-xs tracking-wider">Informasi Gaji:</span>
                        <p class="font-medium leading-relaxed whitespace-pre-line text-slate-700"
                            id="detail_get_information">-</p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end px-6 py-4 border-t border-slate-100 bg-slate-50">
                <button type="button" onclick="closeSalaryModal()"
                    class="px-5 py-2.5 text-xs font-bold transition-colors bg-white border text-slate-600 border-slate-200 rounded-xl hover:bg-slate-100 active:scale-95 shadow-2xs cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="application/json" id="salary-config">{!! json_encode(['indexUrl' => route('salary.index'), 'exportUrl' => route('salary.exportExcel')], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    @vite('resources/js/pages/salary.js')
@endpush
