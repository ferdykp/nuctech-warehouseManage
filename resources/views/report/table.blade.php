@forelse ($data as $index => $item)
    <tr class="transition-colors hover:bg-slate-50/60" id="row-{{ $item->id }}">
        {{-- Checkbox --}}
        @if (Auth::user()?->role === 'superadmin')
            <td class="px-6 py-4 text-center">
                <input type="checkbox" name="ids[]" value="{{ $item->id }}"
                    class="w-4 h-4 rounded cursor-pointer text-rose-600 sub_chk border-slate-300 focus:ring-rose-500">
            </td>
        @endif

        {{-- Row Index --}}
        <td class="px-6 py-4 font-bold text-center text-slate-400">
            {{ ($data->currentPage() - 1) * $data->perPage() + $loop->iteration }}
        </td>

        {{-- Site Machine --}}
        <td class="px-6 py-4">
            <div class="text-sm font-bold leading-snug text-slate-900">{{ $item->site_machine }}</div>
            <div class="text-[11px] font-mono text-slate-400 mt-0.5">Series: {{ $item->series_machine ?? 'N/A' }}</div>
        </td>

        {{-- Attendant --}}
        <td class="px-6 py-4">
            <div class="flex items-center gap-3">
                <div
                    class="flex items-center justify-center w-8 h-8 text-xs font-black uppercase border rounded-2xl text-rose-600 bg-rose-50 border-rose-100 shrink-0">
                    {{ substr($item->attendant, 0, 2) }}
                </div>
                <span class="font-bold text-slate-800">{{ $item->attendant }}</span>
            </div>
        </td>

        {{-- Failure Date --}}
        <td class="px-6 py-4 text-center">
            <span
                class="inline-flex items-center gap-1.5 px-3 py-1 bg-slate-100 border border-slate-200/80 rounded-xl text-xs font-bold text-slate-700">
                <i class="fa-regular fa-calendar text-[11px] text-slate-400"></i>
                {{ \Carbon\Carbon::parse($item->failure_date)->format('d M Y') }}
            </span>
        </td>

        {{-- Action Column --}}
        <td class="px-6 py-4 text-center">
            <div class="flex items-center justify-center gap-1.5">
                {{-- Detail Button (Selalu Tampil) --}}
                <button type="button" onclick='openDetailReportModal(@json($item))'
                    class="flex items-center justify-center w-8 h-8 transition-all border cursor-pointer text-emerald-600 border-emerald-100 rounded-xl bg-emerald-50 hover:bg-emerald-600 hover:text-white active:scale-95"
                    title="View Detail Report">
                    <i class="text-xs fa-solid fa-eye"></i>
                </button>

                @if (Auth::user()?->role === 'superadmin')
                    {{-- Edit --}}
                    <a href="{{ route($routePrefix . '.edit', $item->id) }}"
                        class="flex items-center justify-center w-8 h-8 text-blue-600 transition-all border border-blue-100 rounded-xl bg-blue-50 hover:bg-blue-600 hover:text-white active:scale-95"
                        title="Edit Report">
                        <i class="text-xs fa-solid fa-pen-to-square"></i>
                    </a>

                    {{-- Delete --}}
                    <button type="button"
                        onclick="openDeleteReportModal('{{ route($routePrefix . '.destroy', ['report' => $item->id]) }}', '#REP-{{ str_pad($item->id, 4, '0', STR_PAD_LEFT) }}')"
                        class="flex items-center justify-center w-8 h-8 transition-all border cursor-pointer rounded-xl text-rose-600 bg-rose-50 border-rose-100 hover:bg-rose-600 hover:text-white active:scale-95"
                        title="Delete Report">
                        <i class="text-xs fa-solid fa-trash-can"></i>
                    </button>
                @endif
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="{{ Auth::user()?->role === 'superadmin' ? 6 : 5 }}" class="p-12 text-center text-slate-400">
            <div
                class="flex items-center justify-center w-12 h-12 mx-auto mb-3 text-xl rounded-2xl bg-slate-100 text-slate-400">
                <i class="fa-solid fa-folder-open"></i>
            </div>
            <p class="text-sm font-bold text-slate-800">No Failure Reports Found</p>
            <p class="mt-1 text-xs text-slate-400">There are no component breakdown logs matching your search criteria.
            </p>
        </td>
    </tr>
@endforelse
