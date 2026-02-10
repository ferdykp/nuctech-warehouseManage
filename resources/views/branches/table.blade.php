{{-- resources/views/ebeam/table.blade.php --}}
<table class="w-full border-collapse">
    <thead class="text-gray-700 bg-gray-100">
        <tr>
            {{-- @if (Auth::user()->role === 'admin')
                <th class="px-4 py-3 text-center">
                    <input type="checkbox" id="select_all_id">
                </th>
            @endif --}}
            <th class="px-4 py-3 text-center">No</th>
            <th class="px-4 py-3 text-center">Kode</th>
            <th class="px-4 py-3 text-center">City</th>
            @if (Auth::user()->role === 'admin')
                <th class="px-4 py-3 text-center">Action</th>
            @endif
        </tr>
    </thead>

    <tbody>
        @forelse ($branches as $b)
            <tr class="border-b hover:bg-gray-50">
                {{-- @if (Auth::user()->role === 'admin')
                    <td class="px-4 py-3 text-center">
                        <input type="checkbox" class="checkbox-id" value="{{ $b->id }}">
                    </td>
                @endif --}}
                <td class="px-4 py-3 text-center">
                    {{ $loop->iteration + ($branches->currentPage() - 1) * $branches->perPage() }}
                </td>
                <td class="px-4 py-3 text-center">{{ $b->branch_code }}</td>
                <td class="px-4 py-3 text-center">{{ $b->branch_name }}</td>

                @if (Auth::user()->role === 'admin')
                    <td class="px-4 py-3 text-center">
                        <div class="flex justify-center gap-2">
                            {{-- <button onclick='openDetailModal(@json($item))'
                                class="px-3 py-2 font-semibold text-white bg-gray-600 rounded-lg text-md hover:bg-gray-700">
                                Detail
                            </button> --}}
                            {{-- <button onclick='openDetailModal(@json($b))'
                                class="px-3 py-2 text-white bg-gray-600 rounded-lg">
                                Detail
                            </button> --}}


                            {{-- <a href="{{ route('branches.edit', $b->id) }}"
                                class="px-3 py-2 font-semibold text-white bg-blue-600 rounded-lg text-md hover:bg-blue-700">
                                Edit
                            </a> --}}

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
                                            <form action="{{ route('branches.destroy', $b->id) }}" method="POST">
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
    {{ $branches->links() }}
</div>
