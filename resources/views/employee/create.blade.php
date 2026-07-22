@extends('layout.master')

@section('title', 'Tambah Karyawan Baru')

@section('content')
    <div class="w-full px-6 py-8">
        <div class="flex items-center justify-between pb-4 mb-6 border-b border-gray-200">
            <div>
                <h4 class="flex items-center gap-2 text-xl font-bold text-gray-800">
                    <i class="text-blue-600 bi bi-person-plus"></i> Form Pendaftaran Karyawan
                </h4>
            </div>
            <a href="{{ route('employee.index') }}"
                class="px-3 py-1.5 text-xs font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors rounded-lg flex items-center gap-1">
                <i class="bi bi-arrow-left"></i> Kembali ke Daftar
            </a>
        </div>

        <form action="{{ route('employee.store') }}" method="POST"
            class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-xl">
            @csrf

            <div class="flex overflow-x-auto border-b border-gray-200 bg-gray-50 whitespace-nowrap scrollbar-none">
                <button type="button"
                    class="flex items-center gap-2 px-5 py-3 text-sm font-semibold text-blue-600 transition-all border-b-2 border-blue-600 tab-button">
                    <i class="bi bi-briefcase"></i> Informasi Kepegawaian
                </button>
            </div>

            <div class="p-6">
                <div id="tab-core-hr" class="space-y-4 tab-content">
                    <h5 class="mb-2 text-xs font-bold tracking-wider text-gray-400 uppercase">
                        Informasi Pekerjaan Perusahaan
                    </h5>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="block mb-1 text-xs font-medium text-gray-600">Nama Lengkap <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="name"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Nama sesuai KTP..." required>
                        </div>

                        <div>
                            <label class="block mb-1 text-xs font-medium text-gray-600">Penempatan Site Location <span
                                    class="text-red-500">*</span></label>

                            {{-- SUPERADMIN PILIH SITE --}}
                            @if (Auth::user()->role === 'superadmin')
                                <select name="site_id"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    required>
                                    <option value="">-- Pilih Site Location --</option>
                                    @foreach ($sites as $site)
                                        <option value="{{ $site->id }}">
                                            {{ $site->machine_name }} (Branch: {{ $site->branch->branch_name ?? '-' }})
                                        </option>
                                    @endforeach
                                </select>
                                {{-- SITE ADMIN TERKUNCI OTOMATIS --}}
                            @else
                                <input type="hidden" name="site_id" value="{{ Auth::user()->site_id }}">
                                <input type="text"
                                    value="{{ Auth::user()->site->machine_name ?? 'Site Terdaftar' }} (Branch: {{ Auth::user()->site->branch->branch_name ?? '-' }})"
                                    class="w-full px-3 py-2 text-sm font-bold text-gray-700 bg-gray-100 border border-gray-300 rounded-lg cursor-not-allowed"
                                    readonly>
                            @endif
                            <p class="text-[11px] text-gray-400 mt-1">Branch kantor akan terisi otomatis sesuai dengan Site
                                yang dipilih.</p>
                        </div>

                        <div>
                            <label class="block mb-1 text-xs font-medium text-gray-600">Status Kepegawaian</label>
                            <select name="status"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="Probation" selected>Probation (Percobaan)</option>
                                <option value="Contract">Contract (PKWT)</option>
                                <option value="Permanent">Permanent (Tetap)</option>
                                <option value="Daily">Daily (Harian Lepas)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block mb-1 text-xs font-medium text-gray-600">Jabatan / Posisi</label>
                            <input type="text" name="position"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Contoh: Supervisor, Operator, Admin">
                        </div>

                        <div>
                            <label class="block mb-1 text-xs font-medium text-gray-600">Tanggal Mulai Masuk Kerja (Join
                                Date) <span class="text-red-500">*</span></label>
                            <input type="date" name="join_date"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                required>
                        </div>

                        <div>
                            <label class="block mb-1 text-xs font-medium text-gray-600">Mulai Kontrak</label>
                            <input type="date" name="contract_start_date"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100 bg-gray-50">
                <div class="text-xs text-gray-400">
                    Tanda bintang (<span class="text-red-500">*</span>) wajib diisi.
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit"
                        class="px-5 py-2 text-sm font-semibold text-white transition-colors bg-green-600 rounded-lg shadow-sm hover:bg-green-700">
                        <i class="bi bi-check-circle"></i> Simpan Data Karyawan
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
