@extends('layout.master')

@section('title', 'Tambah Data Gaji Karyawan')

@section('content')
    <div class="w-full max-w-3xl mx-auto space-y-6">

        {{-- HEADER --}}
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                    Tambah Data Gaji
                </h1>
                <p class="text-xs sm:text-sm font-medium text-slate-500 mt-0.5">
                    Pilih karyawan untuk menarik otomatis Gaji Pokok, Bank, No Rekening, & Status Probation terdaftar.
                </p>
            </div>
            <a href="{{ route('salary.index') }}"
                class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold transition-all bg-white border text-slate-600 border-slate-200 rounded-xl hover:bg-slate-50 active:scale-95 shrink-0">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Kembali</span>
            </a>
        </div>

        {{-- FORM CARD --}}
        <form action="{{ route('salary.store') }}" method="POST"
            class="overflow-hidden bg-white border shadow-sm border-slate-200/80 rounded-2xl sm:rounded-3xl">
            @csrf

            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-xs font-extrabold tracking-wider uppercase text-slate-700">Rincian Penggajian</h2>
            </div>

            <div class="p-6 space-y-5 sm:p-8">
                {{-- PILIH KARYAWAN --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                        Pilih Karyawan <span class="text-rose-500">*</span>
                    </label>
                    <select name="employee_id" id="employee_select"
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800"
                        required onchange="autoFillSalaryDetails()">
                        <option value="">-- Pilih Karyawan --</option>
                        @foreach ($employees as $emp)
                            @php
                                $branchName = $emp->branch->branch_name ?? ($emp->site->branch->branch_name ?? '-');
                                $calculatedInfo = $emp->getCalculatedSalaryInformation();
                            @endphp
                            <option value="{{ $emp->id }}" data-position="{{ $emp->position ?? '-' }}"
                                data-placement="{{ $branchName }}" data-bank="{{ $emp->bank_name ?? '' }}"
                                data-account="{{ $emp->bank_account_number ?? '' }}"
                                data-salary="{{ (int) ($emp->basic_salary ?? 0) }}" data-info="{{ $calculatedInfo }}">
                                {{ $emp->name }} ({{ $emp->bank_name ?? 'Bank -' }} -
                                {{ $emp->bank_account_number ?? '-' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    {{-- POSISI (READONLY AUTO-FILL) --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-500">
                            Posisi / Jabatan
                        </label>
                        <input type="text" id="position_display"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-bold text-slate-700 bg-slate-100 border border-slate-200 rounded-xl cursor-not-allowed"
                            placeholder="Otomatis dari profil..." readonly>
                    </div>

                    {{-- PLACEMENT / BRANCH (READONLY AUTO-FILL) --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-500">
                            Placement (Branch)
                        </label>
                        <input type="text" id="placement_display"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-bold text-slate-700 bg-slate-100 border border-slate-200 rounded-xl cursor-not-allowed"
                            placeholder="Otomatis dari profil..." readonly>
                    </div>

                    {{-- NAMA BANK --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Nama Bank <span class="text-rose-500">*</span>
                        </label>
                        <select name="bank" id="bank_input"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800"
                            required>
                            <option value="">-- Pilih Bank --</option>
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
                            @foreach ($bankList as $b)
                                <option value="{{ $b }}">{{ $b }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- NOMOR REKENING --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Nomor Rekening <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="account_no" id="account_no_input"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 font-mono placeholder-slate-400"
                            placeholder="e.g. 8830123456" required>
                    </div>

                    {{-- GAJI POKOK (NOMINAL AMOUNT) --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Gaji Pokok Bulanan <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="amount_display"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-bold border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-emerald-50/50 focus:bg-white text-emerald-700 placeholder-slate-400"
                            placeholder="Rp 0" autocomplete="off">
                        <input type="hidden" name="amount" id="amount_real_input" required>
                    </div>

                    {{-- STATUS INFORMASI GAJI (AUTO-SELECTED BY PROBATION CALCULATOR) --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Status Informasi Gaji <span class="text-rose-500">*</span>
                        </label>
                        <select name="information" id="information_input"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800"
                            required>
                            <option value="regular salary">Regular Salary</option>
                            <option value="1st probation">1st Probation</option>
                            <option value="2nd probation">2nd Probation</option>
                            <option value="3rd probation">3rd Probation</option>
                        </select>
                    </div>

                    {{-- MORE INFORMATION --}}
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Catatan Tambahan (More Information)
                        </label>
                        <textarea name="more_information" rows="2"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 placeholder-slate-400"
                            placeholder="Catatan tambahan untuk slip gaji ini..."></textarea>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                <div class="text-xs font-medium text-slate-400">
                    Tanda bintang (<span class="text-rose-500">*</span>) wajib diisi.
                </div>
                <div class="flex items-center gap-2.5">
                    <a href="{{ route('salary.index') }}"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">
                        Batal
                    </a>
                    <button type="submit"
                        class="px-6 py-2.5 text-xs font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-md shadow-blue-600/20 active:scale-95 transition-all">
                        Simpan Data Gaji
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        function formatRupiah(value) {
            let number = value.toString().replace(/\D/g, '');
            return number ? 'Rp ' + new Intl.NumberFormat('id-ID').format(number) : '';
        }

        function autoFillSalaryDetails() {
            const select = document.getElementById('employee_select');
            if (!select || select.selectedIndex === -1) return;

            const opt = select.options[select.selectedIndex];

            const position = opt.getAttribute('data-position') || '-';
            const placement = opt.getAttribute('data-placement') || '-';
            const bank = opt.getAttribute('data-bank') || '';
            const account = opt.getAttribute('data-account') || '';
            const basicSalary = opt.getAttribute('data-salary') || '0';
            const infoStatus = opt.getAttribute('data-info') || 'regular salary';

            document.getElementById('position_display').value = position;
            document.getElementById('placement_display').value = placement;
            document.getElementById('bank_input').value = bank;
            document.getElementById('account_no_input').value = account;
            document.getElementById('information_input').value = infoStatus;

            const amountDisplay = document.getElementById('amount_display');
            const amountReal = document.getElementById('amount_real_input');

            if (basicSalary && basicSalary !== '0') {
                amountReal.value = basicSalary;
                amountDisplay.value = formatRupiah(basicSalary);
            } else {
                amountReal.value = '';
                amountDisplay.value = '';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const amountDisplay = document.getElementById('amount_display');
            const amountReal = document.getElementById('amount_real_input');

            if (amountDisplay && amountReal) {
                amountDisplay.addEventListener('input', function(e) {
                    let rawValue = e.target.value.replace(/\D/g, '');
                    amountReal.value = rawValue ? rawValue : '';
                    e.target.value = formatRupiah(rawValue);
                });
            }
        });
    </script>
@endsection
