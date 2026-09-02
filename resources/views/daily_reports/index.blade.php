@extends('layout.master')

@section('title', 'Daily Activity Reports')

@section('content')
    <div class="w-full space-y-6">

        {{-- 1. HEADER CARD --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <nav class="flex items-center gap-2 mb-1.5 text-xs font-bold tracking-wider text-slate-400 uppercase">
                        <span class="transition-colors cursor-pointer hover:text-emerald-600">Infrastructure</span>
                        <i class="fa-solid fa-chevron-right text-[9px]"></i>
                        <span class="font-extrabold text-emerald-600">Daily Activity Logs</span>
                    </nav>
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                        Daily Activity Reports
                    </h1>
                    <p class="mt-1 text-xs font-semibold sm:text-sm text-slate-500">
                        Monitor daily machine site logs, inspection notes, and photo documentations.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                    <button type="button" onclick="openExportModal()"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200/80 rounded-xl hover:bg-emerald-100 active:scale-95 transition-all shadow-2xs cursor-pointer">
                        <i class="fa-solid fa-file-pdf text-emerald-600"></i> Export Report (PDF)
                    </button>

                    <a href="{{ route('daily_reports.create') }}"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-xs font-bold text-white transition-all bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md shadow-emerald-600/20 active:scale-95 cursor-pointer">
                        <i class="fa-solid fa-plus"></i> New Report
                    </a>
                </div>
            </div>
        </div>

        {{-- ALERTS --}}
        @if (session('success'))
            <div class="p-4 text-xs font-bold border text-emerald-800 border-emerald-200 bg-emerald-50 rounded-2xl">
                {{ session('success') }}
            </div>
        @endif

        {{-- 2. TABLE CARD CONTAINER --}}
        <div class="overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">
            <!-- FILTER TOOLBAR -->
            <div class="p-5 border-b sm:p-6 border-slate-100 bg-slate-50/50">
                <form action="{{ route('daily_reports.index') }}" method="GET"
                    class="grid grid-cols-1 gap-3 sm:grid-cols-12">
                    <div class="relative sm:col-span-5">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search notes..."
                            class="block w-full py-2.5 px-3.5 text-xs font-medium transition-all bg-white border border-slate-200 rounded-xl outline-none focus:border-emerald-500 text-slate-800">
                    </div>
                    <div class="sm:col-span-4">
                        <input type="date" name="date" value="{{ request('date') }}"
                            class="block w-full py-2.5 px-3.5 text-xs font-medium transition-all bg-white border border-slate-200 rounded-xl outline-none focus:border-emerald-500 text-slate-800 cursor-pointer">
                    </div>
                    <div class="flex gap-2 sm:col-span-3">
                        <button type="submit"
                            class="px-4 py-2.5 text-xs font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 transition-colors cursor-pointer">Filter</button>
                        <a href="{{ route('daily_reports.index') }}"
                            class="px-4 py-2.5 text-xs font-bold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">Reset</a>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[700px]">
                    <thead>
                        <tr
                            class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 bg-slate-50 border-b border-slate-100">
                            <th class="px-6 py-4">Date & Site</th>
                            <th class="px-6 py-4">Reporter</th>
                            <th class="px-6 py-4">Log Notes</th>
                            <th class="px-6 py-4">Photos</th>
                            <th class="px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs font-medium divide-y divide-slate-100 text-slate-700">
                        @forelse ($reports as $report)
                            <tr class="transition-colors hover:bg-slate-50/60">
                                <td class="px-6 py-4">
                                    <span
                                        class="block text-xs font-extrabold text-slate-900">{{ $report->report_date->format('d M Y') }}</span>
                                    <span
                                        class="block text-[11px] text-slate-400 font-semibold mt-0.5">{{ $report->site->machine_name ?? '-' }}</span>
                                </td>
                                <td class="px-6 py-4 font-bold text-slate-800">
                                    {{ $report->user->name ?? '-' }}
                                </td>
                                <td class="max-w-xs px-6 py-4">
                                    <p class="line-clamp-2 text-slate-600">{{ $report->description }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="px-2.5 py-1 text-[10px] font-extrabold bg-blue-50 text-blue-700 border border-blue-200 rounded-full">
                                        {{ $report->photos->count() }} Photo(s)
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        {{-- TOMBOL SHOW DETAIL MODAL --}}
                                        <button type="button"
                                            onclick="showDetailModal({{ json_encode($report->load(['site', 'user', 'photos'])) }})"
                                            class="flex items-center justify-center w-8 h-8 transition-all border cursor-pointer rounded-xl text-emerald-600 bg-emerald-50 border-emerald-100 hover:bg-emerald-600 hover:text-white active:scale-95"
                                            title="View Details">
                                            <i class="text-xs fa-solid fa-eye"></i>
                                        </button>

                                        {{-- TOMBOL DELETE --}}
                                        <form action="{{ route('daily_reports.destroy', $report->id) }}" method="POST"
                                            onsubmit="return confirm('Hapus laporan ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="flex items-center justify-center w-8 h-8 transition-all border cursor-pointer rounded-xl text-rose-600 bg-rose-50 border-rose-100 hover:bg-rose-600 hover:text-white active:scale-95"
                                                title="Delete">
                                                <i class="text-xs fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-12 text-center text-slate-400">
                                    <p class="text-sm font-bold text-slate-800">Belum ada laporan harian</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t sm:p-6 border-slate-100 bg-slate-50/30">
                {{ $reports->links() }}
            </div>
        </div>
    </div>

    {{-- MODAL POPUP DETAIL --}}
    <div id="detailModal" onclick="if(event.target===this) closeDetailModal()"
        class="fixed inset-0 z-50 items-center justify-center hidden p-4 transition-all duration-200 bg-slate-900/60 backdrop-blur-xs">
        <div
            class="flex flex-col w-full max-w-2xl max-h-[90vh] overflow-hidden bg-white border shadow-2xl border-slate-100 rounded-3xl">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 bg-slate-50/50 shrink-0">
                <div class="flex items-center gap-3">
                    <div
                        class="flex items-center justify-center w-10 h-10 border text-emerald-600 bg-emerald-50 border-emerald-100 rounded-2xl shrink-0">
                        <i class="text-base fa-solid fa-clipboard-list"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Daily Report Detail</h3>
                        <p id="detail_meta" class="text-[11px] font-medium text-slate-500">-</p>
                    </div>
                </div>
                <button type="button" onclick="closeDetailModal()"
                    class="text-xl font-bold cursor-pointer text-slate-400 hover:text-slate-600">&times;</button>
            </div>

            <div class="p-6 space-y-5 overflow-y-auto">
                <div>
                    <label class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-1">Log
                        Note</label>
                    <div id="detail_description"
                        class="p-4 text-xs font-medium leading-relaxed whitespace-pre-line border border-slate-200/80 rounded-2xl bg-slate-50 text-slate-800">
                        -
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-2">Photo
                        Documentations</label>
                    <div id="detail_photos_container" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <!-- Foto dinamis diinjeksi via JS -->
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end px-6 py-4 border-t border-slate-100 bg-slate-50/50 shrink-0">
                <button type="button" onclick="closeDetailModal()"
                    class="px-5 py-2 text-xs font-bold transition-all cursor-pointer text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl">
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL EXPORT PDF --}}
    <div id="exportModal" onclick="if(event.target===this) closeExportModal()"
        class="fixed inset-0 z-50 items-center justify-center hidden p-4 transition-all duration-200 bg-slate-900/60 backdrop-blur-xs">
        <div class="flex flex-col w-full max-w-md overflow-hidden bg-white border shadow-2xl border-slate-100 rounded-3xl">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 bg-slate-50/50">
                <div class="flex items-center gap-3">
                    <div
                        class="flex items-center justify-center w-10 h-10 border text-emerald-600 bg-emerald-50 border-emerald-100 rounded-2xl shrink-0">
                        <i class="text-base fa-solid fa-file-export"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Export Daily Reports</h3>
                        <p class="text-[11px] font-medium text-slate-500">Pilih rentang tanggal ekspor laporan.</p>
                    </div>
                </div>
                <button type="button" onclick="closeExportModal()"
                    class="text-xl font-bold cursor-pointer text-slate-400 hover:text-slate-600">&times;</button>
            </div>

            <form action="{{ route('daily_reports.export_pdf') }}" method="GET" target="_blank"
                class="p-6 space-y-4">
                <div>
                    <label class="block mb-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-600">Preset
                        Cepat</label>
                    <div class="grid grid-cols-3 gap-2" id="presetContainer">
                        <button type="button" id="btn_preset_this_week" onclick="setPreset('this_week')"
                            class="preset-btn px-2.5 py-2 text-[11px] font-bold border border-slate-200 bg-slate-50 hover:border-emerald-500 hover:text-emerald-700 hover:bg-emerald-50/50 rounded-xl text-slate-700 transition-all cursor-pointer active:scale-95 shadow-2xs">
                            Minggu Ini
                        </button>
                        <button type="button" id="btn_preset_last_week" onclick="setPreset('last_week')"
                            class="preset-btn px-2.5 py-2 text-[11px] font-bold border border-slate-200 bg-slate-50 hover:border-emerald-500 hover:text-emerald-700 hover:bg-emerald-50/50 rounded-xl text-slate-700 transition-all cursor-pointer active:scale-95 shadow-2xs">
                            Minggu Lalu
                        </button>
                        <button type="button" id="btn_preset_this_month" onclick="setPreset('this_month')"
                            class="preset-btn px-2.5 py-2 text-[11px] font-bold border border-slate-200 bg-slate-50 hover:border-emerald-500 hover:text-emerald-700 hover:bg-emerald-50/50 rounded-xl text-slate-700 transition-all cursor-pointer active:scale-95 shadow-2xs">
                            Bulan Ini
                        </button>
                    </div>
                </div>

                @if (auth()->user()->role === 'superadmin')
                    <div>
                        <label class="block mb-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-600">Machine
                            Site</label>
                        <select name="site_id"
                            class="w-full p-2.5 text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl outline-none text-slate-800 cursor-pointer focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all">
                            <option value="">Semua Site</option>
                            @foreach ($sites as $s)
                                <option value="{{ $s->id }}">{{ $s->machine_name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block mb-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-600">Tanggal
                            Mulai <span class="text-rose-500">*</span></label>
                        <input type="date" name="start_date" id="export_start_date" required
                            onchange="checkActivePreset()" value="{{ date('Y-m-01') }}"
                            class="w-full p-2.5 text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl outline-none text-slate-800 cursor-pointer focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all">
                    </div>
                    <div>
                        <label class="block mb-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-600">Tanggal
                            Selesai <span class="text-rose-500">*</span></label>
                        <input type="date" name="end_date" id="export_end_date" required
                            onchange="checkActivePreset()" value="{{ date('Y-m-d') }}"
                            class="w-full p-2.5 text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl outline-none text-slate-800 cursor-pointer focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeExportModal()"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 cursor-pointer transition-colors">Batal</button>
                    <button type="submit" onclick="setTimeout(closeExportModal, 500)"
                        class="px-5 py-2.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md shadow-emerald-600/20 active:scale-95 transition-all cursor-pointer">Export
                        PDF</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function showDetailModal(report) {
            document.getElementById('detail_meta').innerText =
                `${report.site ? report.site.machine_name : '-'} • ${report.user ? report.user.name : '-'} • ${new Date(report.report_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}`;
            document.getElementById('detail_description').innerText = report.description || '-';

            const photosContainer = document.getElementById('detail_photos_container');
            photosContainer.innerHTML = '';

            if (report.photos && report.photos.length > 0) {
                report.photos.forEach(photo => {
                    const photoHtml = `
                        <div class="p-2 text-center border border-slate-200/80 rounded-2xl bg-slate-50">
                            <a href="/storage/${photo.photo_path}" target="_blank">
                                <img src="/storage/${photo.photo_path}" class="object-contain w-full h-40 bg-white border rounded-xl border-slate-100" />
                            </a>
                            ${photo.caption ? `<p class="mt-1.5 text-[10px] font-semibold text-slate-500 italic">${photo.caption}</p>` : ''}
                        </div>
                    `;
                    photosContainer.innerHTML += photoHtml;
                });
            } else {
                photosContainer.innerHTML =
                    '<p class="col-span-2 text-xs italic text-slate-400">Tidak ada foto dokumentasi.</p>';
            }

            const modal = document.getElementById('detailModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            }
        }

        function closeDetailModal() {
            const modal = document.getElementById('detailModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
            document.body.classList.remove('overflow-hidden');
        }

        function openExportModal() {
            const modal = document.getElementById('exportModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
                checkActivePreset();
            }
        }

        function closeExportModal() {
            const modal = document.getElementById('exportModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
            document.body.classList.remove('overflow-hidden');
        }

        function updatePresetBtnStyles(activePresetKey) {
            const buttons = {
                'this_week': document.getElementById('btn_preset_this_week'),
                'last_week': document.getElementById('btn_preset_last_week'),
                'this_month': document.getElementById('btn_preset_this_month')
            };

            const inactiveClasses = ['bg-slate-50', 'text-slate-700', 'border-slate-200', 'hover:border-emerald-500',
                'hover:text-emerald-700', 'hover:bg-emerald-50/50'
            ];
            const activeClasses = ['bg-emerald-600', 'text-white', 'border-emerald-600', 'shadow-emerald-600/20',
                'shadow-md'
            ];

            for (const key in buttons) {
                const btn = buttons[key];
                if (!btn) continue;

                if (key === activePresetKey) {
                    btn.classList.remove(...inactiveClasses);
                    btn.classList.add(...activeClasses);
                } else {
                    btn.classList.remove(...activeClasses);
                    btn.classList.add(...inactiveClasses);
                }
            }
        }

        function getPresetDates(type) {
            const today = new Date();
            let startDate = '',
                endDate = '';

            if (type === 'this_week') {
                const day = today.getDay();
                const diffToMonday = today.getDate() - day + (day === 0 ? -6 : 1);
                const monday = new Date(today.setDate(diffToMonday));
                const sunday = new Date(monday);
                sunday.setDate(monday.getDate() + 6);
                startDate = monday.toISOString().split('T')[0];
                endDate = sunday.toISOString().split('T')[0];
            } else if (type === 'last_week') {
                const day = today.getDay();
                const diffToLastMonday = today.getDate() - day - 6 + (day === 0 ? -6 : 1);
                const lastMonday = new Date(today.setDate(diffToLastMonday));
                const lastSunday = new Date(lastMonday);
                lastSunday.setDate(lastMonday.getDate() + 6);
                startDate = lastMonday.toISOString().split('T')[0];
                endDate = lastSunday.toISOString().split('T')[0];
            } else if (type === 'this_month') {
                const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                startDate = firstDay.toISOString().split('T')[0];
                endDate = lastDay.toISOString().split('T')[0];
            }

            return {
                startDate,
                endDate
            };
        }

        function setPreset(type) {
            const startInput = document.getElementById('export_start_date');
            const endInput = document.getElementById('export_end_date');
            const {
                startDate,
                endDate
            } = getPresetDates(type);

            if (startInput && endInput) {
                startInput.value = startDate;
                endInput.value = endDate;
            }

            updatePresetBtnStyles(type);
        }

        function checkActivePreset() {
            const startVal = document.getElementById('export_start_date')?.value;
            const endVal = document.getElementById('export_end_date')?.value;

            let matchedPreset = null;
            ['this_week', 'last_week', 'this_month'].forEach(type => {
                const {
                    startDate,
                    endDate
                } = getPresetDates(type);
                if (startVal === startDate && endVal === endDate) {
                    matchedPreset = type;
                }
            });

            updatePresetBtnStyles(matchedPreset);
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeExportModal();
                closeDetailModal();
            }
        });
    </script>
@endpush
