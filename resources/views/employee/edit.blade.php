@extends('layout.master')

@section('title', 'Edit Employee Data')

@section('content')
    <div class="w-full max-w-3xl mx-auto space-y-6">

        {{-- 1. HEADER CARD (TERPISAH) --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <nav class="flex items-center gap-2 mb-1.5 text-xs font-bold tracking-wider text-slate-400 uppercase">
                        <a href="{{ route('employee.index') }}" class="transition-colors hover:text-blue-600">Employee
                            Directory</a>
                        <i class="fa-solid fa-chevron-right text-[9px]"></i>
                        <span class="font-extrabold text-blue-600">Edit Record</span>
                    </nav>
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                        Edit Employee Record
                    </h1>
                    <p class="mt-1 text-xs font-medium sm:text-sm text-slate-500">
                        Update employment parameters, salary allocation, and placement information.
                    </p>
                </div>
                <a href="{{ route('employee.index') }}"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-all rounded-xl active:scale-95 shrink-0">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Back</span>
                </a>
            </div>
        </div>

        {{-- 2. FORM CARD --}}
        <form action="{{ route('employee.update', $employee->id) }}" method="POST"
            class="overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">
            @csrf
            @method('PUT')

            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-xs font-extrabold tracking-wider uppercase text-slate-700">Employment Details</h2>
            </div>

            <div class="p-6 space-y-6 sm:p-8">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    {{-- FULL NAME --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Full Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name', $employee->name) }}"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 placeholder-slate-400"
                            placeholder="Full name as per ID..." required>
                    </div>

                    {{-- PHONE NUMBER --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Phone Number <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="phone_number" id="phone_number"
                            value="{{ old('phone_number', $employee->phone_number) }}"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 placeholder-slate-400"
                            placeholder="Phone number..." required>
                    </div>

                    {{-- SITE LOCATION --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Site Placement <span class="text-rose-500">*</span>
                        </label>

                        @if (Auth::user()?->role === 'superadmin')
                            <select name="site_id"
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-bold border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800"
                                required>
                                <option value="">-- Select Site Location --</option>
                                @foreach ($sites as $site)
                                    <option value="{{ $site->id }}"
                                        {{ old('site_id', $employee->site_id) == $site->id ? 'selected' : '' }}>
                                        {{ $site->machine_name }} (Branch: {{ $site->branch->branch_name ?? '-' }})
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <input type="hidden" name="site_id" value="{{ Auth::user()->site_id }}">
                            <input type="text"
                                value="{{ Auth::user()->site->machine_name ?? 'Registered Site' }} (Branch: {{ Auth::user()->site->branch->branch_name ?? '-' }})"
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-bold text-slate-600 bg-slate-100 border border-slate-200 rounded-xl cursor-not-allowed"
                                readonly>
                        @endif
                    </div>

                    {{-- STATUS --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Employment Status
                        </label>
                        <select name="status"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-bold border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800">
                            <option value="Probation"
                                {{ old('status', $employee->status) == 'Probation' ? 'selected' : '' }}>Probation</option>
                            <option value="Contract"
                                {{ old('status', $employee->status) == 'Contract' ? 'selected' : '' }}>Contract</option>
                            <option value="Permanent"
                                {{ old('status', $employee->status) == 'Permanent' ? 'selected' : '' }}>Permanent</option>
                            <option value="Daily" {{ old('status', $employee->status) == 'Daily' ? 'selected' : '' }}>
                                Daily</option>
                        </select>
                    </div>

                    {{-- GAJI POKOK --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Basic Salary (Gaji Pokok)
                        </label>
                        <input type="text" id="basic_salary_display"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-black border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-emerald-700 placeholder-slate-400"
                            placeholder="Rp 0" autocomplete="off">
                        <input type="hidden" name="basic_salary" id="basic_salary_real"
                            value="{{ old('basic_salary', (int) ($employee->basic_salary ?? 0)) }}">
                    </div>

                    {{-- BANK NAME --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Bank Name
                        </label>
                        <select name="bank_name"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800">
                            <option value="">-- Select Bank --</option>
                            @php
                                $bankList = [
                                    'BCA',
                                    'Bank Mandiri',
                                    'BRI',
                                    'BNI',
                                    'BSI',
                                    'CIMB Niaga',
                                    'Bank Permata',
                                    'Bank Danamon',
                                    'BTN',
                                    'Maybank',
                                    'Panin Bank',
                                    'OCBC NISP',
                                    'Bank BJB',
                                    'Bank DKI',
                                    'Bank Jatim',
                                    'Bank Jateng',
                                    'Seabank',
                                    'Bank Jago',
                                    'Bank Neo Commerce',
                                ];
                            @endphp
                            @foreach ($bankList as $bank)
                                <option value="{{ $bank }}"
                                    {{ old('bank_name', $employee->bank_name) == $bank ? 'selected' : '' }}>
                                    {{ $bank }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- BANK ACCOUNT NUMBER --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Bank Account Number
                        </label>
                        <input type="text" name="bank_account_number"
                            value="{{ old('bank_account_number', $employee->bank_account_number) }}"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-semibold border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 placeholder-slate-400 font-mono"
                            placeholder="e.g. 8830123456">
                    </div>

                    {{-- ALASAN PERUBAHAN GAJI --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Alasan Perubahan Gaji (Jika ada)
                        </label>
                        <input type="text" name="salary_change_reason"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 placeholder-slate-400"
                            placeholder="e.g. Promosi Jabatan, Penyesuaian UMK">
                    </div>

                    {{-- POSITION --}}
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Position / Job Title
                        </label>
                        <input type="text" name="position" value="{{ old('position', $employee->position) }}"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 placeholder-slate-400"
                            placeholder="e.g. Supervisor, Operator, Admin">
                    </div>

                    {{-- JOIN DATE --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Join Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="join_date"
                            value="{{ old('join_date', isset($employee->join_date) ? \Carbon\Carbon::parse($employee->join_date)->format('Y-m-d') : '') }}"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-semibold border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800"
                            required>
                    </div>

                    {{-- CONTRACT START DATE --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Contract Start Date
                        </label>
                        <input type="date" name="contract_start_date"
                            value="{{ old('contract_start_date', isset($employee->contract_start_date) ? \Carbon\Carbon::parse($employee->contract_start_date)->format('Y-m-d') : '') }}"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-semibold border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800">
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                <div class="text-xs font-medium text-slate-400">
                    Asterisk (<span class="text-rose-500">*</span>) fields are required.
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('employee.index') }}"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">
                        Discard
                    </a>
                    <button type="submit"
                        class="px-6 py-2.5 text-xs font-bold text-white transition-all bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-600/20 active:scale-[0.98]">
                        <i class="mr-1.5 fa-solid fa-floppy-disk"></i> Update Employee
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const salaryDisplay = document.getElementById('basic_salary_display');
            const salaryReal = document.getElementById('basic_salary_real');

            function formatRupiah(value) {
                let number = value.toString().replace(/\D/g, '');
                return number ? 'Rp ' + new Intl.NumberFormat('id-ID').format(number) : '';
            }

            if (salaryDisplay && salaryReal) {
                if (salaryReal.value && salaryReal.value !== '0') {
                    salaryDisplay.value = formatRupiah(salaryReal.value);
                }

                salaryDisplay.addEventListener('input', function(e) {
                    let rawValue = e.target.value.replace(/\D/g, '');
                    salaryReal.value = rawValue ? rawValue : 0;
                    e.target.value = formatRupiah(rawValue);
                });
            }

            const phoneInput = document.getElementById('phone_number');
            if (phoneInput) {
                phoneInput.addEventListener('input', function(e) {
                    let value = e.target.value;
                    let digits = value.replace(/\D/g, '');

                    if (digits.startsWith('0')) {
                        digits = '62' + digits.substring(1);
                    }
                    if (!digits.startsWith('62') && digits.length > 0) {
                        digits = '62' + digits;
                    }

                    digits = digits.substring(0, 14);

                    let formatted = '';
                    if (digits.length > 0) formatted = '+' + digits.substring(0, 2);
                    if (digits.length > 2) formatted += ' ' + digits.substring(2, 5);
                    if (digits.length > 5) formatted += '-' + digits.substring(5, 9);
                    if (digits.length > 9) formatted += '-' + digits.substring(9, 13);

                    e.target.value = formatted;
                });

                phoneInput.addEventListener('focus', function(e) {
                    if (!e.target.value) e.target.value = '+62 ';
                });

                phoneInput.addEventListener('blur', function(e) {
                    if (e.target.value === '+62 ' || e.target.value === '+62') e.target.value = '';
                });
            }
        });
    </script>
@endsection
