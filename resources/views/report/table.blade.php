@forelse ($data as $index => $item)
    <tr class="transition-colors border-b group border-slate-50 hover:bg-slate-50/50">
        @if (Auth::user()->role === 'superadmin')
            <td class="p-4 text-center">
                <input type="checkbox"
                    class="w-4 h-4 text-red-600 rounded border-slate-300 focus:ring-red-500 checkbox_id"
                    value="{{ $item->id }}">
            </td>
        @endif

        <td class="p-4 font-medium text-center text-slate-400">
            {{ $index + 1 + ($data->currentPage() - 1) * $data->perPage() }}
        </td>

        @php
            $siteLabels = [
                'fsjkt' => ['label' => 'FS6000 Jakarta', 'color' => 'bg-blue-50 text-blue-700'],
                'fssmg' => ['label' => 'FS6000 Semarang', 'color' => 'bg-indigo-50 text-indigo-700'],
                'fssby' => ['label' => 'FS6000 Surabaya', 'color' => 'bg-purple-50 text-purple-700'],
                'ebeam' => ['label' => 'E-Beam', 'color' => 'bg-amber-50 text-amber-700'],
            ];
            $site = $siteLabels[$item->site_machine] ?? [
                'label' => $item->site_machine,
                'color' => 'bg-slate-100 text-slate-700',
            ];
        @endphp

        <td class="p-4">
            <span class="px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-tighter {{ $site['color'] }}">
                {{ $site['label'] }}
            </span>
        </td>

        <td class="p-4">
            <div class="flex items-center gap-2">
                <div
                    class="w-7 h-7 rounded-full bg-slate-200 flex items-center justify-center text-[10px] font-bold text-slate-600 uppercase">
                    {{ substr($item->attendant, 0, 2) }}
                </div>
                <span class="font-bold text-slate-700">{{ $item->attendant }}</span>
            </div>
        </td>

        <td class="p-4 text-center">
            <span class="text-sm font-medium text-slate-500">
                <i class="mr-1 opacity-50 fa-regular fa-calendar-check"></i>
                {{ \Carbon\Carbon::parse($item->failure_date)->format('d M Y') }}
            </span>
        </td>

        <td class="p-4">
            <div class="flex items-center justify-center gap-2">
                {{-- DETAIL BUTTON --}}
                <button onclick='openDetailModal(@json($item))'
                    class="p-2 transition-all rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50"
                    title="View Detail">
                    <i class="text-lg fa-solid fa-eye"></i>
                </button>

                @if (Auth::user()->role === 'superadmin')
                    <a href="{{ route('report.edit', $item->id) }}"
                        class="p-2 transition-all rounded-lg text-slate-400 hover:text-amber-600 hover:bg-amber-50"
                        title="Edit Report">
                        <i class="text-lg fa-solid fa-pen-to-square"></i>
                    </a>

                    <div x-data="{ open: false }" class="inline-block">
                        <button @click="open = true"
                            class="p-2 transition-all rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50"
                            title="Delete Report">
                            <i class="text-lg fa-solid fa-trash-can"></i>
                        </button>

                        {{-- DELETE MODAL --}}
                        <div x-show="open" x-cloak x-transition.opacity
                            class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
                            <div @click.outside="open = false" x-transition
                                class="w-full max-w-sm p-8 text-center bg-white shadow-2xl rounded-3xl">
                                <div
                                    class="flex items-center justify-center w-20 h-20 mx-auto mb-6 text-3xl text-red-500 rounded-full bg-red-50">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                </div>
                                <h3 class="mb-2 text-xl font-black text-slate-800">Konfirmasi Hapus</h3>
                                <p class="mb-8 text-sm font-medium text-slate-500">Data report ini akan dihapus permanen
                                    dari sistem.</p>
                                <div class="flex gap-3">
                                    <button @click="open = false"
                                        class="flex-1 py-3 text-xs font-black tracking-widest uppercase transition-colors text-slate-400 hover:text-slate-600">Batal</button>
                                    <form action="{{ route('report.destroy', $item->id) }}" method="POST"
                                        class="flex-1">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                            class="w-full py-3 text-xs font-black tracking-widest text-white uppercase transition-all bg-red-600 shadow-lg rounded-xl hover:bg-red-700 shadow-red-200">Hapus</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="10" class="p-20 text-center">
            <i class="block mb-4 text-5xl fa-solid fa-folder-open text-slate-200"></i>
            <p class="text-xs font-bold tracking-widest uppercase text-slate-400">Belum ada laporan kerusakan</p>
        </td>
    </tr>
@endforelse
