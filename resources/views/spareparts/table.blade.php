<div class="overflow-x-auto bg-white border border-gray-100 shadow-sm rounded-xl">
    <table class="w-full border-collapse">
        <thead>
            <tr
                class="text-xs font-semibold tracking-wider text-gray-500 uppercase border-b border-gray-100 bg-gray-50/50">
                <th class="px-6 py-4 text-center">No</th>
                <th class="px-6 py-4 text-left">Item Information</th>
                <th class="px-6 py-4 text-center">Serial Number</th>
                <th class="px-6 py-4 text-center">Availability (Site)</th>
                <th class="px-6 py-4 text-left">Condition Breakdowns</th>
                @if (Auth::user()->role === 'superadmin' ||
                        (Auth::user()->role === 'admin_site' && Auth::user()->site_id === $siteData->id))
                    <th class="px-6 py-4 text-right">Actions</th>
                @endif
            </tr>
        </thead>

        <tbody class="divide-y divide-gray-50">
            @forelse ($assets as $item)
                @php
                    // FILTER: Hanya ambil stok untuk site yang sedang dibuka
                    $siteStocks = $item->stocks->where('site_id', $siteData->id);
                    $totalQtyAtSite = $siteStocks->sum('qty');
                @endphp
                <tr class="transition-colors hover:bg-blue-50/30 group">
                    <td class="px-6 py-4 text-sm font-medium text-center text-gray-400">
                        {{ ($assets->currentPage() - 1) * $assets->perPage() + $loop->iteration }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col">
                            <span
                                class="font-bold text-gray-800 transition-colors group-hover:text-blue-600">{{ $item->item_name }}</span>
                            @if ($item->type && strtolower(trim($item->type)) !== strtolower(trim($item->item_name)))
                                <span
                                    class="text-[11px] text-gray-500 font-medium uppercase tracking-tight">{{ $item->type }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        @php
                            $sn = trim($item->serial_number);
                            $isDuplicate =
                                strtolower($sn) === strtolower(trim($item->type ?? '')) ||
                                strtolower($sn) === strtolower(trim($item->item_name));
                        @endphp
                        @if ($sn && !$isDuplicate)
                            <span
                                class="px-2 py-1 font-mono text-xs text-gray-600 bg-gray-100 border border-gray-200 rounded">{{ $sn }}</span>
                        @else
                            <span class="text-xs italic text-gray-300">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div
                            class="inline-flex flex-col items-center justify-center min-w-[60px] py-1 bg-white border-2 border-blue-100 rounded-lg shadow-sm">
                            <span class="text-lg font-black leading-none text-blue-700">{{ $totalQtyAtSite }}</span>
                            <span class="text-[9px] font-bold text-blue-400 uppercase mt-1">{{ $item->uom }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-wrap gap-1.5">
                            @forelse ($siteStocks as $stock)
                                @php
                                    $colorMap = [
                                        'new' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
                                        'used-good' => 'bg-blue-50 text-blue-600 border-blue-100',
                                        'damaged' => 'bg-red-50 text-red-600 border-red-100',
                                        'repaired' => 'bg-amber-50 text-amber-600 border-amber-100',
                                    ];
                                    $style = $colorMap[$stock->condition] ?? 'bg-gray-50 text-gray-600 border-gray-100';
                                @endphp
                                <span
                                    class="px-2 py-0.5 text-[10px] font-bold uppercase border rounded-md {{ $style }}">
                                    {{ str_replace('-', ' ', $stock->condition) }}: {{ $stock->qty }}
                                </span>
                            @empty
                                <span class="text-[10px] italic text-gray-400">Empty at this site</span>
                            @endforelse
                        </div>
                    </td>

                    @if (Auth::user()->role === 'superadmin' ||
                            (Auth::user()->role === 'admin_site' && Auth::user()->site_id === $siteData->id))
                        {{-- Ganti bagian Actions pada resources/views/spareparts/table.blade.php --}}

                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                {{-- Tombol View Detail --}}
                                <button
                                    onclick='openDetailModal(@json($item), @json($all_sites))'
                                    class="p-2 text-gray-400 transition-all rounded-lg hover:text-blue-600 hover:bg-blue-50"
                                    title="View Details">
                                    <i class="text-lg fa-solid fa-eye"></i>
                                </button>

                                {{-- Tombol Request Move Tunggal --}}
                                @php
                                    // Mengambil baris stok pertama yang tersedia di site ini untuk inisialisasi modal
                                    $firstAvailableStock = $siteStocks->first();
                                @endphp

                                @if ($firstAvailableStock)
                                    <button
                                        onclick="openMoveModal({{ $firstAvailableStock->id }}, '{{ $item->item_name }}', {{ $firstAvailableStock->qty }}, '{{ $firstAvailableStock->condition }}')"
                                        class="flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-bold text-white bg-orange-500 hover:bg-orange-600 rounded-lg shadow-sm transition-all shadow-orange-100 uppercase tracking-tighter">
                                        <i class="fa-solid fa-truck-fast"></i>
                                        Request Move
                                    </button>
                                @endif
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-20 text-center">
                        <div class="flex flex-col items-center justify-center text-gray-300">
                            <i class="mb-4 text-5xl fa-solid fa-box-open"></i>
                            <span class="text-sm italic font-medium">Data sparepart tidak ditemukan di site ini.</span>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">
    {{ $assets->links() }}
</div>

{{-- MODAL MOVE --}}
<div id="modal-move"
    class="fixed inset-0 z-[60] flex items-center justify-center hidden px-4 bg-black/60 backdrop-blur-sm">
    <div class="w-full max-w-md overflow-hidden bg-white border border-gray-100 shadow-2xl rounded-2xl">
        <div class="flex items-center gap-3 px-6 py-4 border-b border-orange-100 bg-orange-50">
            <div class="p-2 text-white bg-orange-500 rounded-lg">
                <i class="text-lg fa-solid fa-truck-fast"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold leading-tight text-gray-800">Transfer Request</h3>
                <p id="move-asset-tag" class="font-mono text-xs font-bold text-orange-600 uppercase"></p>
            </div>
        </div>

        <form id="form-move" method="POST" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block mb-1.5 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Destination
                    Site</label>
                <select name="to_site_id"
                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition-all outline-none text-sm font-medium"
                    required>
                    <option value="">Select Destination</option>
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
                    <label class="block mb-1.5 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Target
                        Condition</label>
                    <select name="condition" id="target-condition"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition-all outline-none text-sm font-medium"
                        required>
                        <option value="new">NEW</option>
                        <option value="used-good">USED GOOD</option>
                        <option value="damaged">DAMAGED</option>
                        <option value="repaired">REPAIRED</option>
                    </select>
                </div>
                <div>
                    <label
                        class="block mb-1.5 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Quantity</label>
                    <input type="number" name="qty" id="move-quantity" min="1" value="1"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition-all outline-none text-sm font-bold"
                        required>
                </div>
            </div>
            <p id="max-info" class="text-[10px] font-bold text-right text-gray-400 italic mt-0"></p>

            <div>
                <label class="block mb-1.5 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Note</label>
                <textarea name="note"
                    class="w-full h-24 px-4 py-3 text-sm transition-all border border-gray-200 outline-none bg-gray-50 rounded-xl focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 placeholder:text-gray-300"
                    placeholder="Reason for transfer..."></textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeMoveModal()"
                    class="flex-1 px-4 py-3 text-sm font-bold text-gray-500 transition-colors bg-gray-100 hover:bg-gray-200 rounded-xl">Cancel</button>
                <button type="submit"
                    class="flex-[2] px-4 py-3 text-sm font-bold text-white bg-orange-600 hover:bg-orange-700 rounded-xl shadow-lg shadow-orange-200 transition-all">Request
                    Transfer</button>
            </div>
        </form>
    </div>
</div>
{{-- ASSET DETAIL MODAL --}}
<div id="asset-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden px-4 bg-black/60">

    <div class="relative w-full max-w-5xl max-h-[90vh] overflow-y-auto bg-white rounded-2xl shadow-2xl">

        <button onclick="closeAssetModal()" class="absolute text-2xl text-gray-500 top-3 right-4 hover:text-red-500">
            ✕
        </button>

        <div id="asset-modal-content" class="p-6">
            {{-- nanti detail.blade.php masuk ke sini --}}
            <div class="py-20 text-center text-gray-400">
                Loading asset detail...
            </div>
        </div>

    </div>
</div>
<script>
    function openAssetDetail(tag) {
        const modal = document.getElementById('asset-modal');
        const content = document.getElementById('asset-modal-content');

        // tampilkan modal dulu
        modal.classList.remove('hidden');

        // loading state
        content.innerHTML = `
        <div class="py-20 text-center">
            <div class="text-lg font-semibold text-gray-500">Loading asset detail...</div>
        </div>
    `;

        // fetch ke laravel
        fetch(`{{ url('/assets/track') }}/${tag}`)
            .then(response => response.text())
            .then(html => {
                content.innerHTML = html;
            })
            .catch(() => {
                content.innerHTML = `<div class="text-center text-red-500">Gagal mengambil data</div>`;
            });
    }

    function closeAssetModal() {
        document.getElementById('asset-modal').classList.add('hidden');
    }

    // klik area gelap untuk tutup
    window.addEventListener('click', function(e) {
        const modal = document.getElementById('asset-modal');
        if (e.target === modal) closeAssetModal();
    });
</script>


<script>
    function openMoveModal(stockId, itemName, currentQty, currentCondition) {
        const modal = document.getElementById('modal-move');
        const qtyInput = document.getElementById('move-quantity');
        const maxInfo = document.getElementById('max-info');
        const targetCond = document.getElementById('target-condition');

        document.getElementById('move-asset-tag').innerText = itemName + " (" + currentCondition.toUpperCase() + ")";
        document.getElementById('form-move').action = "/movement/request/" + stockId;

        // Set default target condition sama dengan asal
        targetCond.value = currentCondition;

        qtyInput.max = currentQty;
        qtyInput.value = 1;
        maxInfo.innerText = "* Available at source: " + currentQty + " pcs";
        maxInfo.classList.remove('text-red-500');

        modal.classList.remove('hidden');
    }

    function closeMoveModal() {
        document.getElementById('modal-move').classList.add('hidden');
    }

    // Tambahan: Validasi saat user mengetik manual
    document.getElementById('move-quantity').addEventListener('input', function() {
        const max = parseInt(this.max);
        const val = parseInt(this.value);
        const info = document.getElementById('max-info');

        if (val > max) {
            info.innerText = "⚠️ Angka melebihi stok (" + max + ")!";
            info.classList.add('text-red-500');
            this.value = max; // Paksa kembali ke maksimal
        } else {
            info.innerText = "* Stok tersedia: " + max + " pcs";
            info.classList.remove('text-red-500');
        }
    });

    // Menutup modal jika area di luar kotak putih di-klik
    window.onclick = function(event) {
        const modal = document.getElementById('modal-move');
        if (event.target == modal) {
            closeMoveModal();
        }
    }
</script>

<div id="detailModal" class="fixed inset-0 z-50 items-center justify-center hidden bg-black/50 backdrop-blur-sm">
    <div id="modalWrapper"
        class="w-full max-w-4xl overflow-hidden transition transform scale-95 bg-white shadow-2xl opacity-0 rounded-2xl">

        {{-- HEADER --}}
        <div class="flex items-center justify-between p-6 border-b bg-gray-50">
            <div class="flex items-center gap-6">
                <img id="d_image" class="object-cover w-32 h-32 bg-white border rounded-lg shadow-sm">
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
            {{-- STOCK TABLE (LEFT) --}}
            <div class="p-6 border-r">
                <p class="flex items-center gap-2 mb-4 font-bold text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-blue-600" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path
                            d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z" />
                    </svg>
                    Distribution Stock
                </p>
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

            {{-- HISTORY TRACKING (RIGHT) --}}
            <div class="p-6 bg-gray-50">
                <p class="flex items-center gap-2 mb-4 font-bold text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-orange-600" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                            clip-rule="evenodd" />
                    </svg>
                    Tracking History
                </p>
                <div class="relative pl-6 border-l-2 border-orange-200 space-y-6 max-h-[300px] overflow-y-auto"
                    id="d_history">
                </div>
            </div>
        </div>

        <div class="p-4 text-right bg-white border-t">
            <button onclick="closeDetailModal()"
                class="px-6 py-2 font-bold text-white transition-all bg-gray-800 rounded-lg hover:bg-black">
                Close Detail
            </button>
        </div>
    </div>
</div>


<script>
    function openDetailModal(item, sites) {
        // Basic Info
        document.getElementById('d_item_name').innerText = item.item_name;
        document.getElementById('d_type').innerText = "Type: " + item.type;
        document.getElementById('d_serial_number').innerText = "SN: " + item.serial_number;
        document.getElementById('d_source_data').innerText = "Source: " + (item.source_data || 'Manual Input');
        document.getElementById('d_image').src = item.image ? `/storage/${item.image}` : '/no-image.png';

        // STOCK TABLE
        const stockTable = document.getElementById('d_stock_table');
        stockTable.innerHTML = '';

        if (item.stocks && item.stocks.length > 0) {
            item.stocks.forEach(s => {
                stockTable.innerHTML += `
                <tr class="transition-colors hover:bg-blue-50">
                    <td class="p-3 font-medium text-gray-700">${s.site.machine_name}</td>
                    <td class="p-3 font-bold text-center text-blue-600">${s.qty}</td>
                    <td class="p-3 text-center">
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-gray-200 font-bold uppercase">${s.condition}</span>
                    </td>
                </tr>`;
            });
        } else {
            stockTable.innerHTML =
                '<tr><td colspan="3" class="p-4 italic text-center text-gray-400">No active stock</td></tr>';
        }

        // HISTORY
        const historyContainer = document.getElementById('d_history');
        historyContainer.innerHTML = '';

        if (item.histories && item.histories.length > 0) {
            item.histories.forEach(h => {
                const date = new Date(h.created_at).toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                const fromSite = h.from_site ? h.from_site.machine_name : 'Initial';
                const toSite = h.to_site ? h.to_site.machine_name : 'Unknown';

                historyContainer.innerHTML += `
                <div class="relative">
                    <div class="absolute -left-[31px] mt-1.5 w-4 h-4 rounded-full bg-orange-500 border-4 border-white shadow-sm"></div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">${date}</p>
                    <p class="text-sm font-bold text-gray-800">${h.action}</p>
                    <p class="text-xs text-gray-600">
                        <span class="font-semibold text-blue-600">${fromSite}</span> 
                        <span class="mx-1 text-gray-400">➔</span> 
                        <span class="font-semibold text-green-600">${toSite}</span>
                    </p>
                    <p class="mt-1 font-mono text-xs italic text-gray-500">Qty: ${h.qty} | Cond: ${h.condition}</p>
                    ${h.note ? `<p class="p-1 mt-1 text-xs italic text-gray-500 bg-white border rounded">"${h.note}"</p>` : ''}
                </div>`;
            });
        } else {
            historyContainer.innerHTML = '<p class="text-sm italic text-gray-400">No history records found.</p>';
        }

        // SHOW MODAL ANIMATION
        const modal = document.getElementById('detailModal');
        const wrapper = document.getElementById('modalWrapper');

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            wrapper.classList.remove('scale-95', 'opacity-0');
            wrapper.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeDetailModal() {
        const modal = document.getElementById('detailModal');
        const wrapper = document.getElementById('modalWrapper');

        wrapper.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 200);
    }

    function openMoveModal(stockId, itemName, currentQty) {
        const modal = document.getElementById('modal-move');
        const qtyInput = document.getElementById('move-quantity');
        const maxInfo = document.getElementById('max-info');

        document.getElementById('move-asset-tag').innerText = "REQUEST TRANSFER: " + itemName;

        // Sesuaikan URL ini dengan route requestMove Anda
        document.getElementById('form-move').action = "/movement/request/" + stockId;

        qtyInput.max = currentQty;
        qtyInput.value = 1;
        maxInfo.innerText = "* Stok tersedia di site ini: " + currentQty + " pcs";

        modal.classList.remove('hidden');
    }
</script>
