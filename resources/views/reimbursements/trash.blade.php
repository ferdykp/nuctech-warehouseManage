@extends('layout.master')

@section('title', 'Recycle Bin - Reimbursement Claims')

@section('content')
    <div class="w-full space-y-6">

        {{-- 1. HEADER CARD --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <nav class="flex items-center gap-2 mb-1.5 text-xs font-bold tracking-wider text-slate-400 uppercase">
                        <a href="{{ route('reimbursements.index') }}"
                            class="transition-colors hover:text-amber-600">Reimbursement Claims</a>
                        <i class="fa-solid fa-chevron-right text-[9px]"></i>
                        <span class="font-extrabold text-amber-600">Recycle Bin</span>
                    </nav>
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                        Archived Reimbursement Claims
                    </h1>
                    <p class="mt-1 text-xs font-semibold sm:text-sm text-slate-500">
                        View, restore, or permanently delete operational reimbursement claims.
                    </p>
                </div>

                <a href="{{ route('reimbursements.index') }}"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-all rounded-xl active:scale-95 shrink-0">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Back to Active Claims</span>
                </a>
            </div>
        </div>

        {{-- 2. TABLE CARD CONTAINER --}}
        <div class="overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <div class="flex items-center gap-2">
                    <div class="flex items-center justify-center w-6 h-6 rounded-lg bg-amber-100 text-amber-700">
                        <i class="text-xs fa-solid fa-box-archive"></i>
                    </div>
                    <h2 class="text-xs font-extrabold tracking-wider uppercase text-slate-700">Archived Records</h2>
                </div>
                <span class="text-[11px] font-semibold text-slate-400">
                    Showing {{ $reimbursements->total() }} record(s)
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[750px]">
                    <thead>
                        <tr
                            class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 bg-slate-50 border-b border-slate-100">
                            <th class="w-16 px-6 py-4 text-center">No</th>
                            <th class="px-6 py-4">Deleted Date</th>
                            <th class="px-6 py-4">Requester / Date</th>
                            <th class="px-6 py-4">Category</th>
                            <th class="px-6 py-4 text-center">Amount</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="w-48 px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs font-medium divide-y divide-slate-100 text-slate-700">
                        @forelse ($reimbursements as $r)
                            <tr class="transition-colors hover:bg-slate-50/60">
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
                                                Claim Date: {{ \Carbon\Carbon::parse($r->date)->format('d M Y') }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="px-2.5 py-1 text-[10px] font-extrabold rounded-lg uppercase tracking-wider
                                        {{ $r->category == 'transportation' ? 'bg-blue-50 text-blue-700 border border-blue-200/60' : ($r->category == 'delivery' ? 'bg-purple-50 text-purple-700 border border-purple-200/60' : 'bg-slate-100 text-slate-700 border border-slate-200') }}">
                                        {{ $r->category }}
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
                                        {{ strtoupper(str_replace('_', ' ', $r->status)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        {{-- RESTORE BUTTON --}}
                                        <form action="{{ route('reimbursements.restore', $r->id) }}" method="POST">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl hover:bg-emerald-100 active:scale-95 transition-all cursor-pointer"
                                                title="Restore Claim">
                                                <i class="fa-solid fa-rotate-left"></i> Restore
                                            </button>
                                        </form>

                                        {{-- FORCE DELETE BUTTON --}}
                                        <button type="button"
                                            onclick="openForceDeleteModal({{ $r->id }}, '{{ $r->person_name }}', 'Rp {{ number_format((float) ($r->amount ?? 0), 0, ',', '.') }}')"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-rose-700 bg-rose-50 border border-rose-200 rounded-xl hover:bg-rose-100 active:scale-95 transition-all cursor-pointer"
                                            title="Delete Permanently">
                                            <i class="fa-solid fa-ban"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-12 text-center text-slate-400">
                                    <div
                                        class="flex items-center justify-center w-12 h-12 mx-auto mb-3 text-xl rounded-2xl bg-slate-100 text-slate-400">
                                        <i class="fa-solid fa-box-archive"></i>
                                    </div>
                                    <p class="text-sm font-bold text-slate-800">Recycle Bin Is Empty</p>
                                    <p class="mt-1 text-xs text-slate-400">No archived reimbursement claims found.</p>
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
                    Klaim atas nama <strong id="force_person_name" class="text-slate-800"></strong> (<span id="force_amount"
                        class="font-bold text-rose-600"></span>) beserta file berkas bukti nota akan **dihapus secara
                    permanen**. Tindakan ini tidak bisa dibatalkan kembali!
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
