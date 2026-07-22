@extends('layout.master')

@section('title', 'Edit Data Karyawan')

@section('content')
    <div class="w-full px-6 py-8">
        <div class="flex items-center justify-between pb-4 mb-6 border-b border-gray-200">
            <div>
                <h4 class="flex items-center gap-2 text-xl font-bold text-gray-800">
                    <i class="text-blue-600 bi bi-pencil-square"></i> Edit Data Karyawan
                </h4>
            </div>
            <a href="{{ route('employee.index') }}"
                class="px-3 py-1.5 text-xs font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors rounded-lg flex items-center gap-1">
                <i class="bi bi-arrow-left"></i> Kembali ke Daftar
            </a>
        </div>

        <form action="{{ route('employee.update', $employee->id) }}" method="POST"
            class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-xl">
            @csrf
            @method('PUT')

            <div class="p-6">
                <div class="space-y-4">
                    <h5 class="mb-2 text-xs font-bold tracking-wider text-gray-400 uppercase">
                        Informasi Pekerjaan Perusahaan
                    </h5>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="block mb-1 text-xs font-medium text-gray-600">Nama Lengkap <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $employee->name) }}"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                required>
                        </div>

                        <div>
                            <label class="block mb-1 text-xs font-medium text-gray-600">Penempatan Site Location <span
                                    class="text-red-500">*</span></label>

                            @if (Auth::user()->role === 'superadmin')
                                <select name="site_id"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    required>
                                    @foreach ($sites as $site)
                                        <option value="{{ $site->id }}"
                                            {{ $employee->site_id == $site->id ? 'selected' : '' }}>
                                            {{ $site->machine_name }} (Branch: {{ $site->branch->branch_name ?? '-' }})
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <input type="hidden" name="site_id" value="{{ Auth::user()->site_id }}">
                                <input type="text" value="{{ Auth::user()->site->machine_name ?? 'Site Terdaftar' }}"
                                    class="w-full px-3 py-2 text-sm font-bold text-gray-700 bg-gray-100 border border-gray-300 rounded-lg cursor-not-allowed"
                                    readonly>
                            @endif
                        </div>

                        <div>
                            <label class="block mb-1 text-xs font-medium text-gray-600">Status Kepegawaian</label>
                            <select name="status"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="Probation" {{ $employee->status == 'Probation' ? 'selected' : '' }}>Probation
                                    (Percobaan)</option>
                                <option value="Contract" {{ $employee->status == 'Contract' ? 'selected' : '' }}>Contract
                                    (PKWT)</option>
                                <option value="Permanent" {{ $employee->status == 'Permanent' ? 'selected' : '' }}>
                                    Permanent (Tetap)</option>
                                <option value="Daily" {{ $employee->status == 'Daily' ? 'selected' : '' }}>Daily (Harian
                                    Lepas)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block mb-1 text-xs font-medium text-gray-600">Jabatan / Posisi</label>
                            <input type="text" name="position" value="{{ old('position', $employee->position) }}"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block mb-1 text-xs font-medium text-gray-600">Tanggal Mulai Masuk Kerja (Join
                                Date) <span class="text-red-500">*</span></label>
                            <input type="date" name="join_date" value="{{ old('join_date', $employee->join_date) }}"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                required>
                        </div>

                        <div>
                            <label class="block mb-1 text-xs font-medium text-gray-600">Mulai Kontrak</label>
                            <input type="date" name="contract_start_date"
                                value="{{ old('contract_start_date', $employee->contract_start_date) }}"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100 bg-gray-50">
                <div class="text-xs text-gray-400">
                    Tanda bintang (<span class="text-red-500">*</span>) wajib diisi.
                </div>
                <button type="submit"
                    class="px-5 py-2 text-sm font-semibold text-white transition-colors bg-blue-600 rounded-lg shadow-sm hover:bg-blue-700">
                    <i class="bi bi-save"></i> Perbarui Data Karyawan
                </button>
            </div>
        </form>
    </div>
@endsection
