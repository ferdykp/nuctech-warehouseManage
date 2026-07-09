@forelse ($data as $index => $item)
    <tr class="transition-colors hover:bg-slate-50/50" id="row-{{ $item->id }}">
        {{-- Checkbox untuk Delete Selected (Hanya untuk Superadmin) --}}
        @if (Auth::user()->role === 'superadmin')
            <td class="p-4 text-center">
                <input type="checkbox" name="ids[]" value="{{ $item->id }}"
                    class="w-4 h-4 text-red-600 rounded sub_chk border-slate-300 focus:ring-red-500">
            </td>
        @endif

        {{-- Nomor Urut --}}
        <td class="p-4 font-bold text-center text-slate-500">
            {{ ($data->currentPage() - 1) * $data->perPage() + $loop->iteration }}
        </td>

        {{-- Nama Site / Machine --}}
        <td class="p-4">
            <div class="font-bold text-slate-800">{{ $item->site_machine }}</div>
            <div class="text-[11px] font-medium text-slate-400">Series: {{ $item->series_machine }}</div>
        </td>

        {{-- Petugas / Reporter --}}
        <td class="p-4">
            <div class="flex items-center gap-2">
                <div
                    class="flex items-center justify-center text-xs font-black text-red-600 uppercase rounded-full w-7 h-7 bg-red-50">
                    {{ substr($item->attendant, 0, 2) }}
                </div>
                <span class="font-semibold text-slate-700">{{ $item->attendant }}</span>
            </div>
        </td>

        {{-- Tanggal Kegagalan / Kerusakan --}}
        <td class="p-4 font-medium text-center text-slate-600">
            <span
                class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 rounded-lg text-xs font-bold text-slate-600">
                <i class="fa-regular fa-calendar text-[11px]"></i>
                {{ \Carbon\Carbon::parse($item->failure_date)->format('d M Y') }}
            </span>
        </td>

        {{-- Kolom Action (Hanya untuk Superadmin) --}}
        @if (Auth::user()->role === 'superadmin')
            <td class="p-4 text-center">
                <div class="flex items-center justify-center gap-2">
                    {{-- Tombol Edit --}}
                    <a href="{{ route($routePrefix . '.edit', $item->id) }}"
                        class="p-2 text-blue-600 transition-all bg-blue-50 rounded-xl hover:bg-blue-600 hover:text-white"
                        title="Edit Report">
                        <i class="text-xs fa-solid fa-pen-to-square"></i>
                    </a>

                    {{-- Tombol Delete (Menggunakan Form preventif) --}}
                    <form action="{{ route($routePrefix . '.destroy', $item->id) }}" method="POST"
                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus report ini?');" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="p-2 text-red-600 transition-all bg-red-50 rounded-xl hover:bg-red-600 hover:text-white"
                            title="Hapus Report">
                            <i class="text-xs fa-solid fa-trash-can"></i>
                        </button>
                    </form>
                </div>
            </td>
        @endif
    </tr>
@empty
    <tr>
        <td colspan="{{ Auth::user()->role === 'superadmin' ? 6 : 4 }}"
            class="p-8 text-sm italic font-medium text-center text-slate-400">
            <div class="flex flex-col items-center gap-2 py-4">
                <i class="text-2xl fa-solid fa-folder-open text-slate-300"></i>
                <span>Tidak ada data report yang ditemukan.</span>
            </div>
        </td>
    </tr>
@endforelse
