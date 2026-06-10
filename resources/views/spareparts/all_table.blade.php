<div class="overflow-x-auto scrollbar-thin">
    <table class="w-full text-left border-collapse whitespace-nowrap lg:whitespace-normal">
        <thead>
            <tr class="border-b border-gray-100 bg-gray-50/50">
                <th class="px-6 py-4 text-xs font-bold tracking-wider text-gray-500 uppercase">Spare Part Info</th>
                <th class="px-6 py-4 text-xs font-bold tracking-wider text-gray-500 uppercase">Site Location</th>
                <th class="px-6 py-4 text-xs font-bold tracking-wider text-center text-gray-500 uppercase">Stock</th>
                <th class="px-6 py-4 text-xs font-bold tracking-wider text-center text-gray-500 uppercase">Condition</th>
                <th class="px-6 py-4 text-xs font-bold tracking-wider text-center text-gray-500 uppercase">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($allStocks as $stock)
                <tr class="transition-colors hover:bg-gray-50/50">
                    <td class="px-6 py-4">
                        <div class="flex flex-col max-w-[200px] md:max-w-[300px] whitespace-normal">
                            <span
                                class="text-sm font-bold leading-tight text-gray-800 md:text-base">{{ $stock->sparepart->item_name }}</span>
                            <span
                                class="font-mono text-xs text-gray-400 mt-0.5 tracking-wide">{{ $stock->sparepart->serial_number }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3 max-w-[200px] md:max-w-[250px] whitespace-normal">
                            <div class="flex-shrink-0 p-2 text-blue-600 bg-blue-50 rounded-xl">
                                <i class="text-xs fa-solid fa-location-dot"></i>
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span
                                    class="text-sm font-semibold text-gray-700 truncate">{{ $stock->site->machine_name }}</span>
                                <span
                                    class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mt-0.5 truncate">{{ $stock->site->branch->branch_name ?? 'No Branch' }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span
                            class="inline-flex items-center gap-1 px-3 py-1.5 font-bold text-gray-700 bg-gray-100 rounded-xl text-sm">
                            {{ $stock->qty }}
                            <small
                                class="font-bold text-[10px] text-gray-400 uppercase tracking-wide">{{ $stock->sparepart->uom }}</small>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        @php
                            $colors = [
                                'new' => 'bg-green-50 text-green-700 border-green-100',
                                'used-good' => 'bg-blue-50 text-blue-700 border-blue-100',
                                'damaged' => 'bg-red-50 text-red-700 border-red-100',
                                'repair' => 'bg-orange-50 text-orange-700 border-orange-100',
                            ];
                        @endphp
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-xl text-[10px] font-black uppercase border tracking-wider {{ $colors[$stock->condition] ?? 'bg-gray-50 border-gray-100' }}">
                            {{ str_replace('-', ' ', $stock->condition) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <a href="{{ route('sparepart.index', $stock->site->slug) }}"
                            class="inline-flex items-center gap-1.5 text-xs font-bold text-blue-600 hover:text-blue-800 hover:underline transition-colors">
                            View Site
                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-16 text-sm italic text-center text-gray-400">
                        No spare part data found match with criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="px-6 py-4 border-t border-gray-100 pagination bg-gray-50/30">
    {{ $allStocks->links() }}
</div>
