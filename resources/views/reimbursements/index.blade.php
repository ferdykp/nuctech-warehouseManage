@extends('layout.master')

@section('title', 'Klaim Reimbursement')

@section('content')
                            @php
                                $months = [
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
    <div class="w-full space-y-5">

        {{-- 1. HEADER & METRIC SUMMARY --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                    {{ $pageTitle ?? 'Klaim Reimbursement' }}
                </h1>
                <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">
                    Kelola, periksa, dan setujui klaim biaya operasional.
                </p>
            </div>

            <div class="flex items-center gap-2.5">
                <a href="{{ route('reimbursements.trash') }}"
                    class="inline-flex items-center h-10 gap-2 px-4 text-xs font-semibold transition-all bg-white border text-slate-700 border-slate-200 rounded-xl hover:bg-slate-50 shadow-2xs">
                    <i class="fa-solid fa-box-archive text-slate-400"></i>
                    <span>Sampah</span>
                </a>
                <a href="{{ route('reimbursements.create') }}"
                    class="inline-flex items-center gap-2 h-10 p-4 text-xs font-semibold text-white bg-slate-900 rounded-xl hover:bg-slate-800 transition-all shadow-sm active:scale-[0.98]">
                    <i class="fa-solid fa-plus text-[11px]"></i>
                    <span>Ajukan Klaim</span>
                </a>
            </div>
        </div>

        {{-- KPI STATS CARDS --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="flex items-center justify-between p-4 bg-white border border-slate-200/90 rounded-2xl shadow-2xs">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block">Total Nominal Klaim</span>
                    <p id="totalApprovedAmountText" class="text-lg font-bold text-slate-900 font-mono mt-0.5">
                        Rp {{ \App\Support\UiText::number((float) ($totalApprovedAmount ?? 0)) }}
                    </p>
                </div>
                <div
                    class="flex items-center justify-center w-10 h-10 border rounded-xl bg-emerald-50 text-emerald-600 shrink-0 border-emerald-100">
                    <i class="text-sm fa-solid fa-wallet"></i>
                </div>
            </div>

            <div class="flex items-center justify-between p-4 bg-white border border-slate-200/90 rounded-2xl shadow-2xs">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block">Jumlah Data</span>
                    <p id="reimburseCountText" class="text-lg font-bold text-slate-900 font-mono mt-0.5">
                        {{ \App\Support\UiText::number($reimbursements->total()) }} <span
                            class="font-sans text-xs font-normal text-slate-400">Klaim</span>
                    </p>
                </div>
                <div
                    class="flex items-center justify-center w-10 h-10 border rounded-xl bg-slate-100 text-slate-600 shrink-0 border-slate-200">
                    <i class="text-sm fa-solid fa-receipt"></i>
                </div>
            </div>

            <div class="flex items-center justify-between p-4 bg-white border border-slate-200/90 rounded-2xl shadow-2xs">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block">Periode Filter</span>
                    <p id="reimbursePeriodText" class="mt-1 text-sm font-semibold text-slate-800">
                        {{ request('month') && isset($months[request('month')]) ? $months[request('month')] : 'Semua Bulan' }}
                    </p>
                </div>
                <div
                    class="flex items-center justify-center w-10 h-10 border rounded-xl bg-slate-100 text-slate-600 shrink-0 border-slate-200">
                    <i class="text-sm fa-regular fa-calendar-check"></i>
                </div>
            </div>
        </div>

        {{-- 2. TABLE CONTAINER --}}
        <div class="overflow-hidden bg-white border border-slate-200/90 rounded-2xl shadow-2xs">

            {{-- TOOLBAR (FILTER & EXPORT BAR) --}}
            <div
                class="p-4 border-b border-slate-100 bg-slate-50/40 flex flex-col gap-3.5 sm:flex-row sm:items-center sm:justify-between">
                <form id="reimburseFilterForm" action="{{ route('reimbursements.index') }}" method="GET"
                    onsubmit="event.preventDefault(); fetchReimbursementData();" class="flex flex-wrap items-center gap-2.5 flex-1">

                    {{-- Search Input --}}
                    <div class="relative w-full sm:w-80">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <i class="text-xs fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" id="reimburseSearchInput" aria-label="Cari klaim" name="search" value="{{ request('search') }}"
                            placeholder="Cari pengaju, rute, atau nomor nota..." autocomplete="off"
                            class="w-full h-10 pl-9.5 pr-8 text-xs bg-white border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900 transition-all shadow-2xs">

                        <button type="button" id="clearSearchBtn" aria-label="Hapus pencarian" onclick="clearSearch()"
                            class="{{ request('search') ? '' : 'hidden' }} absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 cursor-pointer">
                            <i class="text-xs fa-solid fa-circle-xmark"></i>
                        </button>
                    </div>

                    <div class="w-full sm:w-56">
                        <select id="reimburseStatusSelect" name="status" aria-label="Tahap persetujuan klaim" class="w-full rounded-xl border border-slate-200 bg-white text-sm">
                            <option value="">Semua Status</option>
                            @foreach (['waiting' => 'Semua yang Menunggu', 'pending' => 'Tanda Tangan Pengaju', 'pending_leader' => 'Menunggu Ketua Tim', 'pending_station' => 'Menunggu Kepala Stasiun', 'pending_manager' => 'Menunggu Manajer', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'] as $value => $label)
                                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Month Dropdown --}}
                    <div class="relative w-full sm:w-44">
                        <select id="reimburseMonthSelect" aria-label="Bulan klaim" name="month"
                            class="w-full h-10 pl-3.5 pr-8 text-xs bg-white border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900 appearance-none cursor-pointer transition-all shadow-2xs">
                            <option value="">Semua Bulan</option>

                            @foreach ($months as $value => $name)
                                <option value="{{ $value }}" {{ request('month') == $value ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400">
                            <i class="fa-solid fa-chevron-down text-[10px]"></i>
                        </span>
                    </div>

                </form>

                {{-- Export Actions --}}
                <div class="flex items-center gap-2 shrink-0">
                    {{-- Tombol Bulk Delete (Muncul saat ada yang dicentang) --}}
                    <button type="button" id="bulkDeleteBtn" onclick="submitBulkDelete()" style="display: none;"
                        class="h-10 px-3.5 text-xs font-semibold text-rose-700 bg-rose-50 border border-rose-200 rounded-xl hover:bg-rose-100 transition-all shadow-2xs inline-flex items-center gap-2 active:scale-[0.98] cursor-pointer">
                        <i class="text-sm fa-solid fa-trash-can"></i>
                        <span>Hapus Terpilih (<span id="selectedCount">0</span>)</span>
                    </button>
                    {{-- Tombol Import Excel Baru --}}
                    <button type="button" onclick="openImportModal()" title="Impor Excel"
                        class="h-10 px-3.5 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all shadow-2xs inline-flex items-center gap-2 active:scale-[0.98] cursor-pointer">
                        <i class="text-sm text-blue-600 fa-solid fa-file-import"></i>
                        <span>Impor Excel</span>
                    </button>

                    <a href="{{ route('reimbursements.export_pdf', ['month' => request('month')]) }}" id="pdfExportLink" title="Ekspor klaim yang sudah disetujui pada bulan terpilih"
                        download title="Ekspor PDF"
                        class="h-10 px-3.5 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all shadow-2xs inline-flex items-center gap-2 active:scale-[0.98]">
                        <i class="text-sm fa-solid fa-file-pdf text-rose-500"></i>
                        <span>Ekspor PDF</span>
                    </a>
                    <button type="button" onclick="exportExcelReport()" title="Ekspor Excel"
                        class="h-10 px-3.5 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all shadow-2xs inline-flex items-center gap-2 active:scale-[0.98] cursor-pointer">
                        <i class="text-sm fa-solid fa-file-excel text-emerald-600"></i>
                        <span>Ekspor Excel</span>
                    </button>
                </div>
            </div>

            {{-- DYNAMIC DATA WRAPPER --}}
            <div id="reimbursementDataWrapper" class="transition-opacity duration-150">

                {{-- DESKTOP TABLE --}}
                <div id="desktopTableContainer" class="hidden overflow-x-auto md:block">
                    <table class="w-full text-left border-collapse min-w-[700px]">
                        <thead>
                            <tr
                                class="text-xs font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50 border-b border-slate-100">
                                <th class="w-10 px-4 py-3 text-center">
                                    <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)"
                                        class="rounded cursor-pointer border-slate-300 text-slate-900 focus:ring-slate-900">
                                </th>
                                <th class="px-5 py-3">Pengaju dan Kategori</th>
                                <th class="px-5 py-3 text-right">Nominal</th>
                                <th class="px-5 py-3 text-center">Status</th>
                                <th class="w-48 px-5 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-xs font-medium divide-y divide-slate-100 text-slate-700">
                            @forelse ($reimbursements as $r)
                                <tr class="transition-colors hover:bg-slate-50/80">
                                    <td class="w-10 px-4 py-3.5 text-center">
                                        <input type="checkbox" name="selected_ids[]" value="{{ $r->id }}"
                                            onchange="updateBulkDeleteButton()"
                                            class="rounded cursor-pointer rowCheckbox border-slate-300 text-slate-900 focus:ring-slate-900">
                                    </td>
                                    {{-- Column 1: Requester, Category, Date, & Route/Invoice Ref --}}
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="flex items-center justify-center w-8 h-8 text-xs font-bold border text-slate-700 bg-slate-100 border-slate-200 rounded-xl shrink-0">
                                                {{ strtoupper(substr($r->person_name ?? '?', 0, 1)) }}
                                            </div>
                                            <div class="space-y-0.5">
                                                <div class="flex items-center gap-2">
                                                    <p class="font-bold leading-tight text-slate-900">
                                                        {{ $r->person_name }}
                                                    </p>
                                                    <span
                                                        class="px-1.5 py-0.2 text-xs font-bold uppercase tracking-wider rounded border
                                                        {{ $r->category == 'transportation' ? 'bg-blue-50 text-blue-700 border-blue-200/60' : ($r->category == 'delivery' ? 'bg-purple-50 text-purple-700 border-purple-200/60' : 'bg-slate-100 text-slate-600 border-slate-200') }}">
                                                        {{ \App\Support\UiText::label($r->category) }}
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-2 text-xs text-slate-400">
                                                    <span>{{ \Carbon\Carbon::parse($r->date)->translatedFormat('d M Y') }}</span>
                                                    @if (in_array($r->category, ['transportation', 'delivery']))
                                                        <span>•</span>
                                                        <span class="text-slate-500 font-normal truncate max-w-[180px]">
                                                            {{ $r->from_location }} <i
                                                                class="fa-solid fa-arrow-right text-[9px] mx-0.5 text-slate-300"></i>
                                                            {{ $r->to_location }}
                                                        </span>
                                                    @elseif($r->comment)
                                                        <span>•</span>
                                                        <span class="font-mono text-slate-500 text-xs">Ref:
                                                            {{ $r->comment }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Column 2: Amount --}}
                                    <td class="px-5 py-3.5 text-right font-mono font-bold text-slate-900 text-sm">
                                        Rp {{ \App\Support\UiText::number((float) ($r->amount ?? 0)) }}
                                    </td>

                                    {{-- Column 3: Status --}}
                                    <td class="px-5 py-3.5 text-center">
                                        <x-ui.status-badge :status="$r->status" />
                                    </td>

                                    {{-- Column 4: Direct Action Buttons --}}
                                    <td class="px-5 py-3.5 text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            {{-- Fast Approve --}}
                                            @if (in_array($r->status, ['pending', 'pending_leader', 'pending_station', 'pending_manager']) &&
                                                    Auth::user()->isSuperAdmin())
                                                <form action="{{ route('reimbursements.fast_approve', $r->id) }}"
                                                    method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" title="Setujui Langsung"
                                                        class="inline-flex items-center justify-center transition-colors border rounded-lg cursor-pointer w-7 h-7 text-emerald-600 bg-emerald-50 border-emerald-100 hover:bg-emerald-600 hover:text-white">
                                                        <i class="fa-solid fa-check-double text-[11px]"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            {{-- Quick View Details --}}
                                            <button type="button" onclick="openDetailModal(this)"
                                                data-reimbursement="{{ json_encode($r, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}"
                                                title="Lihat Rincian dan Bukti Nota"
                                                class="inline-flex items-center justify-center transition-colors border rounded-lg cursor-pointer w-7 h-7 text-slate-600 bg-slate-100 border-slate-200/80 hover:bg-slate-900 hover:text-white">
                                                <i class="fa-solid fa-eye text-[11px]"></i>
                                            </button>

                                            {{-- Sign Approval --}}
                                            <a href="{{ route('reimbursements.approval', $r->id) }}"
                                                title="Tanda Tangan Digital"
                                                class="inline-flex items-center justify-center text-blue-600 transition-colors border border-blue-100 rounded-lg w-7 h-7 bg-blue-50 hover:bg-blue-600 hover:text-white">
                                                <i class="fa-solid fa-pen-nib text-[11px]"></i>
                                            </a>

                                            {{-- Download PDF --}}
                                            <a href="{{ route('reimbursements.export_single_pdf', $r->id) }}" download
                                                title="Unduh Nota PDF"
                                                class="inline-flex items-center justify-center transition-colors border rounded-lg w-7 h-7 text-rose-600 bg-rose-50 border-rose-100 hover:bg-rose-600 hover:text-white">
                                                <i class="fa-solid fa-file-pdf text-[11px]"></i>
                                            </a>

                                            {{-- Edit --}}
                                            @if (\App\Services\ReimbursementAccess::canEdit($r))
<a href="{{ route('reimbursements.edit', $r->id) }}" title="Ubah Klaim"
                                                class="inline-flex items-center justify-center transition-colors border rounded-lg w-7 h-7 text-slate-500 bg-slate-50 border-slate-200/80 hover:bg-slate-200 hover:text-slate-800">
                                                <i class="fa-solid fa-pen-to-square text-[11px]"></i>
                                            </a>
@endif

                                            {{-- Archive --}}
                                            <button type="button"
                                                onclick="confirmCancel('{{ $r->id }}', '{{ $r->person_name }}', 'Rp {{ \App\Support\UiText::number((float) ($r->amount ?? 0)) }}')"
                                                title="Pindahkan ke Sampah"
                                                class="inline-flex items-center justify-center transition-colors border rounded-lg cursor-pointer w-7 h-7 text-rose-600 bg-rose-50 border-rose-100 hover:bg-rose-600 hover:text-white">
                                                <i class="fa-solid fa-trash-can text-[11px]"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-10 text-center bg-slate-50/30">
                                        <div class="max-w-xs mx-auto text-center">
                                            <div
                                                class="flex items-center justify-center w-10 h-10 mx-auto mb-2 border rounded-xl bg-slate-100 text-slate-400 border-slate-200">
                                                <i class="text-sm fa-solid fa-receipt"></i>
                                            </div>
                                            <p class="text-xs font-bold text-slate-800">Klaim Tidak Ditemukan</p>
                                            <p class="mt-0.5 text-xs text-slate-400">Tidak ada klaim yang sesuai dengan filter yang dipilih.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- MOBILE VIEW CARDS --}}
                <div id="mobileCardContainer" class="p-4 space-y-3 md:hidden bg-slate-50/50">
                    @forelse ($reimbursements as $r)
                        <div class="p-3.5 bg-white border border-slate-200 rounded-xl space-y-2.5 shadow-2xs">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-7 h-7 font-bold text-slate-700 bg-slate-100 border border-slate-200 rounded-lg flex items-center justify-center text-xs shrink-0">
                                        {{ strtoupper(substr($r->person_name ?? '?', 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold leading-tight text-slate-900">{{ $r->person_name }}
                                        </p>
                                        <p class="text-xs text-slate-400">
                                            {{ \Carbon\Carbon::parse($r->date)->translatedFormat('d M Y') }}</p>
                                    </div>
                                </div>
                                <x-ui.status-badge :status="$r->status" />
                            </div>

                            <div class="flex items-center justify-between p-2 text-xs rounded-lg bg-slate-50">
                                <span class="text-xs font-bold text-slate-400 uppercase">{{ \App\Support\UiText::label($r->category) }}</span>
                                <span class="font-mono font-bold text-slate-900">Rp
                                    {{ \App\Support\UiText::number((float) ($r->amount ?? 0)) }}</span>
                            </div>

                            <div data-ui-row-actions class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-100">
                                <div class="flex gap-1">
                                    @if (\App\Services\ReimbursementAccess::canEdit($r))
<a href="{{ route('reimbursements.edit', $r->id) }}"
                                        class="p-2 text-xs rounded-lg text-slate-600 bg-slate-100">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
@endif
                                    <button type="button" onclick="openDetailModal(this)"
                                        data-reimbursement="{{ json_encode($r, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}"
                                        class="p-2 text-xs rounded-lg cursor-pointer text-slate-600 bg-slate-100">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <button type="button"
                                        onclick="confirmCancel('{{ $r->id }}', '{{ $r->person_name }}', 'Rp {{ \App\Support\UiText::number((float) ($r->amount ?? 0)) }}')"
                                        class="p-2 text-xs rounded-lg cursor-pointer text-rose-600 bg-rose-50">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>

                                <div class="flex items-center gap-1.5">
                                    @if (in_array($r->status, ['pending', 'pending_leader', 'pending_station', 'pending_manager']) &&
                                            Auth::user()->isSuperAdmin())
                                        <form action="{{ route('reimbursements.fast_approve', $r->id) }}" method="POST">
                                            @csrf
                                            <button type="submit"
                                                class="px-2.5 py-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl cursor-pointer">
                                                <i class="fa-solid fa-check-double"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('reimbursements.approval', $r->id) }}"
                                        class="px-3 py-1 text-xs font-bold text-white bg-slate-900 rounded-xl">
                                        {{ in_array($r->status, ['approved', 'rejected']) ? 'Lihat Dokumen' : 'Tanda Tangan' }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-xs text-center bg-white border text-slate-400 border-slate-200 rounded-xl">
                            Tidak ada klaim yang sesuai dengan filter.
                        </div>
                    @endforelse
                </div>

                {{-- Shared pagination; retain the AJAX event wrapper. --}}
                @if ($reimbursements->hasPages())
                    <div class="p-4 border-t border-slate-100 bg-slate-50/40 ajax-pagination">
                        {{ $reimbursements->links() }}
                    </div>
                @endif

            </div>
        </div>
    </div>

    {{-- HIDDEN FORM UNTUK BULK DELETE --}}
    <form id="bulkDeleteForm" action="{{ route('reimbursements.bulk_delete') }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
        <div id="bulkDeleteInputs"></div>
    </form>

    {{-- MODAL 1: PREVIEW DETAILS --}}
    <div id="detailModal" onclick="if(event.target===this) closeDetailModal()"
        class="fixed inset-0 z-50 flex items-center justify-center hidden p-4 transition-opacity bg-slate-900/40 backdrop-blur-xs">
        <div
            class="relative w-full max-w-3xl bg-white border border-slate-200 shadow-2xl rounded-2xl flex flex-col max-h-[90vh] overflow-hidden">

            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/60 shrink-0">
                <div class="flex items-center gap-2">
                    <span id="modal-category"
                        class="px-2 py-0.5 text-xs font-bold uppercase tracking-wider rounded-md bg-slate-200 text-slate-800">
                        Kategori
                    </span>
                    <h3 class="text-sm font-bold text-slate-900">Rincian Klaim dan Bukti Nota</h3>
                </div>
                <button onclick="closeDetailModal()" type="button"
                    class="cursor-pointer text-slate-400 hover:text-slate-600">
                    <i class="text-lg fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 gap-6 p-6 overflow-y-auto text-xs lg:grid-cols-5">
                <div class="space-y-4 lg:col-span-2">
                    <div class="p-3.5 border border-slate-200/80 bg-slate-50/50 rounded-xl space-y-2">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Diajukan oleh</span>
                            <p id="modal-name" class="font-bold text-slate-900 text-sm mt-0.5">-</p>
                        </div>
                        <div class="pt-2 border-t border-slate-200/60">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Tanggal Pengajuan</span>
                            <p id="modal-date" class="font-medium text-slate-800 mt-0.5">-</p>
                        </div>
                    </div>

                    <div class="p-3.5 border border-slate-200/80 rounded-xl space-y-2">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Nominal Klaim</span>
                            <p id="modal-amount" class="text-lg font-mono font-bold text-slate-900 mt-0.5">Rp 0</p>
                        </div>
                        <div class="pt-2 border-t border-slate-100">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Informasi Rute</span>
                            <p id="modal-route" class="font-medium text-slate-800 mt-0.5">-</p>
                        </div>
                    </div>

                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block mb-1">Catatan / Keterangan</span>
                        <div id="modal-comment"
                            class="p-3 italic border border-slate-200 bg-slate-50/50 rounded-xl text-slate-700">
                            "Belum ada keterangan."
                        </div>
                    </div>

                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block mb-1.5">Tahapan Persetujuan</span>
                        <div class="p-3 space-y-2 border border-slate-200/80 rounded-xl bg-slate-50/30">
                            <div class="flex items-center justify-between pb-1.5 border-b border-slate-200/60">
                                <span class="font-semibold text-slate-700">1. Karyawan Pengaju</span>
                                <span id="sign-status-staff" class="px-2 py-0.5 rounded text-xs font-bold"></span>
                            </div>
                            <div class="flex items-center justify-between pb-1.5 border-b border-slate-200/60">
                                <span class="font-semibold text-slate-700">2. Ketua Tim</span>
                                <span id="sign-status-leader" class="px-2 py-0.5 rounded text-xs font-bold"></span>
                            </div>
                            <div class="flex items-center justify-between pb-1.5 border-b border-slate-200/60">
                                <span class="font-semibold text-slate-700">3. Kepala Stasiun</span>
                                <span id="sign-status-station" class="px-2 py-0.5 rounded text-xs font-bold"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-slate-700">4. Manajer Operasional</span>
                                <span id="sign-status-manager" class="px-2 py-0.5 rounded text-xs font-bold"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-3 flex flex-col space-y-1.5 min-h-[300px]">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Lampiran Nota</span>
                    <div id="modal-attachment-frame"
                        class="relative flex-1 w-full overflow-hidden border border-slate-200 bg-slate-100 rounded-xl">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL 2: CANCEL / DELETE CONFIRMATION --}}
    <div id="cancelModal" onclick="if(event.target===this) closeCancelModal()"
        class="fixed inset-0 z-50 flex items-center justify-center hidden p-4 transition-opacity bg-slate-900/40 backdrop-blur-xs">
        <div
            class="relative w-full max-w-sm p-6 space-y-4 text-center bg-white border shadow-2xl border-slate-200 rounded-2xl">
            <div
                class="flex items-center justify-center w-12 h-12 mx-auto border rounded-full bg-slate-100 text-slate-700 border-slate-200">
                <i class="text-lg fa-solid fa-box-archive"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-900">Pindahkan Klaim ke Sampah?</h3>
                <p class="mt-1 text-xs leading-relaxed text-slate-500">
                    Klaim diajukan oleh <strong id="cancel_person_name" class="text-slate-800"></strong> (<span
                        id="cancel_amount" class="font-mono font-bold text-slate-900"></span>) akan dipindahkan ke
                    <strong>Sampah</strong> .
                </p>
            </div>
            <form method="POST" action="" class="flex gap-2 pt-2">
                @csrf @method('DELETE')
                <button type="button" onclick="closeCancelModal()"
                    class="flex-1 py-2 text-xs font-semibold transition-colors cursor-pointer bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl">
                    Batal
                </button>
                <button type="submit"
                    class="flex-1 py-2 text-xs font-semibold text-white transition-colors cursor-pointer bg-slate-900 hover:bg-slate-800 rounded-xl shadow-2xs">
                    Pindahkan ke Sampah
                </button>
            </form>
        </div>
    </div>

    {{-- MODAL IMPORT EXCEL --}}
    <div id="importModal" onclick="if(event.target===this) closeImportModal()"
        class="fixed inset-0 z-50 flex items-center justify-center hidden p-4 transition-opacity bg-slate-900/40 backdrop-blur-xs">
        <div class="relative w-full max-w-md p-6 space-y-4 bg-white border shadow-2xl border-slate-200 rounded-2xl">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-900">Impor Klaim Reimbursement</h3>
                <button onclick="closeImportModal()" type="button"
                    class="cursor-pointer text-slate-400 hover:text-slate-600">
                    <i class="text-base fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="{{ route('reimbursements.import_excel') }}" method="POST" enctype="multipart/form-data"
                class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Pilih File Excel (.xlsx /
                        .csv)</label>
                    <input type="file" name="file" accept=".xlsx, .xls, .csv" required
                        class="block w-full p-1 text-xs border cursor-pointer text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 border-slate-200 rounded-xl">
                    <p class="mt-1.5 text-xs text-slate-400">
                        Gunakan file hasil dari fitur <strong>Ekspor Excel</strong> sebagai acuan struktur data.
                    </p>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeImportModal()"
                        class="px-4 py-2 text-xs font-semibold transition-colors cursor-pointer text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl">
                        Batal
                    </button>
                    <button type="submit"
                        class="px-4 py-2 text-xs font-semibold text-white bg-slate-900 hover:bg-slate-800 rounded-xl transition-all shadow-2xs cursor-pointer inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-upload"></i>
                        <span>Mulai Impor</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="application/json" id="reimbursements-config">{!! json_encode(['allSites' => in_array(auth()->user()?->role, ['superadmin', 'administration']), 'indexUrl' => route('reimbursements.index'), 'pdfUrl' => route('reimbursements.export_pdf'), 'excelUrl' => route('reimbursements.export_excel')], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    @vite('resources/js/pages/reimbursements.js')
@endpush
