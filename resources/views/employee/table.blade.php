<table class="w-full border-collapse">
    <thead class="text-gray-700 bg-gray-100">
        <tr>
            <th scope="col" class="px-6 py-4 text-left">Nama Lengkap</th>
            <th scope="col" class="px-6 py-4 text-left">Jabatan</th>
            <th scope="col" class="px-6 py-4 text-center">Site Location</th>
            <th scope="col" class="px-6 py-4 text-center">Branch</th>
            <th scope="col" class="px-6 py-4 text-center">Status</th>
            <th scope="col" class="px-6 py-4 text-center">Tanggal Bergabung</th>
            <th scope="col" class="px-6 py-4 text-center">Aksi</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-100">
        @forelse ($employees as $employee)
            <tr class="transition-colors hover:bg-gray-50/70">
                <!-- Nama Lengkap -->
                <td class="px-6 py-4 font-semibold text-gray-900">
                    {{ $employee->name }}
                </td>
                <!-- Jabatan -->
                <td class="px-6 py-4 text-gray-600">
                    {{ $employee->position ?? '-' }}
                </td>
                <!-- Site -->
                <td class="px-6 py-4 font-bold text-center text-gray-800">
                    {{ $employee->site->machine_name ?? '-' }}
                </td>
                <!-- Branch (Diambil dari relasi Site -> Branch) -->
                <td class="px-6 py-4 text-center">
                    <span
                        class="px-2 py-1 text-xs font-medium text-gray-700 bg-gray-100 border border-gray-200 rounded-md">
                        {{ $employee->site->branch->branch_name ?? ($employee->branch->branch_name ?? '-') }}
                    </span>
                </td>
                <!-- Status Kepegawaian -->
                <td class="px-6 py-4 text-center">
                    @if ($employee->status == 'Permanent')
                        <span
                            class="px-2.5 py-1 text-xs font-semibold text-green-700 bg-green-50 border border-green-200 rounded-full">Tetap</span>
                    @elseif($employee->status == 'Contract')
                        <span
                            class="px-2.5 py-1 text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-200 rounded-full">Kontrak</span>
                    @elseif($employee->status == 'Probation')
                        <span
                            class="px-2.5 py-1 text-xs font-semibold text-yellow-700 bg-yellow-50 border border-yellow-200 rounded-full">Probation</span>
                    @else
                        <span
                            class="px-2.5 py-1 text-xs font-semibold text-gray-600 bg-gray-50 border border-gray-200 rounded-full">{{ $employee->status }}</span>
                    @endif
                </td>
                <!-- Join Date -->
                <td class="px-6 py-4 text-center text-gray-500">
                    {{ \Carbon\Carbon::parse($employee->join_date)->translatedFormat('d M Y') }}
                </td>
                <!-- Tombol Aksi -->
                <td class="px-6 py-4 text-center">
                    <div class="flex items-center justify-center gap-2">
                        <a href="{{ route('employee.show', $employee->id) }}"
                            class="p-1.5 text-xs text-black hover:text-blue-600 hover:bg-gray-100 rounded-lg transition-colors"
                            title="Detail Karyawan">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                        <a href="{{ route('employee.edit', $employee->id) }}"
                            class="p-1.5 text-xs text-gray-500 hover:text-yellow-600 hover:bg-gray-100 rounded-lg transition-colors"
                            title="Edit Karyawan">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        <form action="{{ route('employee.destroy', $employee->id) }}" method="POST" class="inline"
                            onsubmit="return confirm('Apakah Anda yakin ingin menghapus data karyawan ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="p-1.5 text-xs text-gray-500 hover:text-red-600 hover:bg-gray-100 rounded-lg transition-colors"
                                title="Hapus Karyawan">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                    <div class="flex flex-col items-center justify-center gap-2">
                        <i class="text-4xl text-gray-300 bi bi-folder-x"></i>
                        <p class="text-sm font-medium">Belum ada data karyawan terdaftar.</p>
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="p-4">
    {{ $employees->links() }}
</div>
