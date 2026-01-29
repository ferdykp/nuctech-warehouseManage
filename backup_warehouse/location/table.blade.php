{{-- TABLE BODY --}}
@forelse ($data as $index => $item)
    <tr class="border-b hover:bg-gray-50">

        {{-- @if (Auth::user()->role === 'admin')
            <td class="px-4 py-3 text-center">
                <input type="checkbox" class="w-4 h-4 checkbox_id" value="{{ $item->id }}">
            </td>
        @endif --}}

        {{-- NO --}}
        {{-- <td class="px-4 py-3 text-center">
            {{ $index + 1 + ($data->currentPage() - 1) * $data->perPage() }}
        </td> --}}

        {{-- ITEM NAME --}}
        {{-- <td class="px-4 py-3 text-center">
            {{ $item->machine_type }}
        </td> --}}
        <td class="px-4 py-3 text-center">
            {{ $item->machine_type_label }}
        </td>


        {{-- TYPE --}}
        <td class="px-4 py-3 text-center">
            {{ $item->location_name }}
        </td>


        {{-- ACTION --}}
        <td class="px-4 py-3">
            <div class="flex justify-center gap-2">

                @if (Auth::user()->role === 'admin')
                    <a href="{{ route('location.edit', $item->id) }}"
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

                                    <form action="{{ route('location.destroy', $item->id) }}" method="POST">
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
            Lokasi penyimpanan belum tersedia.
        </td>
    </tr>
@endforelse
