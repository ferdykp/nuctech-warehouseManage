<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse min-w-[800px]">
        <thead>
            <tr
                class="border-b bg-slate-100/70 border-slate-200/80 text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">
                <th scope="col" class="px-4 sm:px-6 py-3.5 text-center w-12">No</th>
                <th scope="col" class="px-4 sm:px-6 py-3.5">Full Name</th>
                <th scope="col" class="px-4 sm:px-6 py-3.5">Position</th>
                <th scope="col" class="px-4 sm:px-6 py-3.5">Site & Branch</th>
                <th scope="col" class="px-4 sm:px-6 py-3.5 text-center">Status</th>
                <th scope="col" class="px-4 sm:px-6 py-3.5 text-center">Join Date</th>
                <th scope="col" class="px-4 sm:px-6 py-3.5 text-center">Tenure</th>
                <th scope="col" class="px-4 sm:px-6 py-3.5 text-center w-28">Actions</th>
            </tr>
        </thead>
        <tbody class="text-xs font-medium divide-y divide-slate-100 sm:text-sm text-slate-700">
            @forelse ($employees as $employee)
                @php
                    $joinDate = \Carbon\Carbon::parse($employee->join_date);
                    $diff = $joinDate->diff(\Carbon\Carbon::now());

                    $tenureParts = [];
                    if ($diff->y > 0) {
                        $tenureParts[] = $diff->y . ' Yrs';
                    }
                    if ($diff->m > 0) {
                        $tenureParts[] = $diff->m . ' Mos';
                    }
                    $tenureParts[] = $diff->d . ' Days';
                    $tenureString = implode(' ', $tenureParts);
                @endphp
                <tr class="transition-colors hover:bg-slate-50/80">
                    {{-- Nomor Urut Paginasi --}}
                    <td class="px-4 sm:px-6 py-3.5 text-center text-slate-400 font-bold">
                        {{ ($employees->currentPage() - 1) * $employees->perPage() + $loop->iteration }}
                    </td>

                    {{-- Nama --}}
                    <td class="px-4 sm:px-6 py-3.5 font-bold text-slate-800">
                        {{ $employee->name }}
                    </td>

                    {{-- Posisi / Jabatan --}}
                    <td class="px-4 sm:px-6 py-3.5 text-slate-600 font-medium">
                        {{ $employee->position ?? '-' }}
                    </td>

                    {{-- Penggabungan Site & Branch --}}
                    <td class="px-4 sm:px-6 py-3.5">
                        <div class="flex flex-col">
                            <span class="font-bold text-slate-800">
                                {{ $employee->site->machine_name ?? '-' }}
                            </span>
                            <span class="text-[11px] font-semibold text-slate-400">
                                {{ $employee->site->branch->branch_name ?? ($employee->branch->branch_name ?? '-') }}
                            </span>
                        </div>
                    </td>

                    {{-- Status --}}
                    <td class="px-4 sm:px-6 py-3.5 text-center">
                        @if ($employee->status == 'Permanent')
                            <span
                                class="px-2.5 py-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200/60 rounded-full uppercase">Permanent</span>
                        @elseif($employee->status == 'Contract')
                            <span
                                class="px-2.5 py-1 text-[10px] font-bold text-blue-700 bg-blue-50 border border-blue-200/60 rounded-full uppercase">Contract</span>
                        @elseif($employee->status == 'Probation')
                            <span
                                class="px-2.5 py-1 text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200/60 rounded-full uppercase">Probation</span>
                        @elseif($employee->status == 'Daily')
                            <span
                                class="px-2.5 py-1 text-[10px] font-bold text-purple-700 bg-purple-50 border border-purple-200/60 rounded-full uppercase">Daily</span>
                        @else
                            <span
                                class="px-2.5 py-1 text-[10px] font-bold text-slate-600 bg-slate-50 border border-slate-200 rounded-full uppercase">{{ $employee->status }}</span>
                        @endif
                    </td>

                    {{-- Join Date --}}
                    <td class="px-4 sm:px-6 py-3.5 font-medium text-center text-slate-600">
                        {{ \Carbon\Carbon::parse($employee->join_date)->format('d M Y') }}
                    </td>

                    {{-- Masa Kerja / Tenure --}}
                    <td class="px-4 sm:px-6 py-3.5 text-center">
                        <span
                            class="px-2.5 py-1 text-[11px] font-extrabold text-blue-700 bg-blue-50 border border-blue-200/60 rounded-full">
                            <i class="mr-1 fa-solid fa-clock-rotate-left"></i> {{ $tenureString }}
                        </span>
                    </td>

                    {{-- Actions --}}
                    <td class="px-4 sm:px-6 py-3.5 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            {{-- View Detail --}}
                            <button type="button" onclick="showEmployeeDetail({{ $employee->id }})"
                                class="p-1.5 text-blue-600 transition-colors bg-blue-50 rounded-lg hover:bg-blue-600 hover:text-white"
                                title="View Details">
                                <i class="text-xs fa-solid fa-eye"></i>
                            </button>
                            {{-- Edit --}}
                            <a href="{{ route('employee.edit', $employee->id) }}"
                                class="p-1.5 text-amber-600 transition-colors bg-amber-50 rounded-lg hover:bg-amber-600 hover:text-white"
                                title="Edit Employee">
                                <i class="text-xs fa-solid fa-pen-to-square"></i>
                            </a>
                            {{-- Delete --}}
                            <form action="{{ route('employee.destroy', $employee->id) }}" method="POST" class="inline"
                                onsubmit="return confirm('Are you sure you want to delete this employee record?')">
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
    {{ $employees->links() }}
</div>
