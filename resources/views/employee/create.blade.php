@extends('layout.master')

@section('title', 'Tambah Karyawan')

@section('content')
    <div class="w-full max-w-3xl mx-auto space-y-6">

        {{-- 1. HEADER CARD --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <nav class="flex items-center gap-2 mb-1.5 text-xs font-bold tracking-wider text-slate-400 uppercase">
                        <a href="{{ route('employee.index') }}" class="transition-colors hover:text-blue-600">Daftar
                            Karyawan</a>
                        <i class="fa-solid fa-chevron-right text-[9px]"></i>
                        <span class="font-extrabold text-blue-600">Karyawan Baru</span>
                    </nav>
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                        Tambah Karyawan
                    </h1>
                    <p class="mt-1 text-xs font-medium sm:text-sm text-slate-500">
                        Daftarkan karyawan baru ke dalam sistem.
                    </p>
                </div>
                <a href="{{ route('employee.index') }}"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-all rounded-xl active:scale-95 shrink-0">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Kembali</span>
                </a>
            </div>
        </div>

        {{-- 2. FORM CARD --}}
        <form action="{{ route('employee.store') }}" method="POST"
            class="overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">
            @csrf

            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-xs font-extrabold tracking-wider uppercase text-slate-700">Informasi Kepegawaian</h2>
            </div>

            <div class="p-6 space-y-6 sm:p-8">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    {{-- FULL NAME --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Nama Lengkap <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 placeholder-slate-400"
                            placeholder="Nama lengkap sesuai KTP..." required>
                    </div>

                    {{-- NIK --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            NIK (Nomor KTP)
                        </label>
                        <input type="text" name="nik" value="{{ old('nik') }}" maxlength="16"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 placeholder-slate-400 font-mono"
                            placeholder="NIK 16 digit...">
                    </div>

                    {{-- EMAIL --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Alamat Email
                        </label>
                        <input type="email" name="email" value="{{ old('email') }}"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 placeholder-slate-400"
                            placeholder="employee@company.com">
                    </div>

                    {{-- PHONE NUMBER --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Nomor Telepon <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="phone_number" id="phone_number" value="{{ old('phone_number') }}"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 placeholder-slate-400"
                            placeholder="+62 896-0851-4923" required autocomplete="off">
                    </div>

                    {{-- SITE LOCATION --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Penempatan Lokasi <span class="text-rose-500">*</span>
                        </label>
                        @if (Auth::user()?->role === 'superadmin')
                            <select name="site_id"
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-bold border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 cursor-pointer"
                                required>
                                <option value="">-- Pilih Lokasi Mesin --</option>
                                @foreach ($sites as $site)
                                    <option value="{{ $site->id }}" {{ old('site_id') == $site->id ? 'selected' : '' }}>
                                        {{ $site->machine_name }} (Cabang: {{ $site->branch->branch_name ?? '-' }})
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <input type="hidden" name="site_id" value="{{ Auth::user()->site_id }}">
                            <input type="text"
                                value="{{ Auth::user()->site->machine_name ?? 'Lokasi Terdaftar' }} (Branch: {{ Auth::user()->site->branch->branch_name ?? '-' }})"
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-bold text-slate-600 bg-slate-100 border border-slate-200 rounded-xl cursor-not-allowed"
                                readonly>
                        @endif
                    </div>

                    {{-- STATUS --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Status Kepegawaian
                        </label>
                        <select name="status"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-bold border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 cursor-pointer">
                            <option value="Probation" {{ old('status') == 'Probation' ? 'selected' : '' }}>Masa Percobaan
                            </option>
                            <option value="Contract" {{ old('status') == 'Contract' ? 'selected' : '' }}>Kontrak</option>
                            <option value="Permanent" {{ old('status') == 'Permanent' ? 'selected' : '' }}>Tetap
                            </option>
                            <option value="Daily" {{ old('status') == 'Daily' ? 'selected' : '' }}>Harian</option>
                        </select>
                    </div>

                    {{-- POSITION --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Jabatan
                        </label>
                        <input type="text" name="position" value="{{ old('position') }}"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 placeholder-slate-400"
                            placeholder="Contoh: Supervisor, Operator, Admin">
                    </div>

                    {{-- GAJI POKOK --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Gaji Pokok
                        </label>
                        <input type="text" id="basic_salary_display"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-black border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-emerald-700 placeholder-slate-400"
                            placeholder="Rp 0" autocomplete="off">
                        <input type="hidden" name="basic_salary" id="basic_salary_real"
                            value="{{ old('basic_salary', 0) }}">
                    </div>

                    {{-- BANK NAME --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Nama Bank
                        </label>
                        <select name="bank_name"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 cursor-pointer">
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
                            @foreach ($bankList as $bank)
                                <option value="{{ $bank }}" {{ old('bank_name') == $bank ? 'selected' : '' }}>
                                    {{ $bank }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- BANK ACCOUNT NUMBER --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Nomor Rekening
                        </label>
                        <input type="text" name="bank_account_number" value="{{ old('bank_account_number') }}"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-semibold border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 placeholder-slate-400 font-mono"
                            placeholder="Contoh: 8830123456">
                    </div>

                    {{-- MCU STATUS --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Lulus MCU
                        </label>
                        <select name="mcu"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-bold border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 cursor-pointer">
                            <option value="no" {{ old('mcu') == 'no' ? 'selected' : '' }}>TIDAK</option>
                            <option value="yes" {{ old('mcu') == 'yes' ? 'selected' : '' }}>YA</option>
                        </select>
                    </div>

                    {{-- TLD BADGE --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Kartu TLD
                        </label>
                        <select name="tld"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-bold border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 cursor-pointer">
                            <option value="no" {{ old('tld') == 'no' ? 'selected' : '' }}>TIDAK</option>
                            <option value="yes" {{ old('tld') == 'yes' ? 'selected' : '' }}>YA</option>
                        </select>
                    </div>

                    {{-- JOIN DATE --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Tanggal Bergabung <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="join_date" value="{{ old('join_date') }}"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-semibold border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800"
                            required>
                    </div>

                    {{-- CONTRACT START DATE --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Tanggal Mulai Kontrak
                        </label>
                        <input type="date" name="contract_start_date" value="{{ old('contract_start_date') }}"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-semibold border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800">
                    </div>

                    {{-- INFORMASI PKWT --}}
                    <div class="pt-4 border-t md:col-span-2 border-slate-100">

                        <div class="flex items-center gap-2 mb-4">
                            <div class="flex items-center justify-center w-8 h-8 text-blue-600 rounded-lg bg-blue-50">
                                <i class="fa-regular fa-file-lines"></i>
                            </div>

                            <div>
                                <h3 class="text-xs font-extrabold tracking-wider uppercase text-slate-800">
                                    Informasi PKWT
                                </h3>

                                <p class="mt-0.5 text-xs font-medium text-slate-400">
                                    Opsional, dapat dilengkapi kemudian.
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                            {{-- NOMOR PKWT --}}
                            <div class="space-y-1.5">
                                <label for="pkwt_number"
                                    class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                                    Nomor PKWT
                                </label>

                                <input type="text" id="pkwt_number" name="pkwt_number" maxlength="150"
                                    value="{{ old('pkwt_number') }}" placeholder="Masukkan nomor dokumen PKWT"
                                    class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800">

                                @error('pkwt_number')
                                    <p class="text-xs font-medium text-rose-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            {{-- TANGGAL PKWT --}}
                            <div class="space-y-1.5">
                                <label for="pkwt_date"
                                    class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                                    Tanggal PKWT
                                </label>

                                <input type="date" id="pkwt_date" name="pkwt_date" value="{{ old('pkwt_date') }}"
                                    class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-semibold border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800">

                                @error('pkwt_date')
                                    <p class="text-xs font-medium text-rose-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                        </div>
                    </div>
                </div>

            </div>

            <div class="flex items-center justify-between px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                <div class="text-xs font-medium text-slate-400">
                    Kolom bertanda (<span class="text-rose-500">*</span>) wajib diisi.
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('employee.index') }}"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">
                        Batal
                    </a>
                    <button type="submit"
                        class="px-6 py-2.5 text-xs font-bold text-white transition-all bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-600/20 active:scale-[0.98] cursor-pointer">
                        <i class="mr-1.5 fa-solid fa-floppy-disk"></i> Simpan Karyawan
                    </button>
                </div>
            </div>
        </form>
        <p class="text-sm text-slate-500">Setelah karyawan disimpan, buka Ubah Karyawan untuk menambahkan tunjangan bulanan.</p>
    </div>

    @push('scripts')
        <script>
            function initCreateEmployeeScripts() {
                const salaryDisplay = document.getElementById('basic_salary_display');
                const salaryReal = document.getElementById('basic_salary_real');

                function formatRupiah(value) {
                    let number = String(value).replace(/\D/g, '');
                    return number ? 'Rp ' + new Intl.NumberFormat('id-ID').format(number) : '';
                }

                if (salaryDisplay && salaryReal) {
                    if (salaryReal.value && salaryReal.value !== '0') {
                        salaryDisplay.value = formatRupiah(salaryReal.value);
                    }

                    salaryDisplay.replaceWith(salaryDisplay.cloneNode(true));
                    const newSalaryDisplay = document.getElementById('basic_salary_display');

                    newSalaryDisplay.addEventListener('input', function(e) {
                        let rawValue = e.target.value.replace(/\D/g, '');
                        salaryReal.value = rawValue ? rawValue : 0;
                        e.target.value = formatRupiah(rawValue);
                    });
                }

                const phoneInput = document.getElementById('phone_number');
                if (phoneInput) {
                    phoneInput.replaceWith(phoneInput.cloneNode(true));
                    const newPhoneInput = document.getElementById('phone_number');

                    newPhoneInput.addEventListener('input', function(e) {
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

                    newPhoneInput.addEventListener('focus', function(e) {
                        if (!e.target.value) e.target.value = '+62 ';
                    });

                    newPhoneInput.addEventListener('blur', function(e) {
                        if (e.target.value === '+62 ' || e.target.value === '+62') e.target.value = '';
                    });
                }
            }

            document.addEventListener('DOMContentLoaded', initCreateEmployeeScripts);

            if (window.up) {
                up.on('up:fragment:inserted', function() {
                    initCreateEmployeeScripts();
                });
            }
        </script>
    @endpush
@endsection
