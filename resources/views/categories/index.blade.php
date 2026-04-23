@extends('layout.master')

@section('content')
    <div class="w-full px-6 py-6">
        <div class="bg-white shadow rounded-2xl">
            <div class="px-6 py-4 border-b">
                <h2 class="text-xl font-semibold uppercase">Manajemen Kategori</h2>
                <p class="text-sm text-gray-500">Kelola kategori untuk sparepart</p>
                <div class="w-32 mt-2 border-b-4 border-emerald-600"></div>
            </div>

            <div class="p-6">
                <div class="flex flex-col gap-3 mb-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        @if (Auth::user()->role === 'superadmin')
                            <button onclick="openCreateCategoryModal()"
                                class="flex items-center gap-2 p-3 text-sm font-semibold text-white transition-all rounded-lg bg-emerald-600 hover:bg-emerald-700">
                                <i class="fa-solid fa-plus"></i> Tambah Kategori
                            </button>
                        @endif
                    </div>
                </div>

                <div class="overflow-x-auto border rounded-lg">
                    <table class="w-full border-collapse">
                        <thead class="text-gray-700 bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left">No</th>
                                <th class="px-4 py-3 text-left">Nama Kategori</th>
                                <th class="px-4 py-3 text-left">Deskripsi</th>
                                @if (Auth::user()->role === 'superadmin')
                                    <th class="px-4 py-3 text-center">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($categories as $cat)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        {{ ($categories->currentPage() - 1) * $categories->perPage() + $loop->iteration }}
                                    </td>
                                    <td class="px-4 py-3 font-bold">{{ $cat->name }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $cat->description ?: '-' }}</td>
                                    @if (Auth::user()->role === 'superadmin')
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex justify-center gap-2">
                                                <button type="button" onclick="openEditCategoryModal(this)"
                                                    data-item="{{ json_encode($cat) }}"
                                                    class="px-3 py-1 text-xs text-white bg-blue-500 rounded hover:bg-blue-600">
                                                    EDIT
                                                </button>
                                                <form action="{{ route('categories.destroy', $cat->id) }}" method="POST"
                                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="px-3 py-1 text-xs text-white bg-red-500 rounded hover:bg-red-600">
                                                        HAPUS
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="p-4 text-center text-gray-500">Data kategori tidak ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $categories->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL CREATE --}}
    <div id="modal-create-category"
        class="fixed inset-0 z-50 flex items-center justify-center hidden px-4 bg-black/60 backdrop-blur-sm">
        <div class="relative w-full max-w-md overflow-hidden bg-white shadow-2xl rounded-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50">
                <h3 class="text-xl font-bold text-gray-800">Tambah Kategori</h3>
                <button onclick="closeCreateCategoryModal()" class="text-gray-400 transition-colors hover:text-red-500">
                    <i class="text-xl fa-solid fa-xmark"></i>
                </button>
            </div>
            <form action="{{ route('categories.store') }}" method="POST" class="p-6">
                @csrf
                <div class="mb-4">
                    <label class="block mb-1 text-sm font-semibold text-gray-700">Nama Kategori</label>
                    <input type="text" name="name" required placeholder="Contoh: Mekanikal"
                        class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none">
                </div>
                <div class="mb-6">
                    <label class="block mb-1 text-sm font-semibold text-gray-700">Deskripsi</label>
                    <textarea name="description" rows="3"
                        class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none"></textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeCreateCategoryModal()"
                        class="px-5 py-2 text-sm font-semibold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">Batal</button>
                    <button type="submit"
                        class="px-5 py-2 text-sm font-bold text-white rounded-lg bg-emerald-600 hover:bg-emerald-700">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL EDIT --}}
    <div id="modal-edit-category"
        class="fixed inset-0 z-50 flex items-center justify-center hidden px-4 bg-black/60 backdrop-blur-sm">
        <div class="relative w-full max-w-md overflow-hidden bg-white shadow-2xl rounded-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50">
                <h3 class="text-xl font-bold text-gray-800">Edit Kategori</h3>
                <button onclick="closeEditCategoryModal()" class="text-gray-400 transition-colors hover:text-red-500">
                    <i class="text-xl fa-solid fa-xmark"></i>
                </button>
            </div>
            <form id="form-edit-category" method="POST" class="p-6">
                @csrf
                @method('PUT')
                <div class="mb-4">
                    <label class="block mb-1 text-sm font-semibold text-gray-700">Nama Kategori</label>
                    <input type="text" id="edit_category_name" name="name" required
                        class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div class="mb-6">
                    <label class="block mb-1 text-sm font-semibold text-gray-700">Deskripsi</label>
                    <textarea id="edit_category_description" name="description" rows="3"
                        class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"></textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeEditCategoryModal()"
                        class="px-5 py-2 text-sm font-semibold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">Batal</button>
                    <button type="submit"
                        class="px-5 py-2 text-sm font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCreateCategoryModal() {
            document.getElementById('modal-create-category').classList.remove('hidden');
        }

        function closeCreateCategoryModal() {
            document.getElementById('modal-create-category').classList.add('hidden');
        }

        function openEditCategoryModal(btn) {
            const item = JSON.parse(btn.getAttribute('data-item'));
            document.getElementById('modal-edit-category').classList.remove('hidden');
            document.getElementById('form-edit-category').action = `/categories/${item.id}`;
            document.getElementById('edit_category_name').value = item.name;
            document.getElementById('edit_category_description').value = item.description || '';
        }

        function closeEditCategoryModal() {
            document.getElementById('modal-edit-category').classList.add('hidden');
        }
    </script>
@endsection
