<div class="w-full overflow-x-auto bg-white ">
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
                        (Auth::user()?->role === 'admin_site' && Auth::user()?->site_id === $siteData->id))
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
                                class="text-sm font-bold leading-snug text-slate-900">{{ $item->sparepart->item_name }}</span>
                            <span class="text-[11px] text-slate-400 font-normal mt-0.5"><i
                                    class="fa-solid fa-cube text-[10px] mr-1 text-slate-300"></i>{{ $item->sparepart->type ?? 'Standard Model' }}</span>
                        </div>
                    </td>

                    {{-- Serial Number --}}
                    <td class="px-6 py-4 font-mono text-center">
                        <span
                            class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-slate-100 text-slate-600 border border-slate-200/60">
                            {{ $item->sparepart->serial_number ?? '-' }}
                        </span>
                    </td>

                    {{-- Quantity Badge --}}
                    <td class="px-6 py-4 text-center">
                        <div
                            class="inline-flex flex-col items-center justify-center min-w-[60px] py-1 px-2.5 bg-blue-50/80 border border-blue-200/80 rounded-xl">
                            <span class="text-base font-black leading-none text-blue-700">{{ $item->qty }}</span>
                            <span
                                class="text-[8px] font-extrabold text-blue-500 uppercase tracking-wider mt-0.5">{{ $item->sparepart->uom }}</span>
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
                            (Auth::user()?->role === 'admin_site' && Auth::user()?->site_id === $siteData->id))
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end items-center gap-1.5">
                                {{-- ADJUST STOK --}}
                                <button
                                    onclick="openAdjustModal({{ $item->id }}, '{{ addslashes($item->sparepart->item_name) }}', {{ $item->qty }}, '{{ $item->condition }}')"
                                    class="flex items-center justify-center w-8 h-8 transition-all border rounded-xl text-amber-600 bg-amber-50 border-amber-100 hover:bg-amber-600 hover:text-white active:scale-95"
                                    title="Adjust Stock & Condition">
                                    <i class="text-xs fa-solid fa-sliders"></i>
                                </button>

                                {{-- EDIT --}}
                                <button onclick="openEditModal(this)" data-item='@json($item->sparepart)'
                                    class="flex items-center justify-center w-8 h-8 text-blue-600 transition-all border border-blue-100 rounded-xl bg-blue-50 hover:bg-blue-600 hover:text-white active:scale-95"
                                    title="Edit Sparepart Info">
                                    <i class="text-xs fa-solid fa-pen-to-square"></i>
                                </button>

                                {{-- DETAIL (VIEW) --}}
                                <button
                                    onclick='openDetailModal(@json($item->sparepart), @json($all_sites))'
                                    class="flex items-center justify-center w-8 h-8 transition-all border rounded-xl text-slate-600 bg-slate-100 border-slate-200 hover:bg-slate-900 hover:text-white active:scale-95"
                                    title="View Full Details">
                                    <i class="text-xs fa-solid fa-eye"></i>
                                </button>

                                {{-- MOVE (TRANSFER) --}}
                                <button
                                    onclick="openMoveModal({{ $item->id }}, '{{ addslashes($item->sparepart->item_name) }}', {{ $item->qty }}, '{{ $item->condition }}')"
                                    class="px-2.5 py-1.5 text-[10px] font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-xl shadow-xs transition-all active:scale-95 flex items-center gap-1">
                                    <i class="fa-solid fa-truck-fast text-[10px]"></i> Move
                                </button>

                                {{-- DELETE --}}
                                <button type="button"
                                    onclick="openDeleteModal('{{ route('sparepart.stock.destroy', [$slug, $item->id]) }}', '{{ addslashes($item->sparepart->item_name) }} ({{ strtoupper($item->condition) }})')"
                                    class="flex items-center justify-center w-8 h-8 transition-all border rounded-xl text-rose-600 bg-rose-50 border-rose-100 hover:bg-rose-600 hover:text-white active:scale-95"
                                    title="Delete Condition Stock">
                                    <i class="text-xs fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="p-12 text-center text-slate-400">
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

<div class="p-2 mt-4">{{ $assets->links() }}</div>

<div id="detailModal"
    class="fixed inset-0 z-50 flex items-center justify-center hidden p-4 transition-all duration-300 bg-slate-900/60 backdrop-blur-xs">
    <div id="modalWrapper"
        class="w-full max-w-3xl overflow-hidden transition-all transform scale-95 opacity-0 bg-white border border-slate-100 shadow-2xl rounded-3xl max-h-[90vh] flex flex-col">

        {{-- HEADER --}}
        <div class="flex items-center justify-between p-6 border-b border-slate-100 bg-slate-50/50 shrink-0">
            <div class="flex items-center gap-4">
                <div class="relative group shrink-0">
                    <div id="d_image_container"
                        class="relative w-20 h-20 overflow-hidden transition-all bg-white border cursor-pointer border-slate-200 shadow-2xs rounded-2xl hover:ring-2 hover:ring-blue-500"
                        onclick="expandImage()">

                        <img id="d_image"
                            class="object-cover w-full h-full transition-all duration-500 group-hover:scale-110">

                        <div
                            class="absolute inset-0 z-10 flex flex-col items-center justify-center transition-all duration-300 opacity-0 pointer-events-none bg-black/40 group-hover:opacity-100">
                            <i class="text-xs text-white fa-solid fa-magnifying-glass-plus"></i>
                        </div>
                    </div>

                    <div id="no-image-placeholder"
                        class="flex flex-col items-center justify-center hidden w-20 h-20 border border-dashed text-slate-300 border-slate-300 bg-slate-50 rounded-2xl">
                        <i class="mb-1 text-xl opacity-50 fa-solid fa-image-slash"></i>
                        <span class="text-[9px] font-bold uppercase tracking-widest opacity-60">No Image</span>
                    </div>
                </div>
                <div>
                    <h3 id="d_item_name" class="text-lg font-extrabold leading-snug text-slate-900"></h3>
                    <p id="d_type" class="font-mono text-xs font-semibold text-slate-500 mt-0.5"></p>
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        <span id="d_serial_number"
                            class="px-2.5 py-0.5 bg-slate-100 border border-slate-200 text-slate-700 text-[10px] rounded-lg font-bold font-mono"></span>
                        <span id="d_source_data"
                            class="px-2.5 py-0.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-[10px] rounded-lg font-bold"></span>
                    </div>
                </div>
            </div>
            <button onclick="closeDetailModal()" type="button"
                class="flex items-center justify-center w-8 h-8 transition-colors rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <i class="text-base fa-solid fa-xmark"></i>
            </button>
        </div>

        {{-- BODY GRID --}}
        <div class="grid grid-cols-1 overflow-y-auto text-xs md:grid-cols-2">

            {{-- Stock Distribution --}}
            <div class="p-6 border-b md:border-b-0 md:border-r border-slate-100">
                <p class="flex items-center gap-2 mb-3 font-bold text-slate-800 uppercase tracking-wider text-[11px]">
                    <i class="text-blue-600 fa-solid fa-layer-group"></i> Stock Distribution Across Sites
                </p>
                <div class="overflow-hidden border border-slate-200/80 rounded-2xl">
                    <table class="w-full text-xs text-left">
                        <thead
                            class="font-bold text-slate-500 bg-slate-50 border-b border-slate-100 text-[10px] uppercase">
                            <tr>
                                <th class="p-3">Site Location</th>
                                <th class="p-3 text-center">Qty</th>
                                <th class="p-3 text-center">Condition</th>
                            </tr>
                        </thead>
                        <tbody id="d_stock_table" class="font-medium divide-y divide-slate-100 text-slate-700"></tbody>
                    </table>
                </div>
            </div>

            {{-- Tracking History --}}
            <div class="p-6 bg-slate-50/50">
                <p class="flex items-center gap-2 mb-4 font-bold text-slate-800 uppercase tracking-wider text-[11px]">
                    <i class="text-amber-600 fa-solid fa-clock-rotate-left"></i> Tracking Movement History
                </p>
                <div class="relative pl-5 border-l-2 border-amber-300 space-y-4 max-h-[250px] overflow-y-auto pr-2"
                    id="d_history"></div>
            </div>

        </div>

        {{-- FOOTER --}}
        <div class="p-4 text-right bg-white border-t border-slate-100 shrink-0">
            <button onclick="closeDetailModal()" type="button"
                class="px-5 py-2.5 text-xs font-bold transition-colors text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl">
                Close Details
            </button>
        </div>
    </div>
</div>

<div id="image-viewer"
    class="fixed inset-0 z-[100] hidden bg-slate-950/90 backdrop-blur-md items-center justify-center p-4">
    <button onclick="closeImageViewer()" type="button"
        class="absolute z-10 p-2 text-white transition-transform hover:text-slate-300 top-4 right-4 hover:scale-110">
        <i class="text-3xl fa-solid fa-xmark"></i>
    </button>
    <img id="full-image" src=""
        class="max-w-full max-h-full transition-all duration-300 transform scale-95 shadow-2xl rounded-2xl"
        alt="Full Preview">
</div>

<div id="modal-delete"
    class="fixed inset-0 z-[100] flex items-center justify-center hidden p-4 bg-slate-900/60 backdrop-blur-xs transition-all">
    <div
        class="relative w-full max-w-sm p-6 text-center transition-all duration-300 transform scale-95 bg-white border shadow-2xl opacity-0 border-slate-100 modal-content rounded-3xl">
        <div class="flex justify-center mb-4">
            <div
                class="flex items-center justify-center w-12 h-12 border rounded-2xl bg-rose-50 border-rose-100 text-rose-600">
                <i class="text-xl fa-solid fa-triangle-exclamation"></i>
            </div>
        </div>
        <h3 class="mb-1 text-base font-extrabold text-slate-900">Are you sure?</h3>
        <p class="mb-6 text-xs font-medium text-slate-500">You are about to delete <strong id="delete-item-name"
                class="text-slate-900"></strong>.</p>
        <form id="form-confirm-delete" method="POST">
            @csrf @method('DELETE')
            <div class="flex items-center justify-end gap-3">
                <button type="button" onclick="closeDeleteModal()"
                    class="w-full py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">
                    Cancel
                </button>
                <button type="submit"
                    class="w-full py-2.5 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 active:scale-[0.98] transition-all rounded-xl shadow-md shadow-rose-600/20">
                    Yes, Delete
                </button>
            </div>
        </form>
    </div>
</div>

<div id="modal-move"
    class="fixed inset-0 z-[60] flex items-center justify-center hidden p-4 bg-slate-900/60 backdrop-blur-xs transition-all">
    <div
        class="w-full max-w-md overflow-hidden bg-white border border-slate-100 shadow-2xl rounded-3xl max-h-[90vh] flex flex-col">
        <div class="flex items-center gap-3 px-6 py-4 border-b border-amber-100 bg-amber-50/60 shrink-0">
            <div class="p-2.5 text-amber-700 bg-amber-100/80 rounded-2xl"><i
                    class="text-base fa-solid fa-truck-fast"></i></div>
            <div>
                <h3 class="text-base font-extrabold text-slate-900">Transfer Request</h3>
                <p id="move-asset-tag" class="font-mono text-[11px] font-bold text-amber-700 uppercase"></p>
            </div>
        </div>
        <form id="form-move" method="POST" class="p-6 space-y-4 overflow-y-auto">
            @csrf
            <div class="space-y-1.5">
                <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Destination Site</label>
                <select name="to_site_id" required
                    class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-bold border border-slate-200 bg-slate-50 rounded-xl focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 outline-none transition-all text-slate-800">
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
                    <select name="condition" id="target-condition" required
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-bold border border-slate-200 bg-slate-50 rounded-xl outline-none text-slate-800">
                        <option value="new">NEW</option>
                        <option value="used-good">USED GOOD</option>
                        <option value="damaged">DAMAGED</option>
                        <option value="repair">REPAIRED</option>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Quantity</label>
                    <input type="number" name="qty" id="move-quantity" min="1" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs sm:text-sm font-bold text-slate-800">
                </div>
            </div>
            <p id="max-info" class="text-[11px] font-bold text-right text-slate-400 italic"></p>
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeMoveModal()"
                    class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">
                    Cancel
                </button>
                <button type="submit"
                    class="px-5 py-2.5 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-xl shadow-md shadow-amber-600/20 active:scale-[0.98] transition-all">
                    Request Transfer
                </button>
            </div>
        </form>
    </div>
</div>

{{-- JAVASCRIPT LOGIC --}}
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
                    <td class="p-3 font-bold text-slate-800">${s.site.machine_name}</td>
                    <td class="p-3 font-extrabold text-center text-blue-600">${s.qty}</td>
                    <td class="p-3 text-center"><span class="text-[9px] px-2 py-0.5 rounded-md bg-slate-100 font-bold uppercase text-slate-700">${s.condition}</span></td>
                </tr>`;
            });
        } else {
            stockTable.innerHTML =
                '<tr><td colspan="3" class="p-4 italic text-center text-slate-400">No active stock</td></tr>';
        }

        const historyContainer = document.getElementById('d_history');
        historyContainer.innerHTML = (item.histories && item.histories.length > 0) ? '' :
            '<p class="text-xs italic text-slate-400">No movement history recorded.</p>';
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
                    <p class="text-xs font-bold text-slate-900 mt-0.5">${h.action}</p>
                    <p class="text-[11px] font-medium text-slate-600">${h.from_site?.machine_name || 'Initial'} &rarr; ${h.to_site?.machine_name || 'Unknown'}</p>
                    <p class="text-[10px] italic font-semibold text-slate-400 mt-0.5">Qty: ${h.qty} | Condition: ${h.condition}</p>
                </div>`;
            });
        }

        const modal = document.getElementById('detailModal');
        const wrapper = document.getElementById('modalWrapper');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
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
        document.body.classList.remove('overflow-hidden');
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
        document.body.classList.add('overflow-hidden');
        setTimeout(() => {
            modal.querySelector('.modal-content').classList.replace('scale-95', 'scale-100');
            modal.querySelector('.modal-content').classList.replace('opacity-0', 'opacity-100');
        }, 10);
    }

    function closeDeleteModal() {
        const modal = document.getElementById('modal-delete');
        modal.querySelector('.modal-content').classList.replace('scale-100', 'scale-95');
        modal.querySelector('.modal-content').classList.replace('opacity-100', 'opacity-0');
        document.body.classList.remove('overflow-hidden');
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
        document.getElementById('max-info').innerText = "* Available stock: " + currentQty + " pcs";

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function closeMoveModal() {
        const modal = document.getElementById('modal-move');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    window.onclick = function(event) {
        if (event.target.id === 'detailModal') closeDetailModal();
        if (event.target.id === 'modal-delete') closeDeleteModal();
        if (event.target.id === 'modal-move') closeMoveModal();
        if (event.target.id === 'image-viewer') closeImageViewer();
    }
</script>
