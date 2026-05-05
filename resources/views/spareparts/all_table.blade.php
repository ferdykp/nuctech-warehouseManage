<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-gray-50/50">
                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase text-nowrap">Spare Part Info</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase text-nowrap">Site Location</th>
                <th class="px-6 py-4 text-xs font-bold text-center text-gray-500 uppercase text-nowrap">Stock</th>
                <th class="px-6 py-4 text-xs font-bold text-center text-gray-500 uppercase text-nowrap">Condition</th>
                <th class="px-6 py-4 text-xs font-bold text-center text-gray-500 uppercase text-nowrap">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($allStocks as $stock)
                <tr class="transition-colors hover:bg-gray-50/50">
                    <td class="px-6 py-4 text-nowrap">
                        <div class="flex flex-col">
                            <span class="font-bold text-gray-800">{{ $stock->sparepart->item_name }}</span>
                            <span class="font-mono text-xs text-gray-400">{{ $stock->sparepart->serial_number }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <div class="p-1.5 bg-blue-50 text-blue-600 rounded-lg">
                                <i class="text-xs fa-solid fa-location-dot"></i>
                            </div>
                            <div class="flex flex-col">
                                <span
                                    class="text-sm font-semibold text-gray-700">{{ $stock->site->machine_name }}</span>
                                <span
                                    class="text-[10px] text-gray-400 uppercase leading-none">{{ $stock->site->branch->branch_name ?? 'No Branch' }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-3 py-1 font-bold text-gray-700 bg-gray-100 rounded-lg">
                            {{ $stock->qty }} <small
                                class="font-normal text-gray-400 uppercase">{{ $stock->sparepart->uom }}</small>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        @php
                            $colors = [
                                'new' => 'bg-green-100 text-green-700',
                                'used-good' => 'bg-blue-100 text-blue-700',
                                'damaged' => 'bg-red-100 text-red-700',
                                'repair' => 'bg-orange-100 text-orange-700',
                            ];
                        @endphp
                        <span
                            class="px-3 py-1 rounded-full text-[10px] font-bold uppercase {{ $colors[$stock->condition] ?? 'bg-gray-100' }}">
                            {{ str_replace('-', ' ', $stock->condition) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <a href="{{ route('sparepart.index', $stock->site->slug) }}"
                            class="inline-flex items-center gap-1 text-xs font-bold text-blue-600 hover:underline">
                            View Site <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-10 italic text-center text-gray-400">
                        No spare part data found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</div>

<div class="px-6 py-4 border-t pagination">
    {{ $allStocks->links() }}
</div>
