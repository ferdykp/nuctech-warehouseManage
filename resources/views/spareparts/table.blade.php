<div class="w-full overflow-x-auto bg-white">
    <table class="w-full border-collapse min-w-[750px] text-left">
        <thead>
            <tr
                class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 bg-slate-50 border-b border-slate-100">
                <th class="w-16 px-6 py-4 text-center">No</th>
                <th class="px-6 py-4">Item Information</th>
                <th class="px-6 py-4 text-center">Serial Number</th>
                <th class="px-6 py-4 text-center">Quantity</th>
                <th class="px-6 py-4 text-center">Condition</th>
                @if (Auth::user()?->role === 'superadmin' ||
                        (Auth::user()?->role === 'team_leader' && Auth::user()?->site_id === $siteData->id))
                    <th class="px-6 py-4 text-right w-52">Actions</th>
                @endif
            </tr>
        </thead>

        <tbody class="text-xs font-medium divide-y divide-slate-100 text-slate-700">
            @forelse ($assets as $item)
                <tr class="transition-colors hover:bg-slate-50/60">
                    {{-- Row Index --}}
                    <td class="px-6 py-4 font-bold text-center text-slate-400">
                        {{ ($assets->currentPage() - 1) * $assets->perPage() + $loop->iteration }}
                    </td>

                    {{-- Item Info --}}
                    <td class="px-6 py-4">
                        <div class="flex flex-col">
                            <span
                                class="text-sm font-bold leading-snug text-slate-900">{{ $item->sparepart?->item_name ?? 'Unnamed Item' }}</span>
                            <span class="text-[11px] text-slate-400 font-normal mt-0.5"><i
                                    class="fa-solid fa-cube text-[10px] mr-1 text-slate-300"></i>{{ $item->sparepart?->type ?? 'Standard Model' }}</span>
                        </div>
                    </td>

                    {{-- Serial Number --}}
                    <td class="px-6 py-4 font-mono text-center">
                        <span
                            class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-slate-100 text-slate-600 border border-slate-200/60">
                            {{ $item->sparepart?->serial_number ?? '-' }}
                        </span>
                    </td>

                    {{-- Quantity Badge --}}
                    <td class="px-6 py-4 text-center">
                        <div
                            class="inline-flex flex-col items-center justify-center min-w-[60px] py-1 px-2.5 bg-blue-50/80 border border-blue-200/80 rounded-xl">
                            <span class="text-base font-black leading-none text-blue-700">{{ $item->qty }}</span>
                            <span
                                class="text-[8px] font-extrabold text-blue-500 uppercase tracking-wider mt-0.5">{{ $item->sparepart?->uom ?? 'PCS' }}</span>
                        </div>
                    </td>

                    {{-- Condition Badge --}}
                    <td class="px-6 py-4 text-center">
                        @php
                            $colorMap = [
                                'new' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                'used-good' => 'bg-blue-50 text-blue-800 border-blue-200',
                                'damaged' => 'bg-rose-50 text-rose-800 border-rose-200',
                                'repair' => 'bg-amber-50 text-amber-800 border-amber-200',
                            ];
                            $style = $colorMap[$item->condition] ?? 'bg-slate-50 text-slate-700 border-slate-200';
                        @endphp
                        <span
                            class="px-3 py-1 text-[10px] font-extrabold uppercase tracking-wider border rounded-lg shadow-2xs {{ $style }}">
                            {{ str_replace('-', ' ', $item->condition) }}
                        </span>
                    </td>

                    {{-- Actions --}}
                    @if (Auth::user()?->role === 'superadmin' ||
                            (Auth::user()?->role === 'team_leader' && Auth::user()?->site_id === $siteData->id))
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end items-center gap-1.5">
                                {{-- ADJUST STOK --}}
                                <button type="button"
                                    onclick="openAdjustModal({{ $item->id }}, '{{ addslashes($item->sparepart?->item_name ?? '') }}', {{ $item->qty }}, '{{ $item->condition }}')"
                                    class="flex items-center justify-center w-8 h-8 transition-all border cursor-pointer rounded-xl text-amber-600 bg-amber-50 border-amber-100 hover:bg-amber-600 hover:text-white active:scale-95"
                                    title="Adjust Stock & Condition">
                                    <i class="text-xs fa-solid fa-sliders"></i>
                                </button>

                                {{-- EDIT --}}
                                <button type="button" onclick="openEditModal(this)"
                                    data-item='@json($item->sparepart)'
                                    class="flex items-center justify-center w-8 h-8 text-blue-600 transition-all border border-blue-100 cursor-pointer rounded-xl bg-blue-50 hover:bg-blue-600 hover:text-white active:scale-95"
                                    title="Edit Sparepart Info">
                                    <i class="text-xs fa-solid fa-pen-to-square"></i>
                                </button>

                                {{-- DETAIL (VIEW) --}}
                                <button type="button"
                                    onclick='openDetailModal(@json($item->sparepart), @json($all_sites))'
                                    class="flex items-center justify-center w-8 h-8 transition-all border cursor-pointer rounded-xl text-slate-600 bg-slate-100 border-slate-200 hover:bg-slate-900 hover:text-white active:scale-95"
                                    title="View Full Details">
                                    <i class="text-xs fa-solid fa-eye"></i>
                                </button>

                                {{-- MOVE (TRANSFER) --}}
                                <button type="button"
                                    onclick="openMoveModal({{ $item->id }}, '{{ addslashes($item->sparepart?->item_name ?? '') }}', {{ $item->qty }}, '{{ $item->condition }}')"
                                    class="px-2.5 py-1.5 text-[10px] font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-xl shadow-xs transition-all active:scale-95 flex items-center gap-1 cursor-pointer">
                                    <i class="fa-solid fa-truck-fast text-[10px]"></i> Move
                                </button>

                                {{-- DELETE --}}
                                <button type="button"
                                    onclick="openDeleteModal('{{ route('sparepart.stock.destroy', [$slug, $item->id]) }}', '{{ addslashes($item->sparepart?->item_name ?? '') }} ({{ strtoupper($item->condition) }})')"
                                    class="flex items-center justify-center w-8 h-8 transition-all border cursor-pointer rounded-xl text-rose-600 bg-rose-50 border-rose-100 hover:bg-rose-600 hover:text-white active:scale-95"
                                    title="Delete Condition Stock">
                                    <i class="text-xs fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ Auth::user()?->role === 'superadmin' || (Auth::user()?->role === 'team_leader' && Auth::user()?->site_id === $siteData->id) ? 6 : 5 }}"
                        class="p-12 text-center text-slate-400">
                        <div
                            class="flex items-center justify-center w-12 h-12 mx-auto mb-3 text-xl rounded-2xl bg-slate-100 text-slate-400">
                            <i class="fa-solid fa-boxes-stacked"></i>
                        </div>
                        <p class="text-sm font-bold text-slate-800">No Spareparts Registered</p>
                        <p class="mt-1 text-xs text-slate-400">There are no inventory items assigned to this site
                            location.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="p-4 border-t border-slate-100 bg-slate-50/50">
    {{ $assets->links() }}
</div>
