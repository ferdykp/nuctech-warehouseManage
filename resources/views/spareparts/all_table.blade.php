<div class="w-full overflow-x-auto">
    <table class="w-full text-left border-collapse min-w-[700px]">
        <thead>
            <tr
                class="border-b border-slate-200/80 bg-slate-100/70 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">
                <th class="px-4 sm:px-6 py-3.5">Spare Part Info</th>
                <th class="px-4 sm:px-6 py-3.5">Site Location</th>
                <th class="px-4 sm:px-6 py-3.5 text-center">Stock</th>
                <th class="px-4 sm:px-6 py-3.5 text-center">Condition</th>
                <th class="px-4 sm:px-6 py-3.5 text-center w-28">Action</th>
            </tr>
        </thead>
        <tbody class="text-xs font-medium divide-y divide-slate-100 sm:text-sm text-slate-700">
            @forelse($allStocks as $stock)
                <tr class="transition-colors hover:bg-slate-50/80">
                    <td class="px-4 sm:px-6 py-3.5">
                        <div class="flex flex-col max-w-[220px] sm:max-w-[300px]">
                            <span
                                class="font-bold leading-tight text-slate-800">{{ $stock->sparepart->item_name }}</span>
                            <span
                                class="font-mono text-[11px] text-slate-400 mt-0.5 tracking-wide">{{ $stock->sparepart->serial_number ?? '-' }}</span>
                        </div>
                    </td>
                    <td class="px-4 sm:px-6 py-3.5">
                        <div class="flex items-center gap-2.5 max-w-[200px] sm:max-w-[250px]">
                            <div class="shrink-0 p-1.5 text-blue-600 bg-blue-50 rounded-lg">
                                <i class="text-xs fa-solid fa-location-dot"></i>
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span
                                    class="font-semibold truncate text-slate-700">{{ $stock->site->machine_name }}</span>
                                <span
                                    class="text-[10px] font-bold text-slate-400 uppercase tracking-wider truncate">{{ $stock->site->branch->branch_name ?? 'No Branch' }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 sm:px-6 py-3.5 text-center">
                        <span
                            class="inline-flex items-center gap-1 px-3 py-1 text-xs font-bold rounded-lg text-slate-700 bg-slate-100 sm:text-sm">
                            {{ $stock->qty }}
                            <small
                                class="font-bold text-[9px] text-slate-400 uppercase tracking-wide">{{ $stock->sparepart->uom }}</small>
                        </span>
                    </td>
                    <td class="px-4 sm:px-6 py-3.5 text-center">
                        @php
                            $colors = [
                                'new' => 'bg-emerald-50 text-emerald-700 border-emerald-200/60',
                                'used-good' => 'bg-blue-50 text-blue-700 border-blue-200/60',
                                'damaged' => 'bg-rose-50 text-rose-700 border-rose-200/60',
                                'repair' => 'bg-amber-50 text-amber-700 border-amber-200/60',
                            ];
                        @endphp
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[10px] font-extrabold uppercase border tracking-wider {{ $colors[$stock->condition] ?? 'bg-slate-50 border-slate-200' }}">
                            {{ str_replace('-', ' ', $stock->condition) }}
                        </span>
                    </td>
                    <td class="px-4 sm:px-6 py-3.5 text-center">
                        <a href="{{ route('sparepart.index', $stock->site->slug) }}"
                            class="inline-flex items-center gap-1 text-xs font-bold text-blue-600 transition-colors hover:text-blue-800">
                            <span>View Site</span>
                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="p-10 text-center text-slate-400">
                        <i class="block mb-2 text-3xl opacity-50 fa-solid fa-boxes-stacked"></i>
                        <p class="text-sm font-bold text-slate-700">No sparepart data matches your criteria</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="px-6 py-4 border-t border-slate-100 pagination bg-slate-50/50">
    {{ $allStocks->links() }}
</div>
