<table class="w-full border-collapse">
    <thead class="text-gray-700 bg-gray-100">
        <tr>
            <th class="px-4 py-3 text-center">No</th>
            <th class="px-4 py-3 text-center">Item Name</th>
            <th class="px-4 py-3 text-center">Serial Number</th>
            <th class="px-4 py-3 text-center">Total Qty</th>
            {{-- <th class="px-4 py-3 text-center">UOM</th> --}}
            <th class="px-4 py-3 text-center">Conditions</th>
            @if (Auth::user()->role === 'admin')
                <th class="px-4 py-3 text-center">Action</th>
            @endif
        </tr>
    </thead>

    <tbody>
        @forelse ($assets as $item)
            <tr class="border-b hover:bg-gray-50">
                <td class="px-4 py-3 text-center">
                    {{ ($assets->currentPage() - 1) * $assets->perPage() + $loop->iteration }}
                </td>
                <td class="px-4 py-3 font-bold text-center">{{ $item->item_name }}</td>
                <td class="px-4 py-3 text-center">{{ $item->serial_number }}</td>
                <td class="px-4 py-3 text-center">
                    <div
                        class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-50 border border-blue-100 rounded-full">
                        <span class="font-bold text-blue-700">
                            {{ $item->total_qty }}
                        </span>
                        <span class="text-[10px] font-bold text-blue-400 uppercase tracking-wider">
                            {{ $item->uom }}
                        </span>
                    </div>
                </td>
                <td class="px-4 py-3 text-xs text-center">
                    @foreach ($item->stocks as $stock)
                        <div class="mb-1">
                            <span class="font-semibold uppercase">{{ $stock->condition }}:</span> {{ $stock->qty }}
                        </div>
                    @endforeach
                </td>

                @if (Auth::user()->role === 'admin')
                    <td class="px-4 py-3 text-center">
                        <div class="flex justify-center gap-2">
                            <button
                                onclick='openDetailModal(@json($item), @json($sites))'
                                class="px-3 py-1 text-xs text-white bg-gray-600 rounded hover:bg-gray-700">
                                DETAIL
                            </button>

                            @if (Auth::user()->role === 'admin')
                                <button
                                    onclick="openMoveModal({{ $item->id }}, '{{ $item->item_name }}', {{ $item->total_qty }})"
                                    class="px-3 py-1 text-xs text-white bg-orange-500 rounded hover:bg-orange-600">
                                    MOVE
                                </button>
                            @endif
                        </div>
                    </td>
                @endif
            </tr>
        @empty
            <tr>
                <td colspan="7" class="p-4 text-center text-gray-500">Data sparepart tidak ditemukan di site ini.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="p-4">
    {{ $assets->links() }}
</div>

<div id="modal-move" class="fixed inset-0 z-50 flex items-center justify-center hidden px-4 bg-black bg-opacity-50">
    <div class="w-full max-w-md p-6 bg-white rounded-lg shadow-2xl">
        <h3 class="mb-2 text-xl font-bold text-gray-800">Pindahkan Barang</h3>
        <div id="move-asset-tag"
            class="p-3 mb-6 font-mono text-sm font-bold text-blue-600 border border-blue-100 rounded bg-blue-50"></div>

        <form id="form-move" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block mb-1 text-sm font-medium text-gray-700">Tujuan Site:</label>
                <select name="to_site_id"
                    class="w-full p-2 text-sm border rounded outline-none focus:ring-2 focus:ring-orange-500" required>
                    <option value="">-- Pilih Lokasi Baru --</option>
                    @foreach ($all_sites as $s)
                        <option value="{{ $s->id }}">{{ $s->machine_name }} ({{ $s->branch->branch_name }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="block mb-1 text-sm font-medium text-gray-700">Kondisi Saat Ini:</label>
                <select name="condition"
                    class="w-full p-2 text-sm border rounded outline-none focus:ring-2 focus:ring-orange-500" required>
                    <option value="new">NEW (Baru/Gres)</option>
                    <option value="used-good">USED (Pernah Terpakai)</option>
                    <option value="damaged">BROKEN (Rusak)</option>
                    <option value="repaired">REFURBISHED (Hasil Perbaikan)</option>
                </select>
                <p class="text-[10px] text-gray-400 mt-1">*Pilih kondisi terkini barang sebelum dipindahkan</p>
            </div>

            <div class="mb-4">
                <label class="block mb-1 text-sm font-medium text-gray-700">Jumlah yang Dipindah:</label>
                <input type="number" name="qty" id="move-quantity" min="1" value="1"
                    class="w-full p-2 text-sm border rounded outline-none focus:ring-2 focus:ring-orange-500" required>
                <p id="max-info" class="text-[10px] text-gray-500 mt-1"></p>
            </div>
            <div class="mb-6">
                <label class="block mb-1 text-sm font-medium text-gray-700">Catatan Pemindahan:</label>
                <textarea name="note" class="w-full h-24 p-2 text-sm border rounded outline-none focus:ring-2 focus:ring-orange-500"
                    placeholder="Alasan pemindahan..."></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeMoveModal()"
                    class="px-5 py-2 text-sm font-semibold text-gray-700 bg-gray-200 rounded hover:bg-gray-300">Batal</button>
                <button type="submit"
                    class="px-5 py-2 text-sm font-bold text-white bg-orange-600 rounded shadow-md hover:bg-orange-700">Konfirmasi
                    Pindah</button>
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
    function openMoveModal(id, tag, currentQty) {
        const modal = document.getElementById('modal-move');
        const qtyInput = document.getElementById('move-quantity');
        const maxInfo = document.getElementById('max-info');

        document.getElementById('move-asset-tag').innerText = "TRANSFER TAG: " + tag;
        document.getElementById('form-move').action = "/movement/move/" + id;

        // KUNCI UTAMA: Set atribut max pada input number
        qtyInput.max = currentQty;
        qtyInput.value = 1; // Default pindah 1
        maxInfo.innerText = "* Stok tersedia: " + currentQty + " pcs";
        maxInfo.classList.remove('text-red-500');

        modal.classList.remove('hidden');
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
</script>
