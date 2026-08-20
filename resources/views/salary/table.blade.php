<div class="w-full overflow-x-auto">
    <table class="w-full text-left border-collapse min-w-[700px]">
        <thead>
            <tr
                class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 bg-slate-50 border-b border-slate-100">
                <th class="px-6 py-4">Employee</th>
                <th class="px-6 py-4">Bank Account</th>
                <th class="px-6 py-4">Basic Salary</th>
                <th class="px-6 py-4">Status</th>
                <th class="px-6 py-4">Adjustment (Overtime)</th>
                <th class="px-6 py-4 text-center w-28">Actions</th>
            </tr>
        </thead>
        <tbody class="text-xs font-medium divide-y divide-slate-100 text-slate-700">
            @forelse ($salaries as $item)
                <tr class="transition-colors hover:bg-slate-50/60">
                    {{-- Employee: Name, Position & Placement --}}
                    <td class="px-6 py-4">
                        <div class="text-sm font-extrabold text-slate-900">{{ $item->name }}</div>
                        <div class="text-[11px] font-semibold text-slate-400 mt-0.5">
                            {{ $item->position ?? '-' }} &bull; <span
                                class="text-slate-600">{{ $item->placement ?? '-' }}</span>
                        </div>
                    </td>

                    {{-- Bank Account --}}
                    <td class="px-6 py-4">
                        <div class="font-extrabold text-slate-800">{{ $item->bank }}</div>
                        <div class="font-mono text-[11px] text-slate-400 font-semibold">{{ $item->account_no }}</div>
                    </td>

                    {{-- Basic Salary --}}
                    <td class="px-6 py-4 text-sm font-black text-emerald-700 whitespace-nowrap">
                        Rp {{ number_format($item->amount, 0, ',', '.') }}
                    </td>

                    {{-- Information Badge --}}
                    <td class="px-6 py-4">
                        <span
                            class="inline-block px-2.5 py-1 text-[10px] font-extrabold rounded-full border tracking-wide uppercase leading-tight
                            {{ str_contains($item->information, 'probation') ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-blue-50 text-blue-800 border-blue-200' }}">
                            {{ strtoupper($item->information) }}
                        </span>
                    </td>

                    {{-- Before / After --}}
                    <td class="px-6 py-4">
                        @if (str_contains($item->calculated_before_after, '+Lembur'))
                            @php
                                $parts = explode('/', $item->calculated_before_after);
                                $beforeVal = trim($parts[0] ?? '');
                                $afterFull = trim($parts[1] ?? '');

                                preg_match('/(Rp [0-9\.]+)\s*\((.+)\)/', $afterFull, $matches);
                                $afterVal = $matches[1] ?? $afterFull;
                                $overtimeNote = $matches[2] ?? '';
                            @endphp
                            <div class="space-y-1">
                                <div class="text-xs font-extrabold text-blue-800">
                                    {{ $beforeVal }} <span class="font-normal text-slate-400">➔</span>
                                    {{ $afterVal }}
                                </div>
                                @if ($overtimeNote)
                                    <div
                                        class="inline-block px-2 py-0.5 text-[10px] font-extrabold bg-rose-50 text-rose-700 border border-rose-100 rounded-md">
                                        {{ $overtimeNote }}
                                    </div>
                                @endif
                            </div>
                        @else
                            <span class="font-semibold text-slate-600">
                                {{ $item->calculated_before_after }}
                            </span>
                        @endif
                    </td>

                    {{-- Actions --}}
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            {{-- VIEW DETAIL --}}
                            <button type="button" onclick="showSalaryDetail({{ $item->id }})"
                                class="flex items-center justify-center w-8 h-8 transition-colors rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100"
                                title="Salary Details">
                                <i class="text-xs fa-solid fa-eye"></i>
                            </button>

                            {{-- EDIT --}}
                            <a href="{{ route('salary.edit', $item->id) }}"
                                class="flex items-center justify-center w-8 h-8 transition-all border rounded-xl text-amber-600 bg-amber-50 border-amber-100 hover:bg-amber-600 hover:text-white active:scale-95"
                                title="Edit Salary Record" onclick="event.stopPropagation();">
                                <i class="text-xs fa-solid fa-pen-to-square"></i>
                            </a>

                            {{-- DELETE --}}
                            <form action="{{ route('salary.destroy', $item->id) }}" method="POST" class="inline"
                                onsubmit="return confirm('Are you sure you want to delete this salary record?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="flex items-center justify-center w-8 h-8 transition-all border rounded-xl text-rose-600 bg-rose-50 border-rose-100 hover:bg-rose-600 hover:text-white active:scale-95"
                                    title="Delete Salary Record">
                                    <i class="text-xs fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="p-12 text-center text-slate-400">
                        <div
                            class="flex items-center justify-center w-12 h-12 mx-auto mb-3 text-xl rounded-2xl bg-slate-100 text-slate-400">
                            <i class="fa-solid fa-folder-open"></i>
                        </div>
                        <p class="text-sm font-bold text-slate-800">No Salary Data Registered for This Period</p>
                        <p class="mt-1 text-xs text-slate-400">Use bulk generation or manual entry to create records.
                        </p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="p-4 border-t sm:p-6 border-slate-100 bg-slate-50/30">
    {{ $salaries->links() }}
</div>
