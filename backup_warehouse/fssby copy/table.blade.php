{{-- TABLE BODY --}}
@forelse ($data as $index => $item)
    <tr class="border-b hover:bg-gray-50">

        @if (Auth::user()->role === 'admin')
            <td class="px-4 py-3 text-center">
                <input type="checkbox" class="w-4 h-4 checkbox_id" value="{{ $item->id }}">
            </td>
        @endif

        {{-- NO --}}
        <td class="px-4 py-3 text-center">
            {{ $index + 1 + ($data->currentPage() - 1) * $data->perPage() }}
        </td>

        {{-- ITEM NAME --}}
        <td class="px-4 py-3 text-center">
            {{ $item->item_name }}
        </td>

        {{-- TYPE --}}
        <td class="px-4 py-3 text-center">
            {{ $item->type }}
        </td>

        {{-- STOCK --}}
        <td class="px-4 py-3 text-center">
            {{ $item->stock }} {{ strtoupper($item->uom) }}
        </td>

        {{-- ACTION --}}
        <td class="px-4 py-3">
            <div class="flex justify-center gap-2">

                {{-- DETAIL --}}
                <button onclick='openDetailModal(@json($item))'
                    class="px-3 py-2 font-semibold text-white bg-gray-600 rounded-lg text-md hover:bg-gray-700">
                    Detail
                </button>

                @if (Auth::user()->role === 'admin')
                    <a href="{{ route('fssby.edit', $item->id) }}"
                        class="px-3 py-2 font-semibold text-white bg-blue-600 rounded-lg text-md hover:bg-blue-700">
                        Edit
                    </a>

                    <div x-data="{ open: false }">
                        <button @click="open = true"
                            class="px-3 py-2 font-semibold text-white bg-red-600 rounded-lg text-md hover:bg-red-700">
                            Delete
                        </button>

                        {{-- MODAL --}}
                        <div x-show="open" x-cloak x-transition.opacity
                            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                            <div @click.outside="open = false" x-transition
                                class="w-full max-w-md p-6 bg-white shadow-2xl rounded-2xl">
                                <h3 class="mb-2 text-lg font-semibold text-gray-800">
                                    Konfirmasi Hapus
                                </h3>

                                <p class="mb-6 text-sm text-gray-600">
                                    Apakah kamu yakin ingin menghapus data ini?
                                    <span class="font-semibold text-red-600">Tindakan ini tidak bisa dibatalkan.</span>
                                </p>

                                <div class="flex justify-end gap-3">
                                    <button @click="open = false"
                                        class="px-4 py-2 text-sm font-semibold text-gray-600 bg-gray-200 rounded-lg hover:bg-gray-300">
                                        Batal
                                    </button>

                                    <form action="{{ route('fssby.destroy', $item->id) }}" method="POST">
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
                @endif

            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="10" class="py-6 text-center text-gray-500">
            Data sparepart belum tersedia.
        </td>
    </tr>
@endforelse


{{-- ================= MODAL DETAIL ================= --}}
<div id="detailModal" class="fixed inset-0 z-50 items-center justify-center hidden bg-black/60 backdrop-blur-sm">

    {{-- MODAL WRAPPER --}}
    <div id="modalWrapper"
        class="w-full max-w-2xl overflow-hidden transition-all duration-300 ease-out transform scale-95 bg-white shadow-2xl opacity-0 rounded-2xl">

        {{-- HEADER --}}
        <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-800">
                Detail Sparepart
            </h2>
            <button onclick="closeDetailModal()" class="text-2xl text-gray-400 transition hover:text-red-500">
                &times;
            </button>
        </div>

        {{-- BODY --}}
        <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2">

            {{-- IMAGE --}}
            <div class="flex flex-col items-center">
                <div class="flex items-center justify-center w-full overflow-hidden bg-gray-100 border h-92 rounded-xl">
                    <img id="d_image" class="hidden object-contain w-full h-full">
                    <span id="no_image" class="text-sm text-gray-400">
                        No Image Available
                    </span>
                </div>
            </div>

            {{-- INFO --}}
            <div class="space-y-4 text-sm text-gray-700">
                <div>
                    <p class="text-xs text-gray-500">Item Name</p>
                    <p class="font-semibold" id="d_item_name"></p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Type</p>
                    <p class="font-semibold" id="d_type"></p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Stock</p>
                    <p class="font-semibold">
                        <span id="d_stock"></span>
                        <span id="d_uom" class="ml-1 text-sm text-gray-600"></span>
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Date Update</p>
                    <p class="font-semibold" id="d_date"></p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Location</p>
                    <p class="font-semibold" id="d_location"></p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Note</p>
                    <p class="font-semibold" id="d_note"></p>
                </div>
            </div>
        </div>

        {{-- FOOTER --}}
        <div class="flex justify-end px-6 py-4 border-t bg-gray-50">
            <button onclick="closeDetailModal()"
                class="px-5 py-2 text-sm font-semibold text-white bg-gray-700 rounded-lg hover:bg-gray-800">
                Close
            </button>
        </div>
    </div>
</div>


{{-- ================= SCRIPT ================= --}}
<script>
    function openDetailModal(item) {

        // text
        document.getElementById('d_item_name').innerText = item.item_name;
        document.getElementById('d_type').innerText = item.type;
        document.getElementById('d_stock').innerText = item.stock;
        document.getElementById('d_uom').innerText = item.uom;
        document.getElementById('d_date').innerText = item.date_update;
        document.getElementById('d_location').innerText = item.location ?? '-';
        document.getElementById('d_note').innerText = item.note ?? '-';

        // image
        const img = document.getElementById('d_image');
        const noImg = document.getElementById('no_image');

        if (item.image) {
            img.src = `/storage/${item.image}`;
            img.classList.remove('hidden');
            noImg.classList.add('hidden');
        } else {
            img.classList.add('hidden');
            noImg.classList.remove('hidden');
        }

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
        wrapper.classList.remove('scale-100', 'opacity-100');

        setTimeout(() => {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }, 200);
    }
</script>
