<div class="w-full overflow-x-auto bg-white border border-slate-200/80 shadow-2xs rounded-2xl">
    <table class="w-full border-collapse min-w-[700px] text-left">
        <thead>
            <tr
                class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 bg-slate-100/70 border-b border-slate-200/80">
                <th class="px-4 sm:px-6 py-3.5 text-center w-14">No</th>
                <th class="px-4 sm:px-6 py-3.5">Item Information</th>
                <th class="px-4 sm:px-6 py-3.5 text-center">Serial Number</th>
                <th class="px-4 sm:px-6 py-3.5 text-center">Quantity</th>
                <th class="px-4 sm:px-6 py-3.5 text-center">Condition</th>
                @if (Auth::user()->role === 'superadmin' ||
                        (Auth::user()->role === 'admin_site' && Auth::user()->site_id === $siteData->id))
                    <th class="px-4 sm:px-6 py-3.5 text-right w-48">Actions</th>
                @endif
            </tr>
        </thead>

        <tbody class="text-xs font-medium divide-y divide-slate-100 sm:text-sm text-slate-700">
            @forelse ($assets as $item)
                <tr class="transition-colors hover:bg-slate-50/80">
                    <td class="px-4 sm:px-6 py-3.5 text-center text-slate-400 font-bold">
                        {{ ($assets->currentPage() - 1) * $assets->perPage() + $loop->iteration }}
                    </td>

                    <td class="px-4 sm:px-6 py-3.5">
                        <div class="flex flex-col">
                            <span class="font-bold text-slate-800">{{ $item->sparepart->item_name }}</span>
                            <span class="text-[11px] text-slate-400 font-normal">{{ $item->sparepart->type }}</span>
                        </div>
                    </td>

                    <td class="px-4 sm:px-6 py-3.5 text-center font-mono">
                        <span class="px-2 py-1 text-xs rounded-md bg-slate-100 text-slate-600">
                            {{ $item->sparepart->serial_number ?? '-' }}
                        </span>
                    </td>

                    <td class="px-4 sm:px-6 py-3.5 text-center">
                        <div
                            class="inline-flex flex-col items-center justify-center min-w-[55px] py-0.5 px-2 bg-white border border-blue-200 rounded-lg">
                            <span class="text-base font-black text-blue-700">{{ $item->qty }}</span>
                            <span
                                class="text-[8px] font-bold text-blue-400 uppercase">{{ $item->sparepart->uom }}</span>
                        </div>
                    </td>

                    <td class="px-4 sm:px-6 py-3.5 text-center">
                        @php
                            $colorMap = [
                                'new' => 'bg-emerald-50 text-emerald-700 border-emerald-200/60',
                                'used-good' => 'bg-blue-50 text-blue-700 border-blue-200/60',
                                'damaged' => 'bg-rose-50 text-rose-700 border-rose-200/60',
                                'repair' => 'bg-amber-50 text-amber-700 border-amber-200/60',
                            ];
                            $style = $colorMap[$item->condition] ?? 'bg-slate-50 text-slate-600 border-slate-200';
                        @endphp
                        <span
                            class="px-2.5 py-0.5 text-[10px] font-extrabold uppercase border rounded-md {{ $style }}">
                            {{ str_replace('-', ' ', $item->condition) }}
                        </span>
                    </td>

                    @if (Auth::user()->role === 'superadmin' ||
                            (Auth::user()->role === 'admin_site' && Auth::user()->site_id === $siteData->id))
                        <td class="px-4 sm:px-6 py-3.5 text-right">
                            <div class="flex justify-end items-center gap-1.5">
                                {{-- ADJUST --}}
                                <button
                                    onclick="openAdjustModal({{ $item->id }}, '{{ addslashes($item->sparepart->item_name) }}', {{ $item->qty }}, '{{ $item->condition }}')"
                                    class="flex items-center justify-center transition-all rounded-lg w-7 h-7 text-amber-600 bg-amber-50 hover:bg-amber-600 hover:text-white"
                                    title="Adjust Stock">
                                    <i class="text-xs fa-solid fa-sliders"></i>
                                </button>

                                {{-- EDIT --}}
                                <button onclick="openEditModal(this)" data-item='@json($item->sparepart)'
                                    class="flex items-center justify-center text-blue-600 transition-all rounded-lg w-7 h-7 bg-blue-50 hover:bg-blue-600 hover:text-white"
                                    title="Edit Sparepart">
                                    <i class="text-xs fa-solid fa-pen-to-square"></i>
                                </button>

                                {{-- DETAIL (VIEW) --}}
                                <button
                                    onclick='openDetailModal(@json($item->sparepart), @json($all_sites))'
                                    class="flex items-center justify-center transition-all rounded-lg w-7 h-7 text-slate-500 bg-slate-100 hover:bg-slate-800 hover:text-white"
                                    title="View Details">
                                    <i class="text-xs fa-solid fa-eye"></i>
                                </button>

                                {{-- MOVE --}}
                                <button
                                    onclick="openMoveModal({{ $item->id }}, '{{ addslashes($item->sparepart->item_name) }}', {{ $item->qty }}, '{{ $item->condition }}')"
                                    class="px-2.5 py-1 text-[10px] font-bold text-white bg-amber-600 rounded-lg hover:bg-amber-700 transition-all active:scale-95">
                                    MOVE
                                </button>

                                {{-- DELETE --}}
                                <button type="button"
                                    onclick="openDeleteModal('{{ route('sparepart.stock.destroy', [$slug, $item->id]) }}', '{{ addslashes($item->sparepart->item_name) }} ({{ strtoupper($item->condition) }})')"
                                    class="flex items-center justify-center transition-all rounded-lg w-7 h-7 text-rose-600 bg-rose-50 hover:bg-rose-600 hover:text-white"
                                    title="Delete Condition Stock">
                                    <i class="text-xs fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="p-10 text-center text-slate-400">
                        <i class="block mb-2 text-3xl opacity-50 fa-solid fa-boxes-stacked"></i>
                        <p class="text-sm font-bold text-slate-700">No spareparts registered in this site</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $assets->links() }}</div>

<!-- MODAL DETAIL ASSET -->
<div id="detailModal"
    class="fixed inset-0 z-50 flex items-center justify-center hidden p-3 transition-all duration-300 sm:p-4 bg-slate-900/60 backdrop-blur-xs">
    <div id="modalWrapper"
        class="w-full max-w-3xl overflow-hidden transition-all transform scale-95 bg-white shadow-2xl opacity-0 rounded-2xl sm:rounded-3xl max-h-[90vh] flex flex-col">
        {{-- HEADER --}}
        <div class="flex items-center justify-between p-5 border-b border-slate-100 bg-slate-50/50 shrink-0">
            <div class="flex items-center gap-4">
                <div class="relative group shrink-0">
                    <div id="d_image_container"
                        class="relative w-24 h-24 overflow-hidden transition-all bg-white border cursor-pointer border-slate-200 shadow-2xs rounded-xl hover:ring-2 hover:ring-blue-500"
                        onclick="expandImage()">

                        <img id="d_image"
                            class="object-cover w-full h-full transition-all duration-500 group-hover:scale-110">

                        <div
                            class="absolute inset-0 z-10 flex flex-col items-center justify-center transition-all duration-300 opacity-0 pointer-events-none bg-black/40 group-hover:opacity-100">
                            <i class="text-sm text-white fa-solid fa-magnifying-glass-plus"></i>
                        </div>
                    </div>

                    <div id="no-image-placeholder"
                        class="flex flex-col items-center justify-center hidden w-24 h-24 border border-dashed text-slate-300 border-slate-300 bg-slate-50 rounded-xl">
                        <i class="mb-1 text-2xl opacity-50 fa-solid fa-image-slash"></i>
                        <span class="text-[9px] font-bold uppercase tracking-widest opacity-60">No Image</span>
                    </div>
                </div>
                <div>
                    <h3 id="d_item_name" class="text-lg font-black text-slate-800"></h3>
                    <p id="d_type" class="font-mono text-xs text-slate-500"></p>
                    <span id="d_serial_number"
                        class="inline-block mt-1 px-2 py-0.5 bg-slate-200 text-slate-700 text-[10px] rounded font-bold"></span>
                    <span id="d_source_data"
                        class="inline-block mt-1 ml-1 px-2 py-0.5 bg-emerald-100 text-emerald-700 text-[10px] rounded font-bold"></span>
                </div>
            </div>
            <button onclick="closeDetailModal()"
                class="p-2 rounded-full text-slate-400 hover:text-slate-600 bg-slate-100">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="grid grid-cols-1 overflow-y-auto text-xs md:grid-cols-2 sm:text-sm">
            <div class="p-5 border-b md:border-b-0 md:border-r border-slate-100">
                <p class="flex items-center gap-2 mb-3 font-bold text-slate-700"><i
                        class="text-blue-600 fa-solid fa-layer-group"></i> Stock Distribution</p>
                <div class="overflow-hidden border border-slate-200 rounded-xl">
                    <table class="w-full text-xs">
                        <thead class="font-bold text-slate-600 bg-slate-100/70">
                            <tr>
                                <th class="p-2.5 text-left">Site Location</th>
                                <th class="p-2.5 text-center">Qty</th>
                                <th class="p-2.5 text-center">Condition</th>
                            </tr>
                        </thead>
                        <tbody id="d_stock_table" class="divide-y divide-slate-100"></tbody>
                    </table>
                </div>
            </div>
            <div class="p-5 bg-slate-50">
                <p class="flex items-center gap-2 mb-3 font-bold text-slate-700"><i
                        class="text-amber-600 fa-solid fa-clock-rotate-left"></i> Tracking History</p>
                <div class="relative pl-5 border-l-2 border-amber-300 space-y-4 max-h-[250px] overflow-y-auto"
                    id="d_history"></div>
            </div>
        </div>

        <div class="p-4 text-right bg-white border-t border-slate-100 shrink-0">
            <button onclick="closeDetailModal()"
                class="px-5 py-2 text-xs font-bold transition-colors text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200">Close
                Details</button>
        </div>
    </div>
</div>

<!-- MODAL FULLSCREEN IMAGE VIEW -->
<div id="image-viewer"
    class="fixed inset-0 z-[100] hidden bg-slate-950/90 backdrop-blur-md items-center justify-center p-4">
    <button onclick="closeImageViewer()"
        class="absolute z-10 p-2 text-white transition-all top-4 right-4 hover:scale-110">
        <i class="text-3xl fa-solid fa-xmark"></i>
    </button>
    <img id="full-image" src=""
        class="max-w-full max-h-full transition-all duration-300 transform scale-95 shadow-2xl rounded-xl"
        alt="Full Preview">
</div>

<!-- MODAL DELETE -->
<div id="modal-delete"
    class="fixed inset-0 z-[100] flex items-center justify-center hidden p-4 bg-slate-900/60 backdrop-blur-xs transition-all">
    <div
        class="relative w-full max-w-sm p-6 text-center transition-all duration-300 transform scale-95 bg-white shadow-2xl opacity-0 modal-content rounded-2xl">
        <div class="flex justify-center mb-4">
            <div class="flex items-center justify-center w-12 h-12 text-rose-600 bg-rose-100 rounded-xl">
                <i class="text-lg fa-solid fa-triangle-exclamation"></i>
            </div>
        </div>
        <h3 class="mb-1 text-base font-extrabold text-slate-800">Are you sure?</h3>
        <p class="mb-6 text-xs text-slate-500">You are about to delete <span id="delete-item-name"
                class="font-bold text-slate-800"></span>.</p>
        <form id="form-confirm-delete" method="POST">
            @csrf @method('DELETE')
            <div class="flex items-center justify-end gap-2">
                <button type="button" onclick="closeDeleteModal()"
                    class="w-full py-2.5 text-xs font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200">Cancel</button>
                <button type="submit"
                    class="w-full py-2.5 text-xs font-bold text-white bg-rose-600 rounded-xl hover:bg-rose-700 shadow-md shadow-rose-600/20 active:scale-95">Yes,
                    Delete</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL MOVE (TRANSFER) -->
<div id="modal-move"
    class="fixed inset-0 z-[60] flex items-center justify-center hidden p-3 sm:p-4 bg-slate-900/60 backdrop-blur-xs">
    <div
        class="w-full max-w-md overflow-hidden bg-white shadow-2xl rounded-2xl sm:rounded-3xl max-h-[90vh] flex flex-col">
        <div class="flex items-center gap-3 px-6 py-4 border-b border-amber-100 bg-amber-50/60 shrink-0">
            <div class="p-2 text-white bg-amber-600 rounded-xl"><i class="text-base fa-solid fa-truck-fast"></i></div>
            <div>
                <h3 class="text-base font-extrabold text-slate-800">Transfer Request</h3>
                <p id="move-asset-tag" class="font-mono text-[11px] font-bold text-amber-700 uppercase"></p>
            </div>
        </div>
        <form id="form-move" method="POST" class="p-6 space-y-4 overflow-y-auto">
            @csrf
            <div class="space-y-1.5">
                <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Destination Site</label>
                <select name="to_site_id"
                    class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-bold border outline-none border-slate-200 bg-slate-50 rounded-xl focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 transition-all"
                    required>
                    <option value="" disabled selected>Select Destination</option>
                    @foreach ($all_sites as $s)
                        @if ($s->id !== $siteData->id)
                            <option value="{{ $s->id }}">{{ $s->machine_name }}
                                ({{ $s->branch->branch_name ?? 'Branch' }})
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Condition</label>
                    <select name="condition" id="target-condition"
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-bold border outline-none border-slate-200 bg-slate-50 rounded-xl"
                        required>
                        <option value="new">NEW</option>
                        <option value="used-good">USED GOOD</option>
                        <option value="damaged">DAMAGED</option>
                        <option value="repair">REPAIRED</option>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Quantity</label>
                    <input type="number" name="qty" id="move-quantity" min="1"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs sm:text-sm font-bold"
                        required>
                </div>
            </div>
            <p id="max-info" class="text-[11px] font-bold text-right text-slate-400 italic"></p>
            <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeMoveModal()"
                    class="px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200">Cancel</button>
                <button type="submit"
                    class="px-5 py-2.5 text-xs font-bold text-white bg-amber-600 rounded-xl shadow-md shadow-amber-600/20 active:scale-95">Request
                    Transfer</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openDetailModal(item, sites) {
        document.getElementById('d_item_name').innerText = item.item_name;
        document.getElementById('d_type').innerText = "Type: " + (item.type || '-');
        document.getElementById('d_serial_number').innerText = "SN: " + (item.serial_number || '-');
        document.getElementById('d_source_data').innerText = "Source: " + (item.source_data || 'Manual Input');

        const imgElement = document.getElementById('d_image');
        const imgContainer = document.getElementById('d_image_container');
        const placeholder = document.getElementById('no-image-placeholder');

        if (item.image) {
            imgElement.src = `/storage/${item.image}`;
            imgContainer.classList.remove('hidden');
            imgElement.classList.remove('hidden');
            placeholder.classList.add('hidden');
            placeholder.classList.remove('flex');
        } else {
            imgContainer.classList.add('hidden');
            placeholder.classList.remove('hidden');
            placeholder.classList.add('flex');
        }

        const stockTable = document.getElementById('d_stock_table');
        stockTable.innerHTML = '';
        if (item.stocks && item.stocks.length > 0) {
            item.stocks.forEach(s => {
                stockTable.innerHTML += `
                <tr class="transition-colors hover:bg-slate-50">
                    <td class="p-2.5 font-bold text-slate-700">${s.site.machine_name}</td>
                    <td class="p-2.5 font-extrabold text-center text-blue-600">${s.qty}</td>
                    <td class="p-2.5 text-center"><span class="text-[9px] px-2 py-0.5 rounded-md bg-slate-100 font-bold uppercase">${s.condition}</span></td>
                </tr>`;
            });
        } else {
            stockTable.innerHTML =
                '<tr><td colspan="3" class="p-4 italic text-center text-slate-400">No active stock</td></tr>';
        }

        const historyContainer = document.getElementById('d_history');
        historyContainer.innerHTML = (item.histories && item.histories.length > 0) ? '' :
            '<p class="text-xs italic text-slate-400">No history records found.</p>';
        if (item.histories) {
            item.histories.forEach(h => {
                const date = new Date(h.created_at).toLocaleString('en-US', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                historyContainer.innerHTML += `
                <div class="relative">
                    <div class="absolute -left-[26px] mt-1.5 w-3.5 h-3.5 rounded-full bg-amber-500 border-2 border-white"></div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase">${date}</p>
                    <p class="text-xs font-bold text-slate-800">${h.action}</p>
                    <p class="text-[11px] text-slate-600">${h.from_site?.machine_name || 'Initial'} &rarr; ${h.to_site?.machine_name || 'Unknown'}</p>
                    <p class="text-[10px] italic text-slate-500">Qty: ${h.qty} | ${h.condition}</p>
                </div>`;
            });
        }

        const modal = document.getElementById('detailModal');
        const wrapper = document.getElementById('modalWrapper');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            wrapper.classList.replace('scale-95', 'scale-100');
            wrapper.classList.replace('opacity-0', 'opacity-100');
        }, 10);
    }

    function closeDetailModal() {
        const modal = document.getElementById('detailModal');
        const wrapper = document.getElementById('modalWrapper');
        wrapper.classList.replace('scale-100', 'scale-95');
        wrapper.classList.replace('opacity-100', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 200);
    }

    function expandImage() {
        const imgElement = document.getElementById('d_image');
        if (imgElement.classList.contains('hidden')) return;

        const viewer = document.getElementById('image-viewer');
        const fullImg = document.getElementById('full-image');

        fullImg.src = imgElement.src;
        viewer.classList.remove('hidden');
        viewer.classList.add('flex');

        setTimeout(() => {
            fullImg.classList.replace('scale-95', 'scale-100');
        }, 10);
    }

    function closeImageViewer() {
        const viewer = document.getElementById('image-viewer');
        const fullImg = document.getElementById('full-image');
        fullImg.classList.replace('scale-100', 'scale-95');
        setTimeout(() => {
            viewer.classList.add('hidden');
            viewer.classList.remove('flex');
        }, 200);
    }

    function openDeleteModal(url, itemName) {
        document.getElementById('form-confirm-delete').action = url;
        document.getElementById('delete-item-name').innerText = itemName;
        const modal = document.getElementById('modal-delete');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            modal.querySelector('.modal-content').classList.replace('scale-95', 'scale-100');
            modal.querySelector('.modal-content').classList.replace('opacity-0', 'opacity-100');
        }, 10);
    }

    function closeDeleteModal() {
        const modal = document.getElementById('modal-delete');
        modal.querySelector('.modal-content').classList.replace('scale-100', 'scale-95');
        modal.querySelector('.modal-content').classList.replace('opacity-100', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 200);
    }

    function openMoveModal(stockId, itemName, currentQty, currentCondition) {
        const modal = document.getElementById('modal-move');
        document.getElementById('move-asset-tag').innerText = itemName + " (" + currentCondition.toUpperCase() + ")";
        document.getElementById('form-move').action = "/movement/request/" + stockId;
        document.getElementById('target-condition').value = currentCondition;

        const qtyInput = document.getElementById('move-quantity');
        qtyInput.max = currentQty;
        qtyInput.value = 1;
        document.getElementById('max-info').innerText = "* Available: " + currentQty + " pcs";

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeMoveModal() {
        const modal = document.getElementById('modal-move');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    window.onclick = function(event) {
        if (event.target.id === 'detailModal') closeDetailModal();
        if (event.target.id === 'modal-delete') closeDeleteModal();
        if (event.target.id === 'modal-move') closeMoveModal();
        if (event.target.id === 'image-viewer') closeImageViewer();
    }
</script>
