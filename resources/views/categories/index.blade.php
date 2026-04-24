@extends('layout.master')

@section('content')
    <div class="w-full px-6 py-8">

        <div class="flex flex-col gap-4 mb-8 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-gray-900">Category Management</h1>
                <p class="mt-1 text-sm text-gray-500">Organize and classify your spare parts inventory.</p>
            </div>

            @if (Auth::user()->role === 'superadmin')
                <button onclick="openCreateCategoryModal()"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-bold text-white transition-all bg-emerald-600 rounded-xl hover:bg-emerald-700 shadow-lg shadow-emerald-200 active:scale-95">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add New Category
                </button>
            @endif
        </div>

        <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-200 rounded-3xl">
            <div class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead
                            class="text-xs font-bold tracking-wider text-gray-500 uppercase border-b border-gray-100 bg-gray-50/50">
                            <tr>
                                <th class="w-16 px-6 py-4 text-center">No</th>
                                <th class="px-6 py-4">Category Details</th>
                                <th class="px-6 py-4">Description</th>
                                @if (Auth::user()->role === 'superadmin')
                                    <th class="px-6 py-4 text-right">Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($categories as $cat)
                                <tr class="transition-colors group hover:bg-gray-50/80">
                                    <td class="px-6 py-4 font-medium text-center text-gray-400">
                                        {{ ($categories->currentPage() - 1) * $categories->perPage() + $loop->iteration }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-base font-bold text-gray-900">{{ $cat->name }}</div>
                                        <div class="text-[10px] text-emerald-600 font-bold uppercase">Active Category</div>
                                    </td>
                                    <td class="max-w-md px-6 py-4 leading-relaxed text-gray-600">
                                        {{ $cat->description ?: 'No description provided.' }}
                                    </td>
                                    @if (Auth::user()->role === 'superadmin')
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <button type="button" onclick="openEditCategoryModal(this)"
                                                    data-item="{{ json_encode($cat) }}"
                                                    class="p-2 text-blue-600 transition-colors rounded-lg bg-blue-50 hover:bg-blue-600 hover:text-white"
                                                    title="Edit Category">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                    </svg>
                                                </button>
                                                <form action="{{ route('categories.destroy', $cat->id) }}" method="POST"
                                                    class="inline-block"
                                                    onsubmit="return confirm('Delete this category? This action cannot be undone.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="p-2 text-red-600 transition-colors rounded-lg bg-red-50 hover:bg-red-600 hover:text-white"
                                                        title="Delete Category">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="p-4 mb-3 rounded-full bg-gray-50">
                                                <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-3m-3 0h-3m-3 0H4" />
                                                </svg>
                                            </div>
                                            <span class="font-medium text-gray-500">No categories found.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($categories->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/30">
                    {{ $categories->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- MODAL SYSTEM (REUSABLE STYLES) --}}
    @php $modalClasses = "fixed inset-0 z-50 flex items-center justify-center hidden px-4 bg-gray-900/60 backdrop-blur-sm transition-all duration-300"; @endphp

    {{-- MODAL CREATE --}}
    <div id="modal-create-category" class="{{ $modalClasses }}">
        <div class="relative w-full max-w-md overflow-hidden transition-all transform bg-white shadow-2xl rounded-3xl">
            <div class="px-8 pt-8 pb-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900">Create Category</h3>
                    <button onclick="closeCreateCategoryModal()"
                        class="p-2 text-gray-400 transition-colors rounded-full hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
            <form action="{{ route('categories.store') }}" method="POST" class="px-8 pb-8 space-y-4">
                @csrf
                <div>
                    <label class="block mb-1.5 text-sm font-bold text-gray-700">Category Name</label>
                    <input type="text" name="name" required placeholder="e.g. Mechanical Parts"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all">
                </div>
                <div>
                    <label class="block mb-1.5 text-sm font-bold text-gray-700">Description <span
                            class="font-normal text-gray-400">(Optional)</span></label>
                    <textarea name="description" rows="3" placeholder="What kind of items belong here?"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all"></textarea>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeCreateCategoryModal()"
                        class="flex-1 px-4 py-2.5 text-sm font-bold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">Discard</button>
                    <button type="submit"
                        class="flex-1 px-4 py-2.5 text-sm font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 shadow-lg shadow-emerald-100 transition-all">Save
                        Category</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL EDIT --}}
    <div id="modal-edit-category" class="{{ $modalClasses }}">
        <div class="relative w-full max-w-md overflow-hidden transition-all transform bg-white shadow-2xl rounded-3xl">
            <div class="px-8 pt-8 pb-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900">Update Category</h3>
                    <button onclick="closeEditCategoryModal()"
                        class="p-2 text-gray-400 transition-colors rounded-full hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
            <form id="form-edit-category" method="POST" class="px-8 pb-8 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block mb-1.5 text-sm font-bold text-gray-700">Category Name</label>
                    <input type="text" id="edit_category_name" name="name" required
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all">
                </div>
                <div>
                    <label class="block mb-1.5 text-sm font-bold text-gray-700">Description</label>
                    <textarea id="edit_category_description" name="description" rows="3"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all"></textarea>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeEditCategoryModal()"
                        class="flex-1 px-4 py-2.5 text-sm font-bold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">Cancel</button>
                    <button type="submit"
                        class="flex-1 px-4 py-2.5 text-sm font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-100 transition-all">Update
                        Category</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCreateCategoryModal() {
            const m = document.getElementById('modal-create-category');
            m.classList.remove('hidden');
            setTimeout(() => m.classList.add('opacity-100'), 10);
        }

        function closeCreateCategoryModal() {
            const m = document.getElementById('modal-create-category');
            m.classList.add('hidden');
        }

        function openEditCategoryModal(btn) {
            const item = JSON.parse(btn.getAttribute('data-item'));
            const m = document.getElementById('modal-edit-category');
            m.classList.remove('hidden');
            document.getElementById('form-edit-category').action = `/categories/${item.id}`;
            document.getElementById('edit_category_name').value = item.name;
            document.getElementById('edit_category_description').value = item.description || '';
        }

        function closeEditCategoryModal() {
            document.getElementById('modal-edit-category').classList.add('hidden');
        }
    </script>
@endsection
