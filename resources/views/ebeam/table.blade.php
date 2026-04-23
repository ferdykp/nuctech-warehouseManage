{{-- resources/views/ebeam/table.blade.php --}}
<table class="w-full border-collapse">
    <thead class="text-gray-700 bg-gray-100">
        <tr>
            @if (Auth::user()->role === 'superadmin')
                <th class="px-4 py-3 text-center">
                    <input type="checkbox" id="select_all_id">
                </th>
            @endif
            <th class="px-4 py-3 text-center">No</th>
            <th class="px-4 py-3 text-center">Item Name</th>
            <th class="px-4 py-3 text-center">Type</th>
            <th class="px-4 py-3 text-center">Stock</th>
            <th class="px-4 py-3 text-center">UOM</th>
            <th class="px-4 py-3 text-center">Condition</th>
            @if (Auth::user()->role === 'superadmin')
                <th class="px-4 py-3 text-center">Action</th>
            @endif
        </tr>
    </thead>

    <tbody>
        @forelse ($data as $item)
            <tr class="border-b hover:bg-gray-50">
                @if (Auth::user()->role === 'superadmin')
                    <td class="px-4 py-3 text-center">
                        <input type="checkbox" class="checkbox-id" value="{{ $item->id }}">
                    </td>
                @endif
                <td class="px-4 py-3 text-center">
                    {{ $loop->iteration + ($data->currentPage() - 1) * $data->perPage() }}
                </td>
                <td class="px-4 py-3 text-center">{{ $item->item_name }}</td>
                <td class="px-4 py-3 text-center">{{ $item->type }}</td>
                <td class="px-4 py-3 text-center">
                    {{ $item->total_qty ?? 0 }}
                </td>

                <td class="px-4 py-3 text-center">{{ $item->uom }}</td>
                <td class="px-4 py-3 text-center">
                    @php
                        $condition = optional($item->stocks->first())->condition;
                    @endphp

                    <span
                        class="px-2 py-1 rounded text-sm
    {{ $condition === 'new' ? 'bg-green-200' : ($condition ? 'bg-red-200' : 'bg-gray-200') }}">
                        {{ $condition ? ucfirst($condition) : '-' }}
                    </span>
                    {{-- <span
                        class="px-2 py-1 rounded text-sm
    {{ match ($condition) {
        'new' => 'bg-green-200 text-green-800',
        'used-good' => 'bg-blue-200 text-blue-800',
        'damaged' => 'bg-red-200 text-red-800',
        'repair' => 'bg-yellow-200 text-yellow-800',
        default => 'bg-gray-200 text-gray-700',
    } }}">
                        {{ str_replace('-', ' ', ucfirst($condition ?? '-')) }}
                    </span> --}}


                </td>
                @if (Auth::user()->role === 'superadmin')
                    <td class="px-4 py-3 text-center">
                        <div class="flex justify-center gap-2">
                            {{-- <button onclick='openDetailModal(@json($item))'
                                class="px-3 py-2 font-semibold text-white bg-gray-600 rounded-lg text-md hover:bg-gray-700">
                                Detail
                            </button> --}}
                            <button
                                onclick='openDetailModal(@json($item), @json($sites))'
                                class="px-3 py-2 text-white bg-gray-600 rounded-lg">
                                Detail
                            </button>


                            <a href="/{{ $site }}/{{ $item->id }}/edit"
                                class="px-3 py-2 font-semibold text-white bg-blue-600 rounded-lg text-md hover:bg-blue-700">
                                Edit
                            </a>
                            {{-- Delete --}}
                            <div x-data="{ open: false }">
                                <button @click="open = true"
                                    class="px-3 py-2 font-semibold text-white bg-red-600 rounded-lg text-md hover:bg-red-700">
                                    Delete
                                </button>
                                <div x-show="open" x-cloak x-transition.opacity
                                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                                    <div @click.outside="open = false" x-transition
                                        class="w-full max-w-md p-6 bg-white shadow-2xl rounded-2xl">
                                        <h3 class="mb-2 text-lg font-semibold text-gray-800">Konfirmasi Hapus</h3>
                                        <p class="mb-6 text-sm text-gray-600">
                                            Apakah kamu yakin ingin menghapus data ini?
                                            <span class="font-semibold text-red-600">Tindakan ini tidak bisa
                                                dibatalkan.</span>
                                        </p>
                                        <div class="flex justify-end gap-3">
                                            <button @click="open = false"
                                                class="px-4 py-2 text-sm font-semibold text-gray-600 bg-gray-200 rounded-lg hover:bg-gray-300">
                                                Batal
                                            </button>
                                            <form action="/{{ $site }}/{{ $item->id }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="px-4 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700">
                                                    Ya, Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                @endif
            </tr>
        @empty
            <tr>
                <td colspan="8" class="p-4 text-center text-gray-500">Data kosong</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="p-4">
    {{ $data->links() }}
</div>

<div id="detailModal" class="fixed inset-0 z-50 items-center justify-center hidden bg-black/50">

    <div id="modalWrapper"
        class="w-full max-w-4xl transition transform scale-95 bg-white shadow-xl opacity-0 rounded-2xl">

        {{-- HEADER --}}
        <div class="flex items-center justify-between p-3 border-b">
            <div class="flex items-center gap-4">
                <img id="d_image" class="object-contain bg-gray-100 rounded h-36 w-36">
                <div>
                    <p id="d_item_name" class="text-lg font-semibold"></p>
                    <p id="d_type" class="text-sm text-gray-500"></p>
                </div>
            </div>
            {{-- <button onclick="closeDetailModal()" class="text-2xl">&times;</button> --}}
        </div>

        {{-- MOVE FORM --}}
        <div class="p-6 border-b">
            <form id="moveForm" method="POST" class="flex flex-wrap items-center gap-3">
                @csrf

                <input type="hidden" name="condition" id="move_condition">

                <input type="hidden" name="sparepart_id" id="move_sparepart_id">
                <input type="hidden" name="from_site_id" id="move_from_site_id">

                <label class="text-sm text-gray-500">Move to</label>

                <select name="to_site_id" id="move_to_site_id" class="px-2 py-1 border rounded"></select>

                <input type="number" name="qty" min="1" value="1"
                    class="w-20 px-2 py-1 border rounded">

                <input type="text" name="note" class="px-3 py-1 border rounded" placeholder="Catatan">

                <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700">
                    Move
                </button>
            </form>
        </div>

        {{-- STOCK TABLE --}}
        <div class="p-6 border-b">
            <p class="mb-2 font-semibold">Current Stock</p>
            <table class="w-full text-sm border">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-2 text-left">Site</th>
                        <th class="p-2 text-center">Stock</th>
                        <th class="p-2 text-center">Condition</th>
                    </tr>
                </thead>
                <tbody id="d_stock_table"></tbody>
            </table>
        </div>

        {{-- HISTORY --}}
        <div class="p-6">
            <p class="mb-2 font-semibold">Tracking History</p>
            <ul id="d_history" class="pl-4 space-y-4 overflow-y-auto border-l max-h-64"></ul>
        </div>

        <div class="p-6 text-right border-t">
            <button onclick="closeDetailModal()" class="px-5 py-2 text-white bg-gray-700 rounded">
                Close
            </button>
        </div>
    </div>
</div>
<script>
    function openDetailModal(item, sites) {

        // AMBIL STOCK VALID (HARUS PALING ATAS)
        // const validStock = item.stocks.find(s => s.qty > 0);
        // if (!validStock) {
        //     alert('Stock kosong');
        //     return;
        // }
        if (!item.stocks || item.stocks.length === 0) {
            alert('Stock tidak tersedia');
            return;
        }

        const validStock = item.stocks.find(s => s.qty > 0) ?? item.stocks[0];


        // SET BASIC INFO
        document.getElementById('d_item_name').innerText = item.item_name;
        document.getElementById('d_type').innerText = item.type;
        document.getElementById('d_image').src =
            item.image ? `/storage/${item.image}` : '/no-image.png';

        // FORM ACTION
        document.getElementById('moveForm').action =
            `/${window.location.pathname.split('/')[1]}/sparepart/${item.id}/move`;

        document.getElementById('move_sparepart_id').value = item.id;
        document.getElementById('move_condition').value = validStock.condition;
        document.getElementById('move_from_site_id').value = validStock.site_id;

        // TO SITE OPTIONS
        const toSelect = document.getElementById('move_to_site_id');
        toSelect.innerHTML = '';

        sites.forEach(site => {
            const opt = document.createElement('option');
            opt.value = site.id;
            opt.text = site.name;

            if (site.id === validStock.site_id) {
                opt.selected = true;
            }

            toSelect.appendChild(opt);
        });




        // STOCK TABLE
        const stockTable = document.getElementById('d_stock_table');
        stockTable.innerHTML = '';
        item.stocks.forEach(s => {
            stockTable.innerHTML += `
        <tr>
            <td class="p-2">${s.site.name}</td>
            <td class="p-2 text-center">${s.qty}</td>
            <td class="p-2 text-center">${s.condition.toUpperCase()}</td>
        </tr>`;
        });

        // HISTORY
        const history = document.getElementById('d_history');
        history.innerHTML = '';
        item.histories.forEach(h => {
            history.innerHTML += `
        <li>
            <p class="text-xs text-gray-500">${new Date(h.created_at).toLocaleString()}</p>
            <p class="text-sm font-semibold">
                ${h.action.toUpperCase()}
                ${h.from_site ? 'from ' + h.from_site.name : ''}
                ${h.to_site ? 'to ' + h.to_site.name : ''}
                (${h.qty})
            </p>
            ${h.note ? `<p class="text-xs">${h.note}</p>` : ''}
        </li>`;
        });

        // SHOW MODAL
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
