<div class="overflow-x-auto bg-white border border-gray-100 shadow-sm rounded-xl">
    <table class="w-full border-collapse">
        <thead>
            <tr
                class="text-xs font-semibold tracking-wider text-gray-500 uppercase border-b border-gray-100 bg-gray-50/50">
                <th class="px-6 py-4 text-center">No</th>
                <th class="px-6 py-4 text-left">Item Information</th>
                <th class="px-6 py-4 text-center">Serial Number</th>
                <th class="px-6 py-4 text-center">Quantity</th>
                <th class="px-6 py-4 text-left">Condition</th>
                @if (Auth::user()->role === 'superadmin' ||
                        (Auth::user()->role === 'admin_site' && Auth::user()->site_id === $siteData->id))
                    <th class="px-6 py-4 text-right">Actions</th>
                @endif
            </tr>
        </thead>

        <tbody class="divide-y divide-gray-50">
            @forelse ($assets as $item)
                <tr class="transition-colors hover:bg-blue-50/30 group">
                    <td class="px-6 py-4 text-sm text-center text-gray-400">
                        {{ ($assets->currentPage() - 1) * $assets->perPage() + $loop->iteration }}
                    </td>

                    <td class="px-6 py-4">
                        <div class="flex flex-col">
                            <span class="font-bold text-gray-800">{{ $item->sparepart->item_name }}</span>
                            <span class="text-[11px] text-gray-500 italic">{{ $item->sparepart->type }}</span>
                        </div>
                    </td>

                    <td class="px-6 py-4 text-center">
                        <span class="px-2 py-1 font-mono text-xs bg-gray-100 rounded">
                            {{ $item->sparepart->serial_number ?? '-' }}
                        </span>
                    </td>

                    <td class="px-6 py-4 text-center">
                        <div
                            class="inline-flex flex-col items-center justify-center min-w-[60px] py-1 bg-white border-2 border-blue-100 rounded-lg">
                            <span class="text-lg font-black text-blue-700">{{ $item->qty }}</span>
                            <span
                                class="text-[9px] font-bold text-blue-400 uppercase">{{ $item->sparepart->uom }}</span>
                        </div>
                    </td>

                    <td class="px-6 py-4">
                        @php
                            $colorMap = [
                                'new' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
                                'used-good' => 'bg-blue-50 text-blue-600 border-blue-100',
                                'damaged' => 'bg-red-50 text-red-600 border-red-100',
                                'repair' => 'bg-amber-50 text-amber-600 border-amber-100',
                            ];
                            $style = $colorMap[$item->condition] ?? 'bg-gray-50 text-gray-600 border-gray-100';
                        @endphp
                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase border rounded-md {{ $style }}">
                            {{ str_replace('-', ' ', $item->condition) }}
                        </span>
                    </td>

                    @if (Auth::user()->role === 'superadmin' ||
                            (Auth::user()->role === 'admin_site' && Auth::user()->site_id === $siteData->id))
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                {{-- ADJUST --}}
                                <button
                                    onclick="openAdjustModal({{ $item->sparepart_id }}, '{{ addslashes($item->sparepart->item_name) }}', {{ $item->qty }}, '{{ $item->condition }}')"
                                    class="flex items-center justify-center w-8 h-8 transition-all rounded-lg text-amber-600 bg-amber-50 hover:bg-amber-600 hover:text-white">
                                    <i class="fa-solid fa-sliders"></i>
                                </button>

                                {{-- EDIT --}}
                                <button onclick="openEditModal(this)" data-item='@json($item->sparepart)'
                                    class="flex items-center justify-center w-8 h-8 text-blue-600 transition-all rounded-lg bg-blue-50 hover:bg-blue-600 hover:text-white">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>

                                {{-- DETAIL (VIEW) --}}
                                <button
                                    onclick='openDetailModal(@json($item->sparepart), @json($all_sites))'
                                    class="flex items-center justify-center w-8 h-8 text-gray-500 transition-all bg-gray-100 rounded-lg hover:bg-gray-800 hover:text-white"
                                    title="View Details">
                                    <i class="fa-solid fa-eye"></i>
                                </button>

                                {{-- MOVE --}}
                                <button
                                    onclick="openMoveModal({{ $item->id }}, '{{ addslashes($item->sparepart->item_name) }}', {{ $item->qty }}, '{{ $item->condition }}')"
                                    class="px-3 py-1 text-[11px] font-bold text-white bg-orange-500 rounded-lg hover:bg-orange-600 transition-all">
                                    MOVE
                                </button>

                                {{-- DELETE --}}
                                {{-- <button type="button"
                                onclick="openDeleteModal('{{ route('sparepart.destroy', [$slug, $item->sparepart_id]) }}', '{{ addslashes($item->sparepart->item_name) }}')"
                                class="flex items-center justify-center w-8 h-8 transition-all rounded-lg text-rose-600 bg-rose-50 hover:bg-rose-600 hover:text-white">
                                <i class="fa-solid fa-trash-can"></i>
                            </button> --}}
                                {{-- Tombol Delete yang sudah diperbaiki --}}
                                <button type="button"
                                    onclick="openDeleteModal('{{ route('sparepart.stock.destroy', [$slug, $item->id]) }}', '{{ addslashes($item->sparepart->item_name) }} ({{ strtoupper($item->condition) }})')"
                                    class="flex items-center justify-center w-8 h-8 transition-all rounded-lg text-rose-600 bg-rose-50 hover:bg-rose-600 hover:text-white"
                                    title="Delete This Condition Only">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                {{-- Empty State --}}
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">{{ $assets->links() }}</div>

<!-- =========================================================================
     MODAL SECTION
     ========================================================================= -->

<!-- MODAL DETAIL ASSET -->
<div id="detailModal" class="fixed inset-0 z-50 items-center justify-center hidden bg-black/50 backdrop-blur-sm">
    <div id="modalWrapper"
        class="w-full max-w-4xl overflow-hidden transition transform scale-95 bg-white shadow-2xl opacity-0 rounded-2xl">
        {{-- HEADER --}}
        <div class="flex items-center justify-between p-6 border-b bg-gray-50">
            <div class="flex items-center gap-6">
                <!-- Image Wrapper dengan Tampilan yang Dirapikan -->
                <div class="relative group">
                    <!-- Kontainer utama gambar -->
                    <div id="d_image_container"
                        class="relative w-32 h-32 overflow-hidden transition-all bg-white border-2 border-gray-100 shadow-sm cursor-pointer rounded-2xl ring-offset-2 hover:ring-2 hover:ring-blue-500"
                        onclick="expandImage()">

                        <!-- Gambar Utama -->
                        <img id="d_image"
                            class="object-cover w-full h-full transition-all duration-500 group-hover:scale-110 group-hover:brightness-75">

                        <!-- Overlay Tengah (Rapi & Minimalis) -->
                        <div
                            class="absolute inset-0 z-10 flex flex-col items-center justify-center transition-all duration-300 opacity-0 pointer-events-none bg-black/40 group-hover:opacity-100">
                            <div class="p-2 mb-1 rounded-full shadow-inner bg-white/20 backdrop-blur-md">
                                <i class="text-lg text-white fa-solid fa-magnifying-glass-plus"></i>
                            </div>
                            <span class="text-[10px] font-bold text-white uppercase tracking-tighter shadow-sm">View
                                Full</span>
                        </div>

                        <!-- Label Kecil di Pojok Bawah (Indikator saat tidak hover) -->
                        <div
                            class="absolute flex items-center justify-center w-6 h-6 transition-opacity border border-gray-100 rounded-lg shadow-sm bottom-2 right-2 bg-white/90 backdrop-blur-sm group-hover:opacity-0">
                            <i class="fa-solid fa-expand text-blue-600 text-[10px]"></i>
                        </div>
                    </div>

                    <!-- Placeholder No Image (Dibuat senada dengan kontainer gambar) -->
                    <div id="no-image-placeholder"
                        class="flex-col items-center justify-center hidden w-32 h-32 text-gray-300 border-2 border-gray-200 border-dashed bg-gray-50 rounded-2xl">
                        <i class="mb-2 text-3xl opacity-50 fa-solid fa-image-slash"></i>
                        <span class="text-[10px] font-black uppercase tracking-widest opacity-60">No Image</span>
                    </div>
                </div>
                <div>
                    <h3 id="d_item_name" class="text-2xl font-bold text-gray-800"></h3>
                    <p id="d_type" class="font-mono text-gray-500"></p>
                    <span id="d_serial_number"
                        class="inline-block mt-1 px-2 py-0.5 bg-gray-200 text-gray-700 text-xs rounded font-bold"></span>
                    <span id="d_source_data"
                        class="inline-block mt-1 ml-2 px-2 py-0.5 bg-emerald-100 text-emerald-700 text-xs rounded font-bold"></span>
                </div>
            </div>
            <button onclick="closeDetailModal()" class="text-3xl text-gray-400 hover:text-red-500">&times;</button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2">
            <div class="p-6 border-r">
                <p class="flex items-center gap-2 mb-4 font-bold text-gray-700"><i
                        class="text-blue-600 fa-solid fa-layer-group"></i> Distribution Stock</p>
                <div class="overflow-hidden border rounded-lg">
                    <table class="w-full text-sm">
                        <thead class="text-gray-600 bg-gray-100">
                            <tr>
                                <th class="p-3 text-left">Site Location</th>
                                <th class="p-3 text-center">Qty</th>
                                <th class="p-3 text-center">Condition</th>
                            </tr>
                        </thead>
                        <tbody id="d_stock_table" class="divide-y"></tbody>
                    </table>
                </div>
            </div>
            <div class="p-6 bg-gray-50">
                <p class="flex items-center gap-2 mb-4 font-bold text-gray-700"><i
                        class="text-orange-600 fa-solid fa-clock-rotate-left"></i> Tracking History</p>
                <div class="relative pl-6 border-l-2 border-orange-200 space-y-6 max-h-[300px] overflow-y-auto"
                    id="d_history"></div>
            </div>
        </div>

        <div class="p-4 text-right bg-white border-t">
            <button onclick="closeDetailModal()"
                class="px-6 py-2 font-bold text-white transition-all bg-gray-800 rounded-lg hover:bg-black">Close
                Detail</button>
        </div>
    </div>
</div>

<!-- MODAL FULLSCREEN IMAGE VIEW -->
<div id="image-viewer"
    class="fixed inset-0 z-[100] hidden bg-black/90 backdrop-blur-md flex items-center justify-center p-4">
    <button onclick="closeImageViewer()"
        class="absolute z-10 p-2 text-white transition-all top-5 right-5 hover:scale-110">
        <i class="text-4xl fa-solid fa-xmark"></i>
    </button>
    <img id="full-image" src=""
        class="max-w-full max-h-full transition-all duration-300 transform scale-95 rounded-lg shadow-2xl"
        alt="Full Preview">
</div>

<!-- MODAL DELETE -->
<div id="modal-delete"
    class="fixed inset-0 z-[100] flex items-center justify-center hidden px-4 bg-slate-900/40 backdrop-blur-md transition-all">
    <div
        class="relative w-full max-w-sm transform transition-all duration-300 scale-95 opacity-0 modal-content bg-white shadow-2xl rounded-[32px] p-8 text-center">
        <div class="flex justify-center mb-6">
            <div class="flex items-center justify-center w-20 h-20 text-rose-600 bg-rose-50 rounded-3xl animate-pulse">
                <i class="text-3xl fa-solid fa-triangle-exclamation"></i>
            </div>
        </div>
        <h3 class="mb-2 text-xl font-black text-slate-800">Are you sure?</h3>
        <p class="mb-8 text-sm font-medium text-slate-500">You are about to delete <span id="delete-item-name"
                class="font-bold text-slate-800"></span>.</p>
        <form id="form-confirm-delete" method="POST">
            @csrf @method('DELETE')
            <div class="flex flex-col gap-3">
                <button type="submit"
                    class="w-full py-4 text-xs font-black tracking-widest text-white uppercase shadow-lg bg-rose-600 rounded-2xl hover:bg-rose-700">Yes,
                    Delete Permanently</button>
                <button type="button" onclick="closeDeleteModal()"
                    class="w-full py-4 text-xs font-black tracking-widest uppercase text-slate-400 bg-slate-50 rounded-2xl hover:bg-slate-100">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL MOVE (Transfer) -->
<div id="modal-move"
    class="fixed inset-0 z-[60] flex items-center justify-center hidden px-4 bg-black/60 backdrop-blur-sm">
    <div class="w-full max-w-md overflow-hidden bg-white border border-gray-100 shadow-2xl rounded-2xl">
        <div class="flex items-center gap-3 px-6 py-4 border-b border-orange-100 bg-orange-50">
            <div class="p-2 text-white bg-orange-500 rounded-lg"><i class="text-lg fa-solid fa-truck-fast"></i></div>
            <div>
                <h3 class="text-lg font-bold leading-tight text-gray-800">Transfer Request</h3>
                <p id="move-asset-tag" class="font-mono text-xs font-bold text-orange-600 uppercase"></p>
            </div>
        </div>
        <form id="form-move" method="POST" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block mb-1.5 text-[11px] font-bold text-gray-400 uppercase">Destination Site</label>
                <select name="to_site_id"
                    class="w-full px-4 py-3 text-sm font-bold border outline-none border-slate-200 bg-slate-50 rounded-xl focus:ring-2 focus:ring-emerald-500"
                    required>
                    <option value="" disabled selected>Select Destination</option>
                    @foreach ($all_sites as $s)
                        @if ($s->id !== $siteData->id)
                            <option value="{{ $s->id }}">{{ $s->machine_name }}
                                ({{ $s->branch->branch_name }})
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1.5 text-[11px] font-bold text-gray-400 uppercase">Condition</label>
                    <select name="condition" id="target-condition"
                        class="w-full px-4 py-3 text-sm font-bold border outline-none border-slate-200 bg-slate-50 rounded-xl"
                        required>
                        <option value="new">NEW</option>
                        <option value="used-good">USED GOOD</option>
                        <option value="damaged">DAMAGED</option>
                        <option value="repair">REPAIRED</option>
                    </select>
                </div>
                <div>
                    <label class="block mb-1.5 text-[11px] font-bold text-gray-400 uppercase">Quantity</label>
                    <input type="number" name="qty" id="move-quantity" min="1"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl outline-none text-sm font-bold"
                        required>
                </div>
            </div>
            <p id="max-info" class="text-[10px] font-bold text-right text-gray-400 italic mt-0"></p>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeMoveModal()"
                    class="flex-1 px-4 py-3 text-sm font-bold text-gray-500 bg-gray-100 rounded-xl">Cancel</button>
                <button type="submit"
                    class="flex-[2] px-4 py-3 text-sm font-bold text-white bg-orange-600 rounded-xl shadow-lg">Request
                    Transfer</button>
            </div>
        </form>
    </div>
</div>

<script>
    // --- Detail Modal Functions ---
    function openDetailModal(item, sites) {
        document.getElementById('d_item_name').innerText = item.item_name;
        document.getElementById('d_type').innerText = "Type: " + (item.type || '-');
        document.getElementById('d_serial_number').innerText = "SN: " + (item.serial_number || '-');
        document.getElementById('d_source_data').innerText = "Source: " + (item.source_data || 'Manual Input');

        // Bagian dalam fungsi openDetailModal
        const imgElement = document.getElementById('d_image');
        const imgContainer = document.getElementById('d_image_container');
        const placeholder = document.getElementById('no-image-placeholder');

        if (item.image) {
            imgElement.src = `/storage/${item.image}`;

            // PASTIKAN INI: Tampilkan kontainer, dan pastikan img itu sendiri tidak hidden
            imgContainer.classList.remove('hidden');
            imgElement.classList.remove('hidden'); // Tambahkan ini agar img tidak tertinggal hidden

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
                <tr class="transition-colors hover:bg-blue-50">
                    <td class="p-3 font-medium text-gray-700">${s.site.machine_name}</td>
                    <td class="p-3 font-bold text-center text-blue-600">${s.qty}</td>
                    <td class="p-3 text-center"><span class="text-[10px] px-2 py-0.5 rounded-full bg-gray-200 font-bold uppercase">${s.condition}</span></td>
                </tr>`;
            });
        } else {
            stockTable.innerHTML =
                '<tr><td colspan="3" class="p-4 italic text-center text-gray-400">No active stock</td></tr>';
        }

        const historyContainer = document.getElementById('d_history');
        historyContainer.innerHTML = (item.histories && item.histories.length > 0) ? '' :
            '<p class="text-sm italic text-gray-400">No history records found.</p>';
        if (item.histories) {
            item.histories.forEach(h => {
                const date = new Date(h.created_at).toLocaleString('id-ID', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                historyContainer.innerHTML += `
                <div class="relative">
                    <div class="absolute -left-[31px] mt-1.5 w-4 h-4 rounded-full bg-orange-500 border-4 border-white"></div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase">${date}</p>
                    <p class="text-sm font-bold text-gray-800">${h.action}</p>
                    <p class="text-xs text-gray-600">${h.from_site?.machine_name || 'Initial'} ➔ ${h.to_site?.machine_name || 'Unknown'}</p>
                    <p class="text-[10px] italic text-gray-500">Qty: ${h.qty} | ${h.condition}</p>
                    <p class="text-[10px] italic text-gray-500">Note: ${h.note}</p>

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

    // --- Image Viewer Functions ---
    function expandImage() {
        const imgElement = document.getElementById('d_image');
        // Pastikan kita tidak memperbesar jika yang tampil adalah placeholder
        if (imgElement.classList.contains('hidden')) return;

        const imgSrc = imgElement.src;
        const viewer = document.getElementById('image-viewer');
        const fullImg = document.getElementById('full-image');

        fullImg.src = imgSrc;
        viewer.classList.remove('hidden');
        viewer.classList.add('flex');

        // Animasi zoom in saat modal terbuka
        setTimeout(() => {
            fullImg.classList.replace('scale-95', 'scale-100');
        }, 10);
    }

    function closeImageViewer() {
        const viewer = document.getElementById('image-viewer');
        const fullImg = document.getElementById('full-image');
        fullImg.classList.replace('scale-100', 'scale-95');
        setTimeout(() => viewer.classList.add('hidden'), 200);
    }

    // --- Delete Modal Functions ---
    function openDeleteModal(url, itemName) {
        document.getElementById('form-confirm-delete').action = url;
        document.getElementById('delete-item-name').innerText = itemName;
        const modal = document.getElementById('modal-delete');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.querySelector('.modal-content').classList.replace('scale-95', 'scale-100');
            modal.querySelector('.modal-content').classList.replace('opacity-0', 'opacity-100');
        }, 10);
    }

    function closeDeleteModal() {
        const modal = document.getElementById('modal-delete');
        modal.querySelector('.modal-content').classList.replace('scale-100', 'scale-95');
        modal.querySelector('.modal-content').classList.replace('opacity-100', 'opacity-0');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    // --- Move Modal Functions ---
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
    }

    function closeMoveModal() {
        document.getElementById('modal-move').classList.add('hidden');
    }

    // Close modals on outside click
    window.onclick = function(event) {
        if (event.target.id === 'detailModal') closeDetailModal();
        if (event.target.id === 'modal-delete') closeDeleteModal();
        if (event.target.id === 'modal-move') closeMoveModal();
        if (event.target.id === 'image-viewer') closeImageViewer();
    }
</script>
