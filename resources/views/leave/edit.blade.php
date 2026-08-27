@extends('layout.master')

@section('title', 'Edit Leave Request')

@section('content')
    <div class="w-full space-y-6">

        {{-- 1. HEADER CARD --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <nav class="flex items-center gap-2 mb-1.5 text-xs font-bold tracking-wider text-slate-400 uppercase">
                        <a href="{{ route('leave.index') }}" class="transition-colors hover:text-emerald-600">Leave
                            Management</a>
                        <i class="fa-solid fa-chevron-right text-[9px]"></i>
                        <span class="font-extrabold text-emerald-600">Edit Leave Request</span>
                    </nav>
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                        Edit Leave Request
                    </h1>
                    <p class="mt-1 text-xs font-semibold sm:text-sm text-slate-500">
                        Update the information for this pending leave request.
                    </p>
                </div>

                <div class="flex items-center gap-2.5 shrink-0">
                    <a href="{{ route('leave.index') }}"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-slate-700 transition-all bg-white border border-slate-200 rounded-xl hover:bg-slate-50 active:scale-95 shadow-2xs">
                        <i class="fa-solid fa-arrow-left text-slate-400"></i> Back to List
                    </a>
                </div>
            </div>
        </div>

        {{-- 2. ALERTS SECTION --}}
        @if (session('error'))
            <div
                class="flex items-center gap-3 p-4 text-xs font-bold border sm:text-sm text-rose-800 border-rose-200/80 bg-rose-50 rounded-2xl shadow-2xs">
                <i class="text-base fa-solid fa-circle-xmark text-rose-600 shrink-0"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- 3. FORM CARD CONTAINER --}}
        <div class="max-w-3xl mx-auto overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">
            <form action="{{ route('leave.update', $leaveRequest->id) }}" method="POST" enctype="multipart/form-data"
                class="p-6 space-y-5 sm:p-8">
                @csrf
                @method('PUT')

                {{-- EMPLOYEE SELECTION --}}
                <div>
                    <label class="block mb-1.5 text-[11px] font-bold tracking-wider uppercase text-slate-600">Select
                        Employee</label>
                    <select name="employee_id" required
                        class="w-full px-3.5 py-2.5 text-xs font-bold transition-all bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 text-slate-800">
                        <option value="">-- Choose Employee --</option>
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}"
                                {{ old('employee_id', $leaveRequest->employee_id) == $emp->id ? 'selected' : '' }}>
                                {{ $emp->name }} ({{ $emp->position ?? '-' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- LEAVE TYPE --}}
                <div>
                    <label class="block mb-1.5 text-[11px] font-bold tracking-wider uppercase text-slate-600">Leave
                        Type</label>
                    <select name="leave_type_id" required
                        class="w-full px-3.5 py-2.5 text-xs font-bold transition-all bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 text-slate-800">
                        <option value="">-- Choose Leave Type --</option>
                        @foreach ($leaveTypes as $type)
                            <option value="{{ $type->id }}"
                                {{ old('leave_type_id', $leaveRequest->leave_type_id) == $type->id ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @php
                    $isSingleDay =
                        $leaveRequest->start_date->format('Y-m-d') === $leaveRequest->end_date->format('Y-m-d');
                @endphp

                {{-- DURATION TYPE --}}
                <div>
                    <label class="block mb-1.5 text-[11px] font-bold tracking-wider uppercase text-slate-600">Duration
                        Type</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label
                            class="flex items-center gap-2.5 p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition-all text-xs font-bold text-slate-700 bg-slate-50/50">
                            <input type="radio" name="duration_type" value="single" {{ $isSingleDay ? 'checked' : '' }}
                                onclick="toggleDurationType('single')" class="text-emerald-600 focus:ring-emerald-500">
                            <span>Single Day Only (1 Day)</span>
                        </label>
                        <label
                            class="flex items-center gap-2.5 p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition-all text-xs font-bold text-slate-700 bg-slate-50/50">
                            <input type="radio" name="duration_type" value="multiple"
                                {{ !$isSingleDay ? 'checked' : '' }} onclick="toggleDurationType('multiple')"
                                class="text-emerald-600 focus:ring-emerald-500">
                            <span>Multiple Days (Date Range)</span>
                        </label>
                    </div>
                </div>

                {{-- SINGLE DATE CONTAINER --}}
                <div id="single_date_container" class="{{ !$isSingleDay ? 'hidden' : '' }}">
                    <label class="block mb-1.5 text-[11px] font-bold tracking-wider uppercase text-slate-600">Leave
                        Date</label>
                    <input type="date" id="single_date"
                        value="{{ old('start_date', $leaveRequest->start_date->format('Y-m-d')) }}"
                        onchange="syncSingleDate(this.value)"
                        class="w-full px-3.5 py-2.5 text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 text-slate-800">
                </div>

                {{-- MULTIPLE DATES CONTAINER --}}
                <div id="multiple_date_container" class="grid grid-cols-2 gap-3 {{ $isSingleDay ? 'hidden' : '' }}">
                    <div>
                        <label class="block mb-1.5 text-[11px] font-bold tracking-wider uppercase text-slate-600">Start
                            Date</label>
                        <input type="date" id="start_date" name="start_date"
                            value="{{ old('start_date', $leaveRequest->start_date->format('Y-m-d')) }}" required
                            class="w-full px-3.5 py-2.5 text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 text-slate-800">
                    </div>
                    <div>
                        <label class="block mb-1.5 text-[11px] font-bold tracking-wider uppercase text-slate-600">End
                            Date</label>
                        <input type="date" id="end_date" name="end_date"
                            value="{{ old('end_date', $leaveRequest->end_date->format('Y-m-d')) }}" required
                            class="w-full px-3.5 py-2.5 text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 text-slate-800">
                    </div>
                </div>

                {{-- REASON --}}
                <div>
                    <label class="block mb-1.5 text-[11px] font-bold tracking-wider uppercase text-slate-600">Reason /
                        Description</label>
                    <textarea name="reason" required rows="3"
                        class="w-full px-3.5 py-2.5 text-xs font-medium bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 text-slate-800 placeholder-slate-400"
                        placeholder="Detail the purpose of leave request...">{{ old('reason', $leaveRequest->reason) }}</textarea>
                </div>

                {{-- ATTACHMENT --}}
                <div>
                    <label class="block mb-1.5 text-[11px] font-bold tracking-wider uppercase text-slate-600">Attachment
                        (Optional / Doctor's Note)</label>
                    @if ($leaveRequest->attachment_file)
                        <div class="mb-2 text-xs font-medium text-slate-500">
                            <i class="fa-solid fa-paperclip text-slate-400"></i> Current File:
                            <a href="{{ asset('storage/' . $leaveRequest->attachment_file) }}" target="_blank"
                                class="font-bold underline text-emerald-600">View Attachment</a>
                        </div>
                    @endif
                    <input type="file" name="attachment_file"
                        class="w-full px-3.5 py-2 text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl text-slate-700">
                </div>

                {{-- ACTION BUTTONS --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('leave.index') }}"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">
                        Cancel
                    </a>
                    <button type="submit"
                        class="px-5 py-2.5 text-xs font-bold text-white transition-all shadow-md bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-emerald-600/20 active:scale-95">
                        <i class="mr-1.5 fa-solid fa-floppy-disk"></i> Update Leave Request
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleDurationType(type) {
            const singleContainer = document.getElementById('single_date_container');
            const multipleContainer = document.getElementById('multiple_date_container');
            const singleInput = document.getElementById('single_date');
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');

            if (type === 'single') {
                singleContainer.classList.remove('hidden');
                multipleContainer.classList.add('hidden');

                if (singleInput.value) {
                    startDateInput.value = singleInput.value;
                    endDateInput.value = singleInput.value;
                }
            } else {
                singleContainer.classList.add('hidden');
                multipleContainer.classList.remove('hidden');
            }
        }

        function syncSingleDate(val) {
            document.getElementById('start_date').value = val;
            document.getElementById('end_date').value = val;
        }
    </script>
@endsection
