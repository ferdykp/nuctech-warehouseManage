@extends('layout.master')

@section('title', 'Leave & Time-Off Management')

@section('content')
    <div class="w-full space-y-6">

        {{-- 1. HEADER CARD --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <nav class="flex items-center gap-2 mb-1.5 text-xs font-bold tracking-wider text-slate-400 uppercase">
                        <span class="transition-colors cursor-pointer hover:text-emerald-600">HR & Attendance</span>
                        <i class="fa-solid fa-chevron-right text-[9px]"></i>
                        <span class="font-extrabold text-emerald-600">Leave Management</span>
                    </nav>
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                        Leave & Time-Off Management
                    </h1>
                    <p class="mt-1 text-xs font-semibold sm:text-sm text-slate-500">
                        Manage employee leave requests, quota tracking, and approvals seamlessly.
                        untuk notes saja, tidak masuk ke data integrasi
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                    <a href="{{ route('leave.create') }}"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-xs font-bold text-white transition-all bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md shadow-emerald-600/20 active:scale-95 cursor-pointer">
                        <i class="fa-solid fa-plus"></i> Apply Leave
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

        @if (session('error'))
            <div
                class="flex items-center gap-3 p-4 text-xs font-bold border sm:text-sm text-rose-800 border-rose-200/80 bg-rose-50 rounded-2xl shadow-2xs">
                <i class="text-base fa-solid fa-circle-xmark text-rose-600 shrink-0"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- 3. MAIN CARD CONTAINER --}}
        <div class="overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">
            <!-- FILTER TOOLBAR -->
            <div class="p-5 border-b sm:p-6 border-slate-100 bg-slate-50/50">
                <form action="{{ route('leave.index') }}" method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-12">
                    <div class="relative sm:col-span-6 md:col-span-8">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <i class="text-xs fa-solid fa-magnifying-glass"></i>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Search employee name..."
                            class="block w-full py-2.5 pl-9 pr-3.5 text-xs font-medium transition-all bg-white border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 shadow-2xs text-slate-800 placeholder-slate-400">
                    </div>

                    <div class="sm:col-span-4 md:col-span-3">
                        <select name="status" onchange="this.form.submit()"
                            class="block w-full px-3.5 py-2.5 text-xs font-bold transition-all bg-white border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 text-slate-800 shadow-2xs cursor-pointer">
                            <option value="">All Approval Statuses</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending Approval
                            </option>
                            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved
                            </option>
                            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected
                            </option>
                        </select>
                    </div>

                    <div class="flex justify-end sm:col-span-2 md:col-span-1">
                        <a href="{{ route('leave.index') }}"
                            class="inline-flex items-center justify-center w-full px-3 py-2.5 text-xs font-bold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors shadow-2xs">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- TABLE CONTENT -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[700px]">
                    <thead>
                        <tr
                            class="border-b border-slate-100 bg-slate-50/60 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                            <th scope="col" class="px-6 py-4">Employee</th>
                            <th scope="col" class="px-4 py-4">Leave Type</th>
                            <th scope="col" class="px-4 py-4">Date Range</th>
                            <th scope="col" class="px-4 py-4">Duration</th>
                            <th scope="col" class="px-4 py-4">Reason</th>
                            <th scope="col" class="px-4 py-4">Status</th>
                            <th scope="col" class="px-6 py-4 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs font-medium divide-y divide-slate-100 text-slate-700">
                        @forelse ($leaveRequests as $req)
                            <tr class="transition-colors hover:bg-slate-50/50">
                                <td class="px-6 py-4">
                                    <strong
                                        class="block text-sm font-bold text-slate-900">{{ $req->employee->name ?? '-' }}</strong>
                                    <span
                                        class="text-[11px] text-slate-400 font-semibold">{{ $req->employee->position ?? '-' }}</span>
                                </td>
                                <td class="px-4 py-4">
                                    <span
                                        class="px-3 py-1 text-[11px] font-extrabold rounded-full bg-slate-100 text-slate-700 border border-slate-200/60">
                                        {{ $req->leaveType->name ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 font-semibold text-slate-800 whitespace-nowrap">
                                    {{ $req->start_date ? \Carbon\Carbon::parse($req->start_date)->format('d M Y') : '-' }}
                                    -
                                    {{ $req->end_date ? \Carbon\Carbon::parse($req->end_date)->format('d M Y') : '-' }}
                                </td>
                                <td class="px-4 py-4 font-black text-slate-900 whitespace-nowrap">
                                    {{ $req->total_days }} {{ Str::plural('Day', $req->total_days) }}
                                </td>
                                <td class="max-w-xs px-4 py-4 font-medium truncate text-slate-600"
                                    title="{{ $req->reason }}">
                                    {{ $req->reason ?? '-' }}
                                </td>
                                <td class="px-4 py-4">
                                    @if ($req->status === 'approved')
                                        <span
                                            class="px-2.5 py-1 text-xs font-extrabold text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-full">
                                            Approved
                                        </span>
                                    @elseif ($req->status === 'rejected')
                                        <span
                                            class="px-2.5 py-1 text-xs font-extrabold text-rose-800 bg-rose-50 border border-rose-200 rounded-full">
                                            Rejected
                                        </span>
                                    @else
                                        <span
                                            class="px-2.5 py-1 text-xs font-extrabold text-amber-800 bg-amber-50 border border-amber-200 rounded-full">
                                            Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        @if ($req->status === 'pending')
                                            <form action="{{ route('leave.approve', $req->id) }}" method="POST"
                                                onsubmit="return confirm('Approve this leave request?')">
                                                @csrf
                                                <button type="submit"
                                                    class="px-3 py-1.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl active:scale-95 transition-all shadow-xs cursor-pointer">
                                                    Approve
                                                </button>
                                            </form>

                                            <button type="button" onclick="openRejectModal({{ $req->id }})"
                                                class="px-3 py-1.5 text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-xl transition-all active:scale-95 cursor-pointer">
                                                Reject
                                            </button>

                                            <a href="{{ route('leave.edit', $req->id) }}"
                                                class="p-2 text-xs font-bold transition-all text-slate-600 hover:text-emerald-600 bg-slate-100 hover:bg-slate-200 rounded-xl"
                                                title="Edit Request">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                        @endif

                                        <form action="{{ route('leave.destroy', $req->id) }}" method="POST"
                                            onsubmit="return confirm('Apakah Anda yakin ingin menghapus data pengajuan cuti ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="p-2 text-xs font-bold transition-all cursor-pointer text-slate-400 hover:text-rose-600 bg-slate-100 hover:bg-rose-50 rounded-xl"
                                                title="Delete Request">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 font-medium text-center text-slate-400">
                                    No leave requests found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                {{ $leaveRequests->links() }}
            </div>
        </div>
    </div>

    {{-- MODAL REJECT --}}
    <div id="rejectModal" onclick="if(event.target===this) closeRejectModal()"
        class="fixed inset-0 z-50 items-center justify-center hidden p-4 transition-all duration-200 bg-slate-900/60 backdrop-blur-xs">
        <div class="flex flex-col w-full max-w-md overflow-hidden bg-white border shadow-2xl border-slate-100 rounded-3xl">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 bg-slate-50/50">
                <div class="flex items-center gap-3">
                    <div
                        class="flex items-center justify-center w-10 h-10 border text-rose-600 bg-rose-50 border-rose-100 rounded-2xl shrink-0">
                        <i class="text-base fa-solid fa-circle-xmark"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Reject Leave Request</h3>
                        <p class="text-[11px] font-medium text-slate-500">Provide a reason for rejecting this leave.</p>
                    </div>
                </div>
                <button type="button" onclick="closeRejectModal()"
                    class="flex items-center justify-center w-8 h-8 transition-colors rounded-lg cursor-pointer text-slate-400 hover:text-slate-600 bg-slate-100 hover:bg-slate-200">&times;</button>
            </div>

            <form id="rejectForm" method="POST" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block mb-1.5 text-[11px] font-bold tracking-wider uppercase text-slate-600">Rejection
                        Reason</label>
                    <textarea name="rejection_reason" required rows="3"
                        class="w-full p-3 text-xs font-medium border outline-none bg-slate-50 border-slate-200 rounded-xl focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 text-slate-800 placeholder-slate-400"
                        placeholder="State reason for rejection..."></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeRejectModal()"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-5 py-2.5 text-xs font-bold text-white transition-all shadow-md bg-rose-600 hover:bg-rose-700 rounded-xl shadow-rose-600/20 active:scale-95 cursor-pointer">
                        Confirm Rejection
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function openRejectModal(id) {
            const form = document.getElementById('rejectForm');
            const modal = document.getElementById('rejectModal');
            if (form) form.action = `/leave/${id}/reject`;
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            }
        }

        function closeRejectModal() {
            const modal = document.getElementById('rejectModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
            document.body.classList.remove('overflow-hidden');
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeRejectModal();
                }
            });
        });
    </script>
@endpush
