<div class="w-full">
    <table class="w-full text-xs text-left border-collapse table-auto">
        <thead class="font-bold tracking-wider text-gray-600 uppercase border-b border-gray-200 bg-gray-100/80">
            <tr>
                <th class="w-1/5 px-3 py-3">Karyawan</th>
                <th class="w-1/5 px-3 py-3">Rekening</th>
                <th class="w-1/6 px-3 py-3">Gaji Pokok</th>
                <th class="w-1/6 px-3 py-3">Status</th>
                <th class="w-1/4 px-3 py-3">Penyesuaian (Lembur)</th>
                <th class="w-16 px-3 py-3 text-center">Aksi</th>
            </tr>
        </thead>
        <tbody class="font-medium divide-y divide-gray-100">
            @forelse ($salaries as $item)
                <tr class="transition-colors hover:bg-gray-50/70">
                    {{-- Karyawan: Nama, Posisi & Placement digabung vertikal --}}
                    <td class="px-3 py-3">
                        <div class="font-bold leading-tight text-gray-900">{{ $item->name }}</div>
                        <div class="text-[11px] text-gray-500 mt-0.5">
                            {{ $item->position ?? '-' }} &bull; <span
                                class="font-semibold text-gray-700">{{ $item->placement ?? '-' }}</span>
                        </div>
                    </td>

                    {{-- Rekening: Bank & No Rek --}}
                    <td class="px-3 py-3">
                        <div class="font-bold text-gray-800">{{ $item->bank }}</div>
                        <div class="font-mono text-[11px] text-gray-500">{{ $item->account_no }}</div>
                    </td>

                    {{-- Gaji Pokok --}}
                    <td class="px-3 py-3 font-bold text-emerald-600 whitespace-nowrap">
                        Rp {{ number_format($item->amount, 0, ',', '.') }}
                    </td>

                    {{-- Information Badge --}}
                    <td class="px-3 py-3">
                        <span
                            class="inline-block px-2 py-0.5 text-[10px] font-bold rounded-full border tracking-wide uppercase leading-tight
                            {{ str_contains($item->information, 'probation') ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-blue-50 text-blue-700 border-blue-200' }}">
                            {{ strtoupper($item->information) }}
                        </span>
                    </td>

                    {{-- Before / After (Format Ringkas Memanjang ke Bawah) --}}
                    <td class="px-3 py-3">
                        @if (str_contains($item->calculated_before_after, '+Lembur'))
                            @php
                                $parts = explode('/', $item->calculated_before_after);
                                $beforeVal = trim($parts[0] ?? '');
                                $afterFull = trim($parts[1] ?? '');

                                // Ambil nominal penerimaan akhir dan keterangan lembur
                                preg_match('/(Rp [0-9\.]+)\s*\((.+)\)/', $afterFull, $matches);
                                $afterVal = $matches[1] ?? $afterFull;
                                $overtimeNote = $matches[2] ?? '';
                            @endphp
                            <div class="space-y-1">
                                <div class="text-xs font-bold text-blue-700">
                                    {{ $beforeVal }} <span class="font-normal text-gray-400">➔</span>
                                    {{ $afterVal }}
                                </div>
                                @if ($overtimeNote)
                                    <div
                                        class="inline-block px-1.5 py-0.5 text-[10px] font-bold bg-rose-50 text-rose-600 border border-rose-100 rounded">
                                        {{ $overtimeNote }}
                                    </div>
                                @endif
                            </div>
                        @else
                            <span class="font-medium text-gray-600">
                                {{ $item->calculated_before_after }}
                            </span>
                        @endif
                    </td>

                    {{-- Aksi --}}
                    <td class="px-3 py-3 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <button type="button" onclick="showSalaryDetail({{ $item->id }})"
                                class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                title="Detail Data Gaji">
                                <i class="fa-solid fa-eye"></i>
                            </button>

                            {{-- Tombol Edit --}}
                            <a href="{{ route('salary.edit', $item->id) }}"
                                class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors"
                                title="Edit Data Gaji" onclick="event.stopPropagation();">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>

                            <form action="{{ route('salary.destroy', $item->id) }}" method="POST" class="inline"
                                onsubmit="return confirm('Yakin menghapus data gaji ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition-colors"
                                    title="Hapus Data Gaji">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                        <div class="flex flex-col items-center justify-center gap-2">
                            <i class="text-2xl text-gray-300 opacity-50 fa-solid fa-folder-open"></i>
                            <p class="text-sm font-bold text-gray-600">Belum ada data gaji terdaftar untuk periode ini.
                            </p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="p-4 border-t border-gray-100">
    {{ $salaries->links() }}
</div>
