@extends('layout.master')

@section('title', 'Reimbursements')

@section('content')
    <div class="w-full pb-10 space-y-6 md:space-y-8">
        {{-- HEADER --}}
        <div class="flex flex-col gap-6 px-2 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-1">
                <h2 class="text-3xl font-black tracking-tighter md:text-4xl text-slate-800">
                    {{ $pageTitle ?? 'Reimbursement Claims' }}</h2>
                <p class="text-xs font-medium md:text-sm text-slate-500">Track and manage employee operational expenses.</p>
            </div>

            {{-- ACCUMULATED TOTAL BUDGET CARD --}}
            <div class="flex items-center gap-4 px-6 py-4 bg-white border shadow-sm border-slate-100 rounded-2xl">
                <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
                    <i class="text-xl fa-solid fa-money-bill-wave"></i>
                </div>
                <div>
                    <span class="text-[9px] font-black uppercase text-slate-400 tracking-widest block">Total Approved
                        Funds</span>
                    <span class="text-lg font-black text-slate-800">Rp
                        {{ number_format($totalApprovedAmount ?? 0, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        {{-- MAIN CONTAINER --}}
        <div class="bg-white border border-slate-100 shadow-sm rounded-[2rem] md:rounded-[3rem] overflow-hidden">
            <div
                class="flex flex-col items-stretch justify-between gap-4 p-6 border-b lg:flex-row lg:items-center border-slate-50">
                <div class="flex flex-col flex-1 gap-3 sm:flex-row sm:items-center">
                    <h3 class="text-lg font-bold text-slate-800 shrink-0">Claim Logs</h3>

                    {{-- SEARCH BAR --}}
                    <div class="relative w-full max-w-md">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400">
                            <i class="text-sm fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" id="reimburseSearchInput" onkeyup="filterReimburseList()"
                            placeholder="Search by staff name or comment..."
                            class="w-full py-2.5 pl-11 pr-4 text-xs font-medium bg-slate-50 border border-slate-100 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 text-slate-700 placeholder-slate-400 transition-all">
                    </div>
                </div>

                <a href="{{ route('reimbursements.create') }}"
                    class="flex items-center justify-center gap-2 px-6 py-3 text-sm font-black text-white transition-all shadow-lg bg-amber-600 rounded-xl shadow-amber-600/10 active:scale-95">
                    <i class="fa-solid fa-plus"></i>
                    <span>File New Claim</span>
                </a>
            </div>

            {{-- EMPTY DATA ALERT --}}
            <div id="emptySearchState" class="flex-col items-center justify-center hidden p-12 text-center bg-white">
                <div class="flex items-center justify-center w-16 h-16 mb-4 rounded-full bg-slate-50 text-slate-400">
                    <i class="text-xl fa-solid fa-receipt"></i>
                </div>
                <h4 class="text-sm font-bold text-slate-700">No Claims Found</h4>
                <p class="max-w-xs mt-1 text-xs text-slate-400">We couldn't find any results matching your search terms.</p>
            </div>

            {{-- DESKTOP VIEW --}}
            <div id="desktopTableContainer" class="hidden overflow-x-auto md:block">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-[10px] font-black tracking-[0.15em] text-slate-400 uppercase bg-slate-50/50">
                            <th class="px-8 py-5">Person Name / Date</th>
                            <th class="px-6 py-5">Category</th>
                            <th class="px-6 py-5">Details / Route</th>
                            <th class="px-6 py-5 text-center">Amount</th>
                            <th class="px-6 py-5 text-center">Status</th>
                            <th class="px-8 py-5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach ($reimbursements as $r)
                            <tr class="transition-colors group hover:bg-slate-50/50 reimburse-row-item"
                                data-search-staff="{{ strtolower($r->person_name) }}"
                                data-search-title="{{ strtolower($r->comment ?? '') }}">
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex items-center justify-center font-black w-9 h-9 text-amber-600 bg-amber-50 rounded-xl shrink-0">
                                            {{ strtoupper(substr($r->person_name ?? '?', 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-800">{{ $r->person_name }}</p>
                                            <p class="text-[10px] font-semibold text-slate-400">
                                                {{ \Carbon\Carbon::parse($r->date)->format('d M Y') }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <span
                                        class="px-2.5 py-1 text-[10px] font-bold rounded-lg uppercase tracking-wider
                                        {{ $r->category == 'transportation' ? 'bg-blue-50 text-blue-700' : ($r->category == 'delivery' ? 'bg-purple-50 text-purple-700' : 'bg-slate-100 text-slate-700') }}">
                                        {{ $r->category }}
                                    </span>
                                </td>
                                <td class="px-6 py-5">
                                    @if (in_array($r->category, ['transportation', 'delivery']))
                                        <p class="text-xs font-semibold text-slate-600">
                                            <i class="mr-1 fa-solid fa-location-dot text-rose-500"></i>
                                            {{ $r->from_location }}
                                            <i class="mx-1 fa-solid fa-arrow-right text-slate-400"></i>
                                            {{ $r->to_location }}
                                        </p>
                                    @else
                                        <p class="text-xs italic text-slate-400">No routing needed</p>
                                    @endif
                                </td>
                                <td class="px-6 py-5 text-center">
                                    <span class="text-xs font-black text-slate-700">Rp
                                        {{ number_format($r->amount, 0, ',', '.') }}</span>
                                </td>
                                <td class="px-6 py-5 text-center">
                                    @if ($r->status == 'approved')
                                        <span
                                            class="px-3 py-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 rounded-full">Approved</span>
                                    @elseif($r->status == 'rejected')
                                        <span
                                            class="px-3 py-1 text-[10px] font-bold text-rose-700 bg-rose-50 rounded-full">Rejected</span>
                                    @else
                                        <span
                                            class="px-3 py-1 text-[10px] font-bold text-amber-700 bg-amber-50 rounded-full animate-pulse">Pending</span>
                                    @endif
                                </td>
                                <td class="px-8 py-5">
                                    <div class="flex items-center justify-end gap-2">
                                        <button onclick="toggleDetailModal('{{ $r->id }}', true)"
                                            class="p-2 text-blue-600 transition-colors rounded-lg bg-blue-50 hover:bg-blue-600 hover:text-white">
                                            <i class="text-xs fa-solid fa-eye"></i>
                                        </button>

                                        @if ($r->status == 'pending' && $r->user_id == auth()->id())
                                            <button onclick="confirmCancel('{{ $r->id }}')"
                                                class="p-2 transition-colors rounded-lg text-rose-600 bg-rose-50 hover:bg-rose-600 hover:text-white">
                                                <i class="text-xs fa-solid fa-ban"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- MOBILE VIEW --}}
            <div id="mobileCardContainer" class="p-4 space-y-4 md:hidden bg-slate-50/50">
                <div class="flex items-center justify-between p-5 border bg-emerald-50/60 border-emerald-100 rounded-2xl">
                    <div>
                        <p class="text-[9px] font-black uppercase text-emerald-700 tracking-wider">Accumulated Total</p>
                        <p class="text-xs font-medium text-slate-500 mt-0.5">All approved claims</p>
                    </div>
                    <span class="text-base font-black text-emerald-700">
                        Rp {{ number_format($totalApprovedAmount ?? 0, 0, ',', '.') }}
                    </span>
                </div>

                @foreach ($reimbursements as $r)
                    <div class="p-5 space-y-4 bg-white border shadow-sm border-slate-100 rounded-2xl reimburse-card-item"
                        data-search-staff="{{ strtolower($r->person_name) }}"
                        data-search-title="{{ strtolower($r->comment ?? '') }}">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex items-center justify-center w-10 h-10 font-black text-amber-600 bg-amber-50 rounded-xl">
                                    {{ strtoupper(substr($r->person_name ?? '?', 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-800">{{ $r->person_name }}</p>
                                    <p class="text-[10px] font-semibold text-slate-400">
                                        {{ \Carbon\Carbon::parse($r->date)->format('d M Y') }}
                                    </p>
                                </div>
                            </div>
                            @if ($r->status == 'approved')
                                <span
                                    class="px-2.5 py-1 text-[9px] font-black uppercase text-emerald-700 bg-emerald-50 rounded-lg">Approved</span>
                            @elseif($r->status == 'rejected')
                                <span
                                    class="px-2.5 py-1 text-[9px] font-black uppercase text-rose-700 bg-rose-50 rounded-lg">Rejected</span>
                            @else
                                <span
                                    class="px-2.5 py-1 text-[9px] font-black uppercase text-amber-700 bg-amber-50 rounded-lg">Pending</span>
                            @endif
                        </div>
                        <div class="py-2 border-y border-slate-50">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-wider">Category / Amount</p>
                            <p class="text-xs font-bold text-slate-700 mt-0.5 uppercase tracking-wide">{{ $r->category }}
                            </p>
                            <p class="mt-1 text-sm font-black text-amber-600">Rp
                                {{ number_format($r->amount, 0, ',', '.') }}</p>
                        </div>
                        <div class="flex justify-end gap-2 pt-1">
                            <button onclick="toggleDetailModal('{{ $r->id }}', true)"
                                class="p-2.5 text-blue-600 bg-blue-50 rounded-xl grow text-center font-bold text-xs flex justify-center items-center gap-2">
                                <i class="fa-solid fa-eye"></i> View Details
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div id="paginationBlock" class="px-6 py-6 border-t md:px-10 border-slate-50">
                {{ $reimbursements->links() }}
            </div>
        </div>
    </div>

    {{-- CANCEL FORM MODAL --}}
    <div id="cancelModal" class="fixed inset-0 z-[9999] flex items-center justify-center hidden px-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
        <div
            class="relative w-full max-w-sm p-8 transition-all scale-95 opacity-0 bg-white shadow-2xl rounded-[2.5rem] modal-card">
            <div class="flex flex-col items-center text-center">
                <div class="flex items-center justify-center w-16 h-16 mb-6 rounded-2xl bg-rose-50 text-rose-500">
                    <i class="text-2xl fa-solid fa-ban"></i>
                </div>
                <h3 class="mb-2 text-xl font-black text-slate-800">Cancel Claim Submission?</h3>
                <p class="mb-8 text-xs font-medium leading-relaxed text-slate-500">This action will erase your request
                    record permanently.</p>
                <div class="flex flex-col w-full gap-3">
                    <form id="cancelForm" method="POST" action="">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="w-full py-4 text-xs font-black tracking-widest text-white uppercase shadow-lg bg-rose-600 rounded-2xl shadow-rose-100">Withdraw
                            Claim</button>
                    </form>
                    <button onclick="closeCancelModal()"
                        class="w-full py-4 text-xs font-black tracking-widest uppercase text-slate-400 bg-slate-50 rounded-2xl">Dismiss</button>
                </div>
            </div>
        </div>
    </div>

    {{-- INJECT MODAL DETAILS & APPROVAL COMPONENT --}}
    @foreach ($reimbursements as $r)
        <div id="modal-detail-{{ $r->id }}"
            class="fixed inset-0 z-[9998] items-center justify-center hidden opacity-0 transition-opacity duration-300 px-4">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                onclick="toggleDetailModal('{{ $r->id }}', false)"></div>
            <div
                class="relative w-full max-w-md p-6 bg-white shadow-2xl rounded-[2rem] modal-card transform scale-95 transition-all duration-300 max-h-[90vh] overflow-y-auto">
                <h3 class="pb-3 text-lg font-black border-b text-slate-800 border-slate-100">Claim Specifications</h3>

                <div class="mt-4 space-y-4 text-xs">
                    <div>
                        <span class="text-slate-400 font-bold block uppercase text-[9px] tracking-wide">Person Name</span>
                        <span class="font-bold text-slate-700 text-sm block mt-0.5">{{ $r->person_name }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 font-bold block uppercase text-[9px] tracking-wide">Category &
                            Route</span>
                        <span
                            class="font-bold text-slate-700 text-xs block mt-0.5 uppercase tracking-wide">{{ $r->category }}</span>
                        @if ($r->from_location)
                            <span class="block mt-1 text-xs font-semibold text-slate-600">
                                <i class="mr-1 fa-solid fa-location-dot text-rose-500"></i> {{ $r->from_location }} &rarr;
                                {{ $r->to_location }}
                            </span>
                        @endif
                    </div>
                    <div>
                        <span class="text-slate-400 font-bold block uppercase text-[9px] tracking-wide">Nominal
                            Amount</span>
                        <span class="font-black text-amber-600 text-lg block mt-0.5">Rp
                            {{ number_format($r->amount, 0, ',', '.') }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 font-bold block uppercase text-[9px] tracking-wide">Comment /
                            Notes</span>
                        <p class="p-3 mt-1 font-medium leading-relaxed text-slate-600 bg-slate-50 rounded-xl">
                            {{ $r->comment ?? 'No extra notes provided.' }}
                        </p>
                    </div>

                    {{-- Bukti Berkas Dokumen --}}
                    <div>
                        <span class="text-slate-400 font-bold block uppercase text-[9px] tracking-wide mb-1">Receipt
                            Attachment</span>
                        @if ($r->receipt_attachment)
                            <a href="{{ asset('storage/' . $r->receipt_attachment) }}" target="_blank"
                                class="flex items-center gap-2 p-3 font-bold text-blue-600 transition-all border border-slate-100 rounded-xl hover:bg-slate-50">
                                <i class="fa-solid fa-file-invoice-dollar"></i>
                                <span>Inspect Attached File</span>
                            </a>
                        @else
                            <span class="italic font-semibold text-slate-400">No receipt uploaded.</span>
                        @endif
                    </div>

                    @if ($r->status == 'rejected' && $r->rejected_reason)
                        <div class="p-3 border bg-rose-50 border-rose-100 rounded-xl text-rose-800">
                            <span class="font-black block text-[9px] uppercase tracking-wide">Rejection Reason</span>
                            <p class="font-bold mt-0.5">{{ $r->rejected_reason }}</p>
                        </div>
                    @endif
                </div>

                {{-- MANAGER ACTION BLOCK --}}
                {{-- @if ($r->status == 'pending' && in_array(auth()->user()->role ?? 'staff', ['superadmin', 'manager'])) --}}
                <div class="pt-4 mt-6 space-y-2 border-t border-slate-100">
                    {{-- DISINKRONKAN KE RUTE YANG BENAR --}}
                    <form method="POST" action="{{ route('reimbursements.approve', $r->id) }}">
                        @csrf @method('PUT')
                        <button type="submit"
                            class="w-full py-3 text-xs font-black tracking-wider text-white uppercase transition-all bg-emerald-600 hover:bg-emerald-700 rounded-xl">
                            Approve & Disburse
                        </button>
                    </form>

                    {{-- DISINKRONKAN KE RUTE YANG BENAR --}}
                    <div class="p-3 mt-2 border border-slate-100 rounded-xl bg-slate-50">
                        <form method="POST" action="{{ route('reimbursements.reject', $r->id) }}" class="space-y-2">
                            @csrf @method('PUT')
                            <input type="text" name="rejected_reason" required
                                placeholder="Specify rejection reason..."
                                class="w-full p-2.5 text-xs bg-white border border-slate-100 rounded-lg focus:outline-none focus:border-rose-500">
                            <button type="submit"
                                class="w-full py-2 bg-rose-600 hover:bg-rose-700 text-white font-black text-[10px] uppercase tracking-widest rounded-lg transition-all">
                                Reject Claim
                            </button>
                        </form>
                    </div>
                </div>
                {{-- @endif --}}
            </div>
        </div>
    @endforeach

@endsection

@push('scripts')
    <script>
        function filterReimburseList() {
            const query = document.getElementById('reimburseSearchInput').value.toLowerCase().trim();
            const desktopRows = document.querySelectorAll('.reimburse-row-item');
            const mobileCards = document.querySelectorAll('.reimburse-card-item');
            const emptyState = document.getElementById('emptySearchState');
            const desktopTable = document.getElementById('desktopTableContainer');
            const mobileContainer = document.getElementById('mobileCardContainer');
            const paginationBlock = document.getElementById('paginationBlock');

            let visibleCount = 0;

            desktopRows.forEach(row => {
                const staff = row.getAttribute('data-search-staff');
                const title = row.getAttribute('data-search-title');
                if (staff.includes(query) || title.includes(query)) {
                    row.style.display = "";
                    visibleCount++;
                } else {
                    row.style.display = "none";
                }
            });

            let mobileVisibleCount = 0;
            mobileCards.forEach(card => {
                const staff = card.getAttribute('data-search-staff');
                const title = card.getAttribute('data-search-title');
                if (staff.includes(query) || title.includes(query)) {
                    card.style.setProperty('display', '', 'important');
                    mobileVisibleCount++;
                } else {
                    card.style.setProperty('display', 'none', 'important');
                }
            });

            const totalVisible = window.innerWidth >= 768 ? visibleCount : mobileVisibleCount;

            if (totalVisible === 0) {
                emptyState.classList.remove('hidden');
                emptyState.classList.add('flex');
                desktopTable.classList.add('md:hidden');
                mobileContainer.classList.add('hidden');
                if (paginationBlock) paginationBlock.classList.add('hidden');
            } else {
                emptyState.classList.add('hidden');
                emptyState.classList.remove('flex');
                desktopTable.classList.remove('md:hidden');
                mobileContainer.classList.remove('hidden');
                if (paginationBlock) paginationBlock.classList.remove('hidden');
            }
        }

        function toggleDetailModal(id, show) {
            const modal = document.getElementById(`modal-detail-${id}`);
            if (!modal) return;
            const card = modal.querySelector('.modal-card');
            if (show) {
                modal.classList.remove('hidden');
                setTimeout(() => {
                    modal.classList.add('flex', 'opacity-100');
                    card.classList.remove('scale-95', 'opacity-0');
                    card.classList.add('scale-100', 'opacity-100');
                }, 10);
            } else {
                card.classList.remove('scale-100', 'opacity-100');
                card.classList.add('scale-95', 'opacity-0');
                setTimeout(() => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex', 'opacity-100');
                }, 300);
            }
        }

        function confirmCancel(id) {
            const modal = document.getElementById('cancelModal');
            const card = modal.querySelector('.modal-card');
            document.getElementById('cancelForm').action = `/reimbursements/${id}`;
            modal.classList.remove('hidden');
            setTimeout(() => {
                card.classList.remove('scale-95', 'opacity-0');
                card.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeCancelModal() {
            const modal = document.getElementById('cancelModal');
            const card = modal.querySelector('.modal-card');
            card.classList.add('scale-95', 'opacity-0');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }
    </script>
@endpush
