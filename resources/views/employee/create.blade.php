@extends('layout.master')

@section('title', 'Add New Employee')

@section('content')
    <div class="w-full max-w-3xl mx-auto space-y-6">

        {{-- HEADER --}}
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                    Add New Employee
                </h1>
                <p class="text-xs sm:text-sm font-medium text-slate-500 mt-0.5">
                    Register a new staff member into the system database.
                </p>
            </div>
            <a href="{{ route('employee.index') }}"
                class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold transition-all bg-white border text-slate-600 border-slate-200 rounded-xl hover:bg-slate-50 active:scale-95 shrink-0">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back</span>
            </a>
        </div>

        {{-- FORM CARD --}}
        <form action="{{ route('employee.store') }}" method="POST"
            class="overflow-hidden bg-white border shadow-sm border-slate-200/80 rounded-2xl sm:rounded-3xl">
            @csrf

            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-xs font-extrabold tracking-wider uppercase text-slate-700">Employment Details</h2>
            </div>

            <div class="p-6 space-y-5 sm:p-8">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    {{-- FULL NAME --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Full Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 placeholder-slate-400"
                            placeholder="Full name as per ID..." required>
                    </div>

                    {{-- PHONE NUMBER --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Phone Number <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="phone_number" id="phone_number" value="{{ old('phone_number') }}"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 placeholder-slate-400"
                            placeholder="+62 896-0851-4923" required autocomplete="off">
                    </div>

                    {{-- SITE LOCATION --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Site Placement <span class="text-rose-500">*</span>
                        </label>

                        @if (Auth::user()->role === 'superadmin')
                            <select name="site_id"
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800"
                                required>
                                <option value="">-- Select Site Location --</option>
                                @foreach ($sites as $site)
                                    <option value="{{ $site->id }}" {{ old('site_id') == $site->id ? 'selected' : '' }}>
                                        {{ $site->machine_name }} (Branch: {{ $site->branch->branch_name ?? '-' }})
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <input type="hidden" name="site_id" value="{{ Auth::user()->site_id }}">
                            <input type="text"
                                value="{{ Auth::user()->site->machine_name ?? 'Registered Site' }} (Branch: {{ Auth::user()->site->branch->branch_name ?? '-' }})"
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-bold text-slate-700 bg-slate-100 border border-slate-200 rounded-xl cursor-not-allowed"
                                readonly>
                        @endif
                        <p class="text-[11px] text-slate-400 mt-1">Branch will automatically sync based on the selected
                            Site.</p>
                    </div>

                    {{-- STATUS --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Employment Status
                        </label>
                        <select name="status"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800">
                            <option value="Probation" {{ old('status') == 'Probation' ? 'selected' : '' }}>Probation
                            </option>
                            <option value="Contract" {{ old('status') == 'Contract' ? 'selected' : '' }}>Contract</option>
                            <option value="Permanent" {{ old('status') == 'Permanent' ? 'selected' : '' }}>Permanent
                            </option>
                            <option value="Daily" {{ old('status') == 'Daily' ? 'selected' : '' }}>Daily</option>
                        </select>
                    </div>

                    {{-- GAJI POKOK --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Basic Salary (Gaji Pokok)
                        </label>
                        <input type="text" id="basic_salary_display"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-bold border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-emerald-700 placeholder-slate-400"
                            placeholder="Rp 0" autocomplete="off">
                        <input type="hidden" name="basic_salary" id="basic_salary_real"
                            value="{{ old('basic_salary', 0) }}">
                    </div>

                    {{-- BANK NAME (DROPDOWN) --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Bank Name
                        </label>
                        <select name="bank_name"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800">
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
                                <option value="{{ $bank }}" {{ old('bank_name') == $bank ? 'selected' : '' }}>
                                    {{ $bank }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- BANK ACCOUNT NUMBER --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Bank Account Number
                        </label>
                        <input type="text" name="bank_account_number" value="{{ old('bank_account_number') }}"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 placeholder-slate-400 font-mono"
                            placeholder="e.g. 8830123456">
                    </div>

                    {{-- POSITION --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Position / Job Title
                        </label>
                        <input type="text" name="position" value="{{ old('position') }}"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 placeholder-slate-400"
                            placeholder="e.g. Supervisor, Operator, Admin">
                    </div>

                    {{-- JOIN DATE --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Join Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="join_date" value="{{ old('join_date') }}"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800"
                            required>
                    </div>

                    {{-- CONTRACT START DATE --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Contract Start Date
                        </label>
                        <input type="date" name="contract_start_date" value="{{ old('contract_start_date') }}"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800">
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                <div class="text-xs font-medium text-slate-400">
                    Asterisk (<span class="text-rose-500">*</span>) fields are required.
                </div>
                <div class="flex items-center gap-2.5">
                    <a href="{{ route('employee.index') }}"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">
                        Cancel
                    </a>
                    <button type="submit"
                        class="px-6 py-2.5 text-xs font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-md shadow-blue-600/20 active:scale-95 transition-all">
                        Save Employee
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
                let number = value.replace(/\D/g, '');
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
