<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse min-w-[850px]">
        <thead>
            <tr
                class="border-b bg-slate-100/70 border-slate-200/80 text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">
                <th scope="col" class="px-4 sm:px-6 py-3.5 text-center w-12">No</th>
                <th scope="col" class="px-4 sm:px-6 py-3.5">Full Name</th>
                <th scope="col" class="px-4 sm:px-6 py-3.5">Position</th>
                <th scope="col" class="px-4 sm:px-6 py-3.5">Site & Branch</th>
                <th scope="col" class="px-4 sm:px-6 py-3.5 text-emerald-700">Gaji Pokok</th>
                <th scope="col" class="px-4 sm:px-6 py-3.5 text-center">Status</th>
                <th scope="col" class="px-4 sm:px-6 py-3.5 text-center">Join Date</th>
                <th scope="col" class="px-4 sm:px-6 py-3.5 text-center w-28">Actions</th>
            </tr>
        </thead>
        <tbody class="text-xs font-medium divide-y divide-slate-100 sm:text-sm text-slate-700">
            @forelse ($employees as $employee)
                <tr class="transition-colors hover:bg-slate-50/80">
                    <td class="px-4 sm:px-6 py-3.5 text-center text-slate-400 font-bold">
                        {{ ($employees->currentPage() - 1) * $employees->perPage() + $loop->iteration }}
                    </td>
                    <td class="px-4 sm:px-6 py-3.5 font-bold text-slate-800">
                        {{ $employee->name }}
                    </td>
                    <td class="px-4 sm:px-6 py-3.5 text-slate-600 font-medium">
                        {{ $employee->position ?? '-' }}
                    </td>
                    <td class="px-4 sm:px-6 py-3.5">
                        <div class="flex flex-col">
                            <span class="font-bold text-slate-800">{{ $employee->site->machine_name ?? '-' }}</span>
                            <span
                                class="text-[11px] font-semibold text-slate-400">{{ $employee->site->branch->branch_name ?? ($employee->branch->branch_name ?? '-') }}</span>
                        </div>
                    </td>
                    <td class="px-4 sm:px-6 py-3.5 font-bold text-emerald-600">
                        Rp {{ number_format($employee->basic_salary ?? 0, 0, ',', '.') }}
                    </td>
                    <td class="px-4 sm:px-6 py-3.5 text-center">
                        <span
                            class="px-2.5 py-1 text-[10px] font-bold text-slate-600 bg-slate-100 border border-slate-200 rounded-full uppercase">{{ $employee->status }}</span>
                    </td>
                    <td class="px-4 sm:px-6 py-3.5 font-medium text-center text-slate-600">
                        {{ \Carbon\Carbon::parse($employee->join_date)->format('d M Y') }}
                    </td>
                    <td class="px-4 sm:px-6 py-3.5 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <button type="button" onclick="showEmployeeDetail({{ $employee->id }})"
                                class="p-1.5 text-blue-600 transition-colors bg-blue-50 rounded-lg hover:bg-blue-600 hover:text-white"
                                title="View Details">
                                <i class="text-xs fa-solid fa-eye"></i>
                            </button>
                            <a href="{{ route('employee.edit', $employee->id) }}"
                                class="p-1.5 text-amber-600 transition-colors bg-amber-50 rounded-lg hover:bg-amber-600 hover:text-white"
                                title="Edit Employee">
                                <i class="text-xs fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('employee.destroy', $employee->id) }}" method="POST" class="inline"
                                onsubmit="return confirm('Yakin menghapus data karyawan ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="p-1.5 text-rose-600 transition-colors bg-rose-50 rounded-lg hover:bg-rose-600 hover:text-white"
                                    title="Delete Employee">
                                    <i class="text-xs fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="p-10 text-center text-slate-400">
                        <div class="flex flex-col items-center justify-center gap-2">
                            <i class="text-3xl opacity-50 text-slate-300 fa-solid fa-folder-open"></i>
                            <p class="text-sm font-bold text-slate-700">No employee records found.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="p-4 border-t border-slate-100">
    {{ $employees->appends(request()->query())->links() }}
</div>
