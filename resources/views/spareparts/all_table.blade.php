<div class="w-full overflow-x-auto">
    <table class="w-full text-left border-collapse min-w-[700px]">
        <thead>
            <tr
                class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 bg-slate-50 border-b border-slate-100">
                <th class="px-6 py-4">Spare Part Information</th>
                <th class="px-6 py-4">Site Location</th>
                <th class="px-6 py-4 text-center">Stock</th>
                <th class="px-6 py-4 text-center">Condition</th>
                <th class="w-32 px-6 py-4 text-center">Action</th>
            </tr>
        </thead>
        <tbody class="text-xs font-medium divide-y divide-slate-100 text-slate-700">
            @forelse($allStocks as $stock)
                <tr class="transition-colors hover:bg-slate-50/60">

                    {{-- Spare Part Info --}}
                    <td class="px-6 py-4">
                        <div class="flex flex-col max-w-[240px] sm:max-w-[320px]">
                            <span
                                class="text-sm font-bold leading-snug text-slate-900">{{ $stock->sparepart->item_name }}</span>
                            <span
                                class="font-mono text-[11px] text-slate-400 mt-0.5 tracking-wide flex items-center gap-1">
                                <i class="fa-solid fa-barcode text-[10px] text-slate-300"></i>
                                {{ $stock->sparepart->serial_number ?? 'NO SERIAL' }}
                            </span>
                        </div>
                    </td>

                    {{-- Site Location --}}
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3 max-w-[220px] sm:max-w-[280px]">
                            <div
                                class="flex items-center justify-center w-8 h-8 text-xs text-blue-600 border border-blue-100 shrink-0 rounded-xl bg-blue-50">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span class="font-bold truncate text-slate-800">{{ $stock->site->machine_name }}</span>
                                <span
                                    class="text-[10px] font-semibold text-slate-400 uppercase truncate mt-0.5">{{ $stock->site->branch->branch_name ?? 'Branch HQ' }}</span>
                            </div>
                        </div>
                    </td>

                    {{-- Stock Quantity --}}
                    <td class="px-6 py-4 text-center">
                        <span
                            class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-black rounded-xl text-slate-800 bg-slate-100 border border-slate-200/80">
                            {{ number_format($stock->qty) }}
                            <small
                                class="font-extrabold text-[9px] text-slate-400 uppercase tracking-wider">{{ $stock->sparepart->uom }}</small>
                        </span>
                    </td>

                    {{-- Condition Badge --}}
                    <td class="px-6 py-4 text-center">
                        @php
                            $colors = [
                                'new' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                'used-good' => 'bg-blue-50 text-blue-800 border-blue-200',
                                'damaged' => 'bg-rose-50 text-rose-800 border-rose-200',
                                'repair' => 'bg-amber-50 text-amber-800 border-amber-200',
                            ];
                        @endphp
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-lg text-[10px] font-extrabold uppercase border tracking-wider {{ $colors[$stock->condition] ?? 'bg-slate-50 border-slate-200 text-slate-600' }}">
                            {{ str_replace('-', ' ', $stock->condition) }}
                        </span>
                    </td>

                    {{-- Action Button --}}
                    <td class="px-6 py-4 text-center">
                        <a href="{{ route('sparepart.index', $stock->site->slug) }}"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-blue-600 bg-blue-50 border border-blue-100 rounded-xl hover:bg-blue-600 hover:text-white transition-all active:scale-95 shrink-0">
                            <span>View Site</span>
                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="p-12 text-center text-slate-400">
                        <div
                            class="flex items-center justify-center w-12 h-12 mx-auto mb-3 text-xl rounded-2xl bg-slate-100 text-slate-400">
                            <i class="fa-solid fa-boxes-stacked"></i>
                        </div>
                        <p class="text-sm font-bold text-slate-800">No Spareparts Found</p>
                        <p class="mt-1 text-xs text-slate-400">There are no inventory records matching your query.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="p-4 border-t sm:p-6 border-slate-100 pagination bg-slate-50/50">
    {{ $allStocks->links() }}
</div>
