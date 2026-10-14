@extends('layout.master')

@section('title', 'Sampah - Klaim Reimbursement')

@section('content')
    <div class="w-full space-y-6">

        {{-- 1. HEADER CARD --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <nav class="flex items-center gap-2 mb-1.5 text-xs font-bold tracking-wider text-slate-400 uppercase">
                        <a href="{{ route('reimbursements.index') }}"
                            class="transition-colors hover:text-amber-600">Klaim Reimbursement</a>
                        <i class="fa-solid fa-chevron-right text-[9px]"></i>
                        <span class="font-extrabold text-amber-600">Sampah</span>
                    </nav>
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                        Sampah Klaim Reimbursement
                    </h1>
                    <p class="mt-1 text-xs font-semibold sm:text-sm text-slate-500">
                        Lihat, pulihkan, atau hapus permanen klaim biaya operasional.
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    {{-- Tombol Aksi Massal (Muncul Saat Ada yang Dicentang) --}}
                    <div id="bulkActionsContainer" class="items-center gap-2" style="display: none;">
                        <button type="button" onclick="submitBulkAction('restore')"
                            class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl hover:bg-emerald-100 transition-all shadow-2xs cursor-pointer">
                            <i class="fa-solid fa-rotate-left"></i> Pulihkan Terpilih (<span id="selectedCount">0</span>)
                        </button>
                        <button type="button" onclick="submitBulkAction('delete')"
                            class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-bold text-rose-700 bg-rose-50 border border-rose-200 rounded-xl hover:bg-rose-100 transition-all shadow-2xs cursor-pointer">
                            <i class="fa-solid fa-ban"></i> Hapus Permanen (<span id="selectedCountDelete">0</span>)
                        </button>
                    </div>

                    <a href="{{ route('reimbursements.index') }}"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-all rounded-xl active:scale-95 shrink-0">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>Kembali ke Klaim Aktif</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- 2. TABLE CARD CONTAINER --}}
        <div class="overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <div class="flex items-center gap-2">
                    <div class="flex items-center justify-center w-6 h-6 rounded-lg bg-amber-100 text-amber-700">
                        <i class="text-xs fa-solid fa-box-archive"></i>
                    </div>
                    <h2 class="text-xs font-extrabold tracking-wider uppercase text-slate-700">Data yang Dihapus</h2>
                </div>
                <span class="text-[11px] font-semibold text-slate-400">
                    Menampilkan {{ $reimbursements->total() }} data
                </span>
            </div>

            <div class="overflow-x-auto">
                <table data-mobile-cards class="w-full text-left border-collapse min-w-[750px]">
                    <thead>
                        <tr
                            class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 bg-slate-50 border-b border-slate-100">
                            <th class="w-12 px-4 py-4 text-center">
                                <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)"
                                    class="rounded cursor-pointer border-slate-300 text-slate-900 focus:ring-slate-900">
                            </th>
                            <th class="w-16 px-6 py-4 text-center">Nomor</th>
                            <th class="px-6 py-4">Tanggal Dihapus</th>
                            <th class="px-6 py-4">Pengaju / Tanggal</th>
                            <th class="px-6 py-4">Kategori</th>
                            <th class="px-6 py-4 text-center">Nominal</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="w-48 px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs font-medium divide-y divide-slate-100 text-slate-700">
                        @forelse ($reimbursements as $r)
                            <tr class="transition-colors hover:bg-slate-50/60">
                                <td class="w-12 px-4 py-4 text-center">
                                    <input type="checkbox" name="selected_ids[]" value="{{ $r->id }}"
                                        onchange="updateBulkActionButtons()"
                                        class="rounded cursor-pointer rowCheckbox border-slate-300 text-slate-900 focus:ring-slate-900">
                                </td>
                                <td class="px-6 py-4 font-bold text-center text-slate-400">
                                    {{ ($reimbursements->currentPage() - 1) * $reimbursements->perPage() + $loop->iteration }}
                                </td>
                                <td class="px-6 py-4 font-semibold text-slate-500">
                                    {{ $r->deleted_at ? $r->deleted_at->format('d M Y H:i') : '-' }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex items-center justify-center w-8 h-8 text-xs font-black border text-amber-700 bg-amber-50 rounded-xl shrink-0 border-amber-100">
                                            {{ strtoupper(substr($r->person_name ?? '?', 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold leading-snug text-slate-900">{{ $r->person_name }}
                                            </p>
                                            <p class="text-[11px] font-medium text-slate-400 mt-0.5">
                                                Tanggal Klaim: {{ \Carbon\Carbon::parse($r->date)->translatedFormat('d M Y') }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="px-2.5 py-1 text-[10px] font-extrabold rounded-lg uppercase tracking-wider
                                        {{ $r->category == 'transportation' ? 'bg-blue-50 text-blue-700 border border-blue-200/60' : ($r->category == 'delivery' ? 'bg-purple-50 text-purple-700 border border-purple-200/60' : 'bg-slate-100 text-slate-700 border border-slate-200') }}">
                                        {{ \App\Support\UiText::label($r->category) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-xs font-black text-slate-900">
                                        Rp {{ number_format((float) ($r->amount ?? 0), 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="px-2.5 py-1 text-[10px] font-extrabold text-slate-600 bg-slate-100 border border-slate-200 rounded-lg uppercase">
                                        {{ \App\Support\UiText::label($r->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        {{-- RESTORE BUTTON --}}
                                        <form action="{{ route('reimbursements.restore', $r->id) }}" method="POST">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl hover:bg-emerald-100 active:scale-95 transition-all cursor-pointer"
                                                title="Pulihkan Klaim">
                                                <i class="fa-solid fa-rotate-left"></i> Pulihkan
                                            </button>
                                        </form>

                                        {{-- FORCE DELETE BUTTON --}}
                                        <button type="button"
                                            onclick="openForceDeleteModal({{ $r->id }}, '{{ $r->person_name }}', 'Rp {{ number_format((float) ($r->amount ?? 0), 0, ',', '.') }}')"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-rose-700 bg-rose-50 border border-rose-200 rounded-xl hover:bg-rose-100 active:scale-95 transition-all cursor-pointer"
                                            title="Hapus Permanen">
                                            <i class="fa-solid fa-ban"></i> Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-12 text-center text-slate-400">
                                    <div
                                        class="flex items-center justify-center w-12 h-12 mx-auto mb-3 text-xl rounded-2xl bg-slate-100 text-slate-400">
                                        <i class="fa-solid fa-box-archive"></i>
                                    </div>
                                    <p class="text-sm font-bold text-slate-800">Sampah Kosong</p>
                                    <p class="mt-1 text-xs text-slate-400">Tidak ada klaim dalam sampah.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t sm:p-6 border-slate-100 bg-slate-50/30">
                {{ $reimbursements->links() }}
            </div>
        </div>
    </div>

    {{-- HIDDEN FORM UNTUK BULK ACTIONS --}}
    <form id="bulkActionForm" action="" method="POST" class="hidden">
        @csrf
        <div id="bulkActionInputs"></div>
    </form>

    {{-- MODAL FORCE DELETE CONFIRMATION --}}
    <div id="forceDeleteModal" onclick="if(event.target===this) closeForceDeleteModal()"
        class="fixed inset-0 z-50 flex items-center justify-center hidden p-4 transition-all duration-300 bg-slate-900/60 backdrop-blur-xs">
        <div
            class="relative w-full max-w-md p-6 space-y-4 text-center bg-white border shadow-2xl border-slate-100 rounded-3xl">
            <div
                class="flex items-center justify-center mx-auto border rounded-full w-14 h-14 text-rose-600 bg-rose-50 border-rose-100">
                <i class="text-xl fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
                <h3 class="text-base font-extrabold text-slate-900">Hapus Permanen?</h3>
                <p class="mt-1 text-xs font-medium leading-relaxed text-slate-500">
                    Klaim atas nama <strong id="force_person_name" class="text-slate-800"></strong> (<span
                        id="force_amount" class="font-bold text-rose-600"></span>) beserta file bukti nota akan
                    <strong>dihapus secara permanen</strong>. Aksi ini tidak bisa dibatalkan kembali!
                </p>
            </div>
            <form id="forceDeleteForm" method="POST" action="" class="flex gap-3 pt-2">
                @csrf
                @method('DELETE')
                <button type="button" onclick="closeForceDeleteModal()"
                    class="flex-1 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs uppercase tracking-wider rounded-xl transition-colors cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                    class="flex-1 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition-all active:scale-[0.98] shadow-md shadow-rose-600/20 cursor-pointer">
                    <i class="mr-1 fa-solid fa-ban"></i> Hapus Permanen
                </button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function toggleSelectAll(master) {
            const checkboxes = document.querySelectorAll('.rowCheckbox');
            checkboxes.forEach(cb => {
                cb.checked = master.checked;
            });
            updateBulkActionButtons();
        }

        function updateBulkActionButtons() {
            const checkboxes = document.querySelectorAll('.rowCheckbox:checked');
            const bulkContainer = document.getElementById('bulkActionsContainer');
            const countSpan = document.getElementById('selectedCount');
            const countSpanDel = document.getElementById('selectedCountDelete');

            if (checkboxes.length > 0) {
                bulkContainer.style.display = 'flex';
                countSpan.innerText = checkboxes.length;
                countSpanDel.innerText = checkboxes.length;
            } else {
                bulkContainer.style.display = 'none';
            }
        }

        function submitBulkAction(actionType) {
            const checkboxes = document.querySelectorAll('.rowCheckbox:checked');
            if (checkboxes.length === 0) return;

            let msg = actionType === 'restore' ?
                'Yakin ingin memulihkan klaim reimbursement yang dipilih?' :
                'Yakin ingin MENGHAPUS PERMANEN klaim reimbursement yang dipilih beserta file notanya? Aksi ini tidak dapat dibatalkan!';

            if (!confirm(msg)) return;

            const form = document.getElementById('bulkActionForm');
            const container = document.getElementById('bulkActionInputs');
            container.innerHTML = '';

            if (actionType === 'restore') {
                form.action = "{{ route('reimbursements.bulk_restore') }}";
            } else {
                form.action = "{{ route('reimbursements.bulk_force_delete') }}";
                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'DELETE';
                container.appendChild(methodInput);
            }

            checkboxes.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = cb.value;
                container.appendChild(input);
            });

            form.submit();
        }

        function openForceDeleteModal(id, personName, amount) {
            const modal = document.getElementById('forceDeleteModal');
            if (!modal) return;

            const form = document.getElementById('forceDeleteForm');
            if (form) form.action = `/reimbursements/${id}/force-delete`;

            document.getElementById('force_person_name').innerText = personName;
            document.getElementById('force_amount').innerText = amount;

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeForceDeleteModal() {
            const modal = document.getElementById('forceDeleteModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
            document.body.classList.remove('overflow-hidden');
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeForceDeleteModal();
            }
        });
    </script>
@endpush
