<table class="w-full border-collapse">
    <thead class="text-xs font-bold text-gray-600 uppercase border-b border-gray-200 bg-gray-100/80">
        <tr>
            <th scope="col" class="px-6 py-4 text-left">Nama Lengkap</th>
            <th scope="col" class="px-6 py-4 text-left">Jabatan</th>
            <th scope="col" class="px-6 py-4 text-center">Site Location</th>
            <th scope="col" class="px-6 py-4 text-center">Branch</th>
            <th scope="col" class="px-6 py-4 text-center">Status</th>
            <th scope="col" class="px-6 py-4 text-center">Tanggal Bergabung</th>
            <th scope="col" class="px-6 py-4 text-center">Masa Kerja (Tenure)</th>
            <th scope="col" class="px-6 py-4 text-center">Aksi</th>
        </tr>
    </thead>
    <tbody class="text-xs divide-y divide-gray-100">
        @forelse ($employees as $employee)
            @php
                $joinDate = \Carbon\Carbon::parse($employee->join_date);
                $diff = $joinDate->diff(\Carbon\Carbon::now());

                $tenureParts = [];
                if ($diff->y > 0) {
                    $tenureParts[] = $diff->y . ' Thn';
                }
                if ($diff->m > 0) {
                    $tenureParts[] = $diff->m . ' Bln';
                }
                $tenureParts[] = $diff->d . ' Hr';
                $tenureString = implode(' ', $tenureParts);
            @endphp
            <tr class="transition-colors hover:bg-gray-50/70">
                <td class="px-6 py-4 font-bold text-gray-900">
                    {{ $employee->name }}
                </td>
                <td class="px-6 py-4 text-gray-600">
                    {{ $employee->position ?? '-' }}
                </td>
                <td class="px-6 py-4 font-bold text-center text-gray-800">
                    {{ $employee->site->machine_name ?? '-' }}
                </td>
                <td class="px-6 py-4 text-center">
                    <span
                        class="px-2 py-1 text-[11px] font-semibold text-gray-700 bg-gray-100 border border-gray-200 rounded-md">
                        {{ $employee->site->branch->branch_name ?? ($employee->branch->branch_name ?? '-') }}
                    </span>
                </td>
                <td class="px-6 py-4 text-center">
                    @if ($employee->status == 'Permanent')
                        <span
                            class="px-2.5 py-1 text-[11px] font-bold text-green-700 bg-green-50 border border-green-200 rounded-full">Tetap</span>
                    @elseif($employee->status == 'Contract')
                        <span
                            class="px-2.5 py-1 text-[11px] font-bold text-blue-700 bg-blue-50 border border-blue-200 rounded-full">Kontrak</span>
                    @elseif($employee->status == 'Probation')
                        <span
                            class="px-2.5 py-1 text-[11px] font-bold text-yellow-700 bg-yellow-50 border border-yellow-200 rounded-full">Probation</span>
                    @else
                        <span
                            class="px-2.5 py-1 text-[11px] font-bold text-gray-600 bg-gray-50 border border-gray-200 rounded-full">{{ $employee->status }}</span>
                    @endif
                </td>
                <td class="px-6 py-4 font-medium text-center text-gray-600">
                    {{ \Carbon\Carbon::parse($employee->join_date)->translatedFormat('d M Y') }}
                </td>
                <td class="px-6 py-4 text-center">
                    <span
                        class="px-2.5 py-1 text-[11px] font-extrabold text-blue-700 bg-blue-50 border border-blue-100 rounded-full">
                        <i class="mr-1 fa-solid fa-clock-history"></i> {{ $tenureString }}
                    </span>
                </td>
                <td class="px-6 py-4 text-center">
                    <div class="flex items-center justify-center gap-2">
                        {{-- Tombol Modal Detail --}}
                        <button type="button" onclick="showEmployeeDetail({{ $employee->id }})"
                            class="p-1.5 text-xs text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                            title="Detail Karyawan">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                        {{-- Tombol Edit --}}
                        <a href="{{ route('employee.edit', $employee->id) }}"
                            class="p-1.5 text-xs text-amber-600 hover:bg-amber-50 rounded-lg transition-colors"
                            title="Edit Karyawan">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        {{-- Tombol Hapus --}}
                        <form action="{{ route('employee.destroy', $employee->id) }}" method="POST" class="inline"
                            onsubmit="return confirm('Apakah Anda yakin ingin menghapus data karyawan ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="p-1.5 text-xs text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                                title="Hapus Karyawan">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="px-6 py-12 text-center text-gray-400">
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
