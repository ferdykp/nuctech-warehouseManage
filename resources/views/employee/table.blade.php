<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse min-w-[950px]">
        <thead>
            <tr
                class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 bg-slate-50 border-b border-slate-100">
                <th scope="col" class="w-12 px-4 py-4 text-center">No</th>
                <th scope="col" class="px-6 py-4">Employee Identity</th>
                <th scope="col" class="px-6 py-4">Position</th>
                <th scope="col" class="px-6 py-4">Site & Branch</th>
                <th scope="col" class="px-6 py-4 text-emerald-700">Gaji Pokok</th>
                <th scope="col" class="px-4 py-4 text-center">MCU</th>
                <th scope="col" class="px-4 py-4 text-center">TLD</th>
                <th scope="col" class="px-6 py-4 text-center">Status</th>
                <th scope="col" class="px-6 py-4 text-center">Join Date</th>
                <th scope="col" class="w-32 px-6 py-4 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="text-xs font-medium divide-y divide-slate-100 text-slate-700">
            @forelse ($employees as $employee)
                <tr class="transition-colors hover:bg-slate-50/60">
                    <td class="px-4 py-4 font-bold text-center text-slate-400">
                        {{ ($employees->currentPage() - 1) * $employees->perPage() + $loop->iteration }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-bold text-slate-900">
                            {{ $employee->name }}
                        </div>
                        <div class="text-[11px] font-medium text-slate-400 mt-0.5 flex flex-col gap-0.5">
                            @if ($employee->nik)
                                <span><i class="fa-regular fa-id-card text-[10px] me-1"></i>{{ $employee->nik }}</span>
                            @endif
                            @if ($employee->email)
                                <span><i
                                        class="fa-regular fa-envelope text-[10px] me-1"></i>{{ $employee->email }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 font-semibold text-slate-600">
                        {{ $employee->position ?? '-' }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col">
                            <span class="font-bold text-slate-900">{{ $employee->site->machine_name ?? '-' }}</span>
                            <span class="text-[11px] font-semibold text-slate-400 mt-0.5">
                                {{ $employee->site->branch->branch_name ?? ($employee->branch->branch_name ?? '-') }}
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-xs font-black text-emerald-600">
                        Rp {{ number_format($employee->basic_salary ?? 0, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-4 text-center">
                        @if ($employee->mcu === 'yes')
                            <span
                                class="px-2 py-0.5 text-[10px] font-black text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-md">YES</span>
                        @else
                            <span
                                class="px-2 py-0.5 text-[10px] font-black text-slate-400 bg-slate-100 border border-slate-200 rounded-md">NO</span>
                        @endif
                    </td>
                    <td class="px-4 py-4 text-center">
                        @if ($employee->tld === 'yes')
                            <span
                                class="px-2 py-0.5 text-[10px] font-black text-blue-700 bg-blue-50 border border-blue-200 rounded-md">YES</span>
                        @else
                            <span
                                class="px-2 py-0.5 text-[10px] font-black text-slate-400 bg-slate-100 border border-slate-200 rounded-md">NO</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        @php
                            $statusClasses = match ($employee->status) {
                                'Permanent' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                'Contract' => 'bg-blue-50 text-blue-800 border-blue-200',
                                'Probation' => 'bg-amber-50 text-amber-800 border-amber-200',
                                default => 'bg-slate-100 text-slate-700 border-slate-200',
                            };
                        @endphp
                        <span
                            class="px-2.5 py-1 text-[10px] font-extrabold uppercase border rounded-full {{ $statusClasses }}">
                            {{ $employee->status }}
                        </span>
                    </td>
                    <td class="px-6 py-4 font-semibold text-center text-slate-600">
                        {{ \Carbon\Carbon::parse($employee->join_date)->format('d M Y') }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            {{-- VIEW DETAIL --}}
                            <button type="button" onclick="showEmployeeDetail({{ $employee->id }})"
                                class="flex items-center justify-center w-8 h-8 text-blue-600 transition-all border border-blue-100 rounded-xl bg-blue-50 hover:bg-blue-600 hover:text-white active:scale-95"
                                title="View Details">
                                <i class="text-xs fa-solid fa-eye"></i>
                            </button>

                            {{-- EDIT --}}
                            <a href="{{ route('employee.edit', $employee->id) }}"
                                class="flex items-center justify-center w-8 h-8 transition-all border rounded-xl text-amber-600 bg-amber-50 border-amber-100 hover:bg-amber-600 hover:text-white active:scale-95"
                                title="Edit Employee">
                                <i class="text-xs fa-solid fa-pen-to-square"></i>
                            </a>

                            {{-- DELETE --}}
                            <form action="{{ route('employee.destroy', $employee->id) }}" method="POST" class="inline"
                                onsubmit="return confirm('Yakin menghapus data karyawan ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="flex items-center justify-center w-8 h-8 transition-all border rounded-xl text-rose-600 bg-rose-50 border-rose-100 hover:bg-rose-600 hover:text-white active:scale-95"
                                    title="Delete Employee">
                                    <i class="text-xs fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="p-12 text-center text-slate-400">
                        <div
                            class="flex items-center justify-center w-12 h-12 mx-auto mb-3 text-xl rounded-2xl bg-slate-100 text-slate-400">
                            <i class="fa-solid fa-users-slash"></i>
                        </div>
                        <p class="text-sm font-bold text-slate-800">No Employee Records Found</p>
                        <p class="mt-1 text-xs text-slate-400">Start adding staff members or try clearing search
                            filters.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="p-4 border-t sm:p-6 border-slate-100 bg-slate-50/30">
    {{ $employees->appends(request()->query())->links() }}
</div>
