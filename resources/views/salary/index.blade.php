@extends('layout.master')

@section('title', 'Daftar Gaji Karyawan')

@section('content')
    <div class="w-full px-6 py-8">
        @if (session('success'))
            <div
                class="flex items-center gap-2 p-4 mb-6 text-sm text-green-800 border border-green-200 bg-green-50 rounded-xl">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <div class="flex flex-col gap-2 mb-6">
            <h1 class="text-3xl font-extrabold tracking-tighter text-black">Salary Management</h1>
            <p class="text-xs text-gray-500">Kelola informasi penggajian seluruh karyawan.</p>
        </div>

        <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-200 rounded-2xl">
            <!-- FILTER BAR -->
            <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">

                    <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-5 xl:w-5/6">
                        <!-- Filter Month & Year -->
                        <div>
                            @php
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
                            <select id="filter-month"
                                class="block w-full px-3 py-2 text-xs font-bold transition-all bg-white border border-gray-200 shadow-sm outline-none rounded-xl focus:border-blue-500 focus:ring-blue-500">
                                @foreach ($daftarBulan as $key => $val)
                                    <option value="{{ $key }}"
                                        {{ sprintf('%02d', $month) == $key ? 'selected' : '' }}>{{ $val }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <select id="filter-year"
                                class="block w-full px-3 py-2 text-xs font-bold transition-all bg-white border border-gray-200 shadow-sm outline-none rounded-xl focus:border-blue-500 focus:ring-blue-500">
                                @for ($y = date('Y') - 2; $y <= date('Y') + 2; $y++)
                                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                                        {{ $y }}</option>
                                @endfor
                            </select>
                        </div>

                        <!-- Search Input -->
                        <div class="relative w-full">
                            <input type="text" id="filter-search" placeholder="Cari Nama / Posisi / No Rek..."
                                class="block w-full py-2 pl-3 pr-3 text-xs transition-all bg-white border border-gray-200 shadow-sm outline-none rounded-xl focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <!-- Filter Information -->
                        <div>
                            <select id="filter-information"
                                class="block w-full px-3 py-2 text-xs transition-all bg-white border border-gray-200 shadow-sm outline-none rounded-xl focus:border-blue-500 focus:ring-blue-500">
                                <option value="">-- Semua Status Gaji --</option>
                                <option value="1st probation">1st Probation</option>
                                <option value="2nd probation">2nd Probation</option>
                                <option value="3rd probation">3rd Probation</option>
                                <option value="regular salary">Regular Salary</option>
                            </select>
                        </div>

                        <!-- Filter Bank -->
                        <div>
                            <select id="filter-bank"
                                class="block w-full px-3 py-2 text-xs transition-all bg-white border border-gray-200 shadow-sm outline-none rounded-xl focus:border-blue-500 focus:ring-blue-500">
                                <option value="">-- Semua Bank --</option>
                                @foreach ($banks as $b)
                                    <option value="{{ $b }}">{{ $b }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('salary.create') }}"
                            class="flex items-center justify-center gap-2 px-4 py-2 text-xs font-bold text-white transition-all bg-blue-600 rounded-xl hover:bg-blue-700 active:scale-95 shrink-0">
                            <i class="fa-solid fa-plus"></i> Tambah Data Gaji
                        </a>
                    </div>
                </div>
            </div>

            <!-- CONTAINER TABLE AJAX -->
            <div id="table-container">
                @include('salary.table', ['salaries' => $salaries])
            </div>
        </div>
    </div>

    {{-- MODAL POP-UP DETAIL GAJI --}}
    <div id="salaryDetailModal"
        class="fixed inset-0 z-50 items-center justify-center hidden p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="w-full max-w-2xl mx-auto overflow-hidden bg-white border border-gray-100 shadow-2xl rounded-2xl">
            <div class="flex items-center justify-between px-6 py-4 text-white bg-gray-900">
                <h5 class="flex items-center gap-2 text-base font-bold">
                    <i class="text-emerald-400 fa-solid fa-money-check-dollar"></i> Detail Informasi Gaji Karyawan
                </h5>
                <button type="button" onclick="closeSalaryModal()"
                    class="text-xl leading-none text-gray-400 hover:text-white">&times;</button>
            </div>

            <div class="p-6 space-y-4 max-h-[80vh] overflow-y-auto">
                <!-- Header Karyawan -->
                <div class="flex items-center gap-4 pb-4 border-b border-gray-100">
                    <div
                        class="flex items-center justify-center w-12 h-12 text-xl font-bold border rounded-full text-emerald-600 border-emerald-200 bg-emerald-50 shrink-0">
                        <i class="fa-solid fa-user-gear"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900" id="detail_name">-</h3>
                        <p class="text-xs font-semibold text-gray-500" id="detail_position">-</p>
                    </div>
                </div>

                <!-- Grid Rincian Data Gaji -->
                <div class="grid grid-cols-1 gap-4 text-xs md:grid-cols-2">
                    <div class="p-3 border border-gray-100 rounded-xl bg-gray-50/50">
                        <span class="block mb-1 text-gray-400">Placement / Branch</span>
                        <strong class="text-sm text-gray-800" id="detail_placement">-</strong>
                    </div>

                    <div class="p-3 border border-gray-100 rounded-xl bg-gray-50/50">
                        <span class="block mb-1 text-gray-400">Status Informasi Gaji</span>
                        <span id="detail_information_badge">-</span>
                    </div>

                    <div class="p-3 border border-gray-100 rounded-xl bg-gray-50/50">
                        <span class="block mb-1 text-gray-400">Nama Bank</span>
                        <strong class="text-sm text-gray-800" id="detail_bank">-</strong>
                    </div>

                    <div class="p-3 border border-gray-100 rounded-xl bg-gray-50/50">
                        <span class="block mb-1 text-gray-400">Nomor Rekening</span>
                        <strong class="font-mono text-sm text-gray-900" id="detail_account_no">-</strong>
                    </div>

                    <div class="p-3 border border-emerald-100 rounded-xl bg-emerald-50/40">
                        <span class="block mb-1 font-medium text-emerald-600">Gaji Pokok Bulanan</span>
                        <strong class="text-base text-emerald-700" id="detail_amount">-</strong>
                    </div>

                    <div class="p-3 border border-blue-100 rounded-xl bg-blue-50/40">
                        <span class="block mb-1 font-medium text-blue-600">Before / After (Penyesuaian Lembur)</span>
                        <strong class="text-sm text-blue-800" id="detail_before_after">-</strong>
                    </div>
                </div>

                <!-- RINGKASAN KALKULASI LEMBUR TANGGAL MERAH -->
                <div class="p-4 space-y-2 border border-rose-100 rounded-xl bg-rose-50/50">
                    <h6 class="text-xs font-bold text-rose-700 flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-exclamation"></i> Rincian Lembur Tanggal Merah (<span
                            id="detail_period_label">Periode</span>)
                    </h6>
                    <div class="grid grid-cols-3 gap-2 text-xs">
                        <div>
                            <span class="block text-gray-400">Hari Kerja Efektif:</span>
                            <strong class="text-gray-800" id="detail_effective_days">0 Hari</strong>
                        </div>
                        <div>
                            <span class="block text-gray-400">Lembur Tgl Merah:</span>
                            <strong class="text-rose-600" id="detail_holiday_days">0 Hari</strong>
                        </div>
                        <div>
                            <span class="block text-gray-400">Bonus Lembur:</span>
                            <strong class="text-emerald-600" id="detail_holiday_pay">Rp 0</strong>
                        </div>
                    </div>
                    <div class="flex items-center justify-between pt-2 border-t border-rose-200/60">
                        <span class="text-xs font-bold text-gray-700">Total Gaji Diterima (Penerimaan Akhir):</span>
                        <strong class="text-base font-extrabold text-blue-700" id="detail_total_pay">Rp 0</strong>
                    </div>
                </div>

                <!-- More Info & Get Info (Note) -->
                <div class="pt-2 space-y-3 text-xs">
                    <div class="p-3 border border-gray-100 rounded-xl bg-gray-50/50">
                        <span class="block mb-1 font-semibold text-gray-500">More Information:</span>
                        <p class="text-gray-700 whitespace-pre-line" id="detail_more_information">-</p>
                    </div>

                    <div class="p-3 border border-gray-100 rounded-xl bg-gray-50/50">
                        <span class="block mb-1 font-semibold text-gray-500">Get Information (Note):</span>
                        <p class="text-gray-700 whitespace-pre-line" id="detail_get_information">-</p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end px-6 py-4 border-t border-gray-100 bg-gray-50">
                <button type="button" onclick="closeSalaryModal()"
                    class="px-5 py-2 text-xs font-bold text-gray-600 transition-all bg-white border border-gray-300 rounded-xl hover:bg-gray-100">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <script>
        let debounceTimer;

        function fetchSalaries(targetUrl = null) {
            const month = document.getElementById('filter-month').value;
            const year = document.getElementById('filter-year').value;
            const search = document.getElementById('filter-search').value;
            const information = document.getElementById('filter-information').value;
            const bank = document.getElementById('filter-bank').value;

            const url = targetUrl ||
                `{{ route('salary.index') }}?month=${month}&year=${year}&search=${encodeURIComponent(search)}&information=${encodeURIComponent(information)}&bank=${encodeURIComponent(bank)}`;

            fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.text())
                .then(html => {
                    document.getElementById('table-container').innerHTML = html;
                })
                .catch(err => console.error('Error fetching salaries:', err));
        }

        document.getElementById('filter-month').addEventListener('change', () => fetchSalaries());
        document.getElementById('filter-year').addEventListener('change', () => fetchSalaries());
        document.getElementById('filter-information').addEventListener('change', () => fetchSalaries());
        document.getElementById('filter-bank').addEventListener('change', () => fetchSalaries());

        document.getElementById('filter-search').addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchSalaries();
            }, 300);
        });

        // PERBAIKAN: Hanya tangkap link di dalam navigasi Pagination
        document.getElementById('table-container').addEventListener('click', function(e) {
            const pageLink = e.target.closest('a');

            // Cek apakah link yang diklik merupakan bagian dari pagination (nav/pagination)
            if (pageLink && pageLink.href && (pageLink.closest('nav') || pageLink.closest('.pagination') || pageLink
                    .getAttribute('rel'))) {
                e.preventDefault();
                fetchSalaries(pageLink.href);
            }
        });

        function showSalaryDetail(id) {
            const month = document.getElementById('filter-month').value;
            const year = document.getElementById('filter-year').value;

            fetch(`/salary/${id}?month=${month}&year=${year}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(async res => {
                    if (!res.ok) {
                        let errMsg = 'Gagal mengambil data gaji (' + res.status + ')';
                        try {
                            let errJson = await res.json();
                            if (errJson.message) errMsg = errJson.message;
                        } catch (e) {}
                        throw new Error(errMsg);
                    }
                    return res.json();
                })
                .then(data => {
                    document.getElementById('detail_name').innerText = data.name || '-';
                    document.getElementById('detail_position').innerText = data.position || '-';
                    document.getElementById('detail_placement').innerText = data.placement || '-';
                    document.getElementById('detail_bank').innerText = data.bank || '-';
                    document.getElementById('detail_account_no').innerText = data.account_no || '-';
                    document.getElementById('detail_amount').innerText = data.amount_formatted || 'Rp 0';
                    document.getElementById('detail_before_after').innerText = data.before_after || '-';
                    document.getElementById('detail_more_information').innerText = data.more_information ||
                        'Tidak ada keterangan tambahan.';
                    document.getElementById('detail_get_information').innerText = data.get_information ||
                        'Tidak ada catatan.';

                    // Label Periode
                    document.getElementById('detail_period_label').innerText = month + '/' + year;

                    // Rincian Kalkulasi Lembur
                    document.getElementById('detail_effective_days').innerText = (data.calculation ? data.calculation
                        .effective_days : 0) + ' Hari';
                    document.getElementById('detail_holiday_days').innerText = (data.calculation ? data.calculation
                        .holiday_overtime_days : 0) + ' Hari';
                    document.getElementById('detail_holiday_pay').innerText = data.calculation ? data.calculation
                        .holiday_overtime_pay : 'Rp 0';
                    document.getElementById('detail_total_pay').innerText = data.calculation ? data.calculation
                        .total_salary_to_pay : 'Rp 0';

                    let infoText = (data.information || '').toUpperCase();
                    let isProbation = (data.information || '').includes('probation');
                    let badgeClass = isProbation ?
                        'px-2.5 py-1 text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200 rounded-full' :
                        'px-2.5 py-1 text-xs font-bold text-blue-700 bg-blue-50 border border-blue-200 rounded-full';

                    document.getElementById('detail_information_badge').innerHTML =
                        `<span class="${badgeClass}">${infoText}</span>`;

                    let modal = document.getElementById('salaryDetailModal');
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                })
                .catch(err => alert(err.message));
        }

        function closeSalaryModal() {
            let modal = document.getElementById('salaryDetailModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>
@endsection
