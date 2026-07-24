@forelse ($data as $index => $item)
    <tr class="transition-colors hover:bg-slate-50/80" id="row-{{ $item->id }}">
        {{-- Checkbox --}}
        @if (Auth::user()->role === 'superadmin')
            <td class="px-4 sm:px-6 py-3.5 text-center">
                <input type="checkbox" name="ids[]" value="{{ $item->id }}"
                    class="w-4 h-4 rounded text-rose-600 sub_chk border-slate-300 focus:ring-rose-500">
            </td>
        @endif

        {{-- Serial Number --}}
        <td class="px-4 sm:px-6 py-3.5 font-bold text-center text-slate-400">
            {{ ($data->currentPage() - 1) * $data->perPage() + $loop->iteration }}
        </td>

        {{-- Site Machine --}}
        <td class="px-4 sm:px-6 py-3.5">
            <div class="font-bold text-slate-800">{{ $item->site_machine }}</div>
            <div class="text-[11px] font-normal text-slate-400">Series: {{ $item->series_machine ?? 'N/A' }}</div>
        </td>

        {{-- Attendant --}}
        <td class="px-4 sm:px-6 py-3.5">
            <div class="flex items-center gap-2.5">
                <div
                    class="flex items-center justify-center text-xs font-black uppercase rounded-full text-rose-600 w-7 h-7 bg-rose-50 shrink-0">
                    {{ substr($item->attendant, 0, 2) }}
                </div>
                <span class="font-semibold text-slate-700">{{ $item->attendant }}</span>
            </div>
        </td>

        {{-- Failure Date --}}
        <td class="px-4 sm:px-6 py-3.5 font-medium text-center text-slate-600">
            <span
                class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 rounded-lg text-xs font-bold text-slate-600">
                <i class="fa-regular fa-calendar text-[11px]"></i>
                {{ \Carbon\Carbon::parse($item->failure_date)->format('d M Y') }}
            </span>
        </td>

        {{-- Action Column --}}
        @if (Auth::user()->role === 'superadmin')
            <td class="px-4 sm:px-6 py-3.5 text-center">
                <div class="flex items-center justify-center gap-1.5">
                    {{-- Edit --}}
                    <a href="{{ route($routePrefix . '.edit', $item->id) }}"
                        class="p-1.5 text-blue-600 transition-colors bg-blue-50 rounded-lg hover:bg-blue-600 hover:text-white"
                        title="Edit Report">
                        <i class="text-xs fa-solid fa-pen-to-square"></i>
                    </a>

                    {{-- Delete --}}
                    <form action="{{ route($routePrefix . '.destroy', $item->id) }}" method="POST"
                        onsubmit="return confirm('Are you sure you want to delete this failure report?');"
                        class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="p-1.5 text-rose-600 transition-colors bg-rose-50 rounded-lg hover:bg-rose-600 hover:text-white"
                            title="Delete Report">
                            <i class="text-xs fa-solid fa-trash-can"></i>
                        </button>
                    </form>
                </div>
            </td>
        @endif
    </tr>
@empty
    <tr>
        <td colspan="{{ Auth::user()->role === 'superadmin' ? 6 : 4 }}" class="p-10 text-center text-slate-400">
            <i class="block mb-2 text-3xl opacity-50 fa-solid fa-folder-open"></i>
            <p class="text-sm font-bold text-slate-700">No failure reports found</p>
        </td>
    </tr>
@endforelse
