@extends('layout.master')

@section('content')
    <div class="w-full space-y-6">

        {{-- PAGE HEADER --}}
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">Category Management</h1>
                <p class="mt-0.5 text-xs sm:text-sm font-medium text-slate-500">Organize and classify your spare parts
                    inventory.</p>
            </div>

            @if (Auth::user()->role === 'superadmin')
                <button onclick="openCreateCategoryModal()"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-xs sm:text-sm font-bold text-white transition-all bg-emerald-600 rounded-xl hover:bg-emerald-700 shadow-md shadow-emerald-600/20 active:scale-95 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Add New Category</span>
                </button>
            @endif
        </div>

        {{-- TABLE CARD CONTAINER --}}
        <div class="overflow-hidden bg-white shadow-sm ring-1 ring-slate-200/80 rounded-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[600px]">
                    <thead>
                        <tr
                            class="border-b border-slate-200/80 bg-slate-100/70 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">
                            <th class="w-16 px-4 sm:px-6 py-3.5 text-center">No</th>
                            <th class="px-4 sm:px-6 py-3.5">Category Details</th>
                            <th class="px-4 sm:px-6 py-3.5">Description</th>
                            @if (Auth::user()->role === 'superadmin')
                                <th class="w-36 px-4 sm:px-6 py-3.5 text-right">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="text-xs font-medium divide-y divide-slate-100 sm:text-sm text-slate-700">
                        @forelse ($categories as $cat)
                            <tr class="transition-colors hover:bg-slate-50/80">
                                <td class="px-4 sm:px-6 py-3.5 text-center text-slate-400 font-bold">
                                    {{ ($categories->currentPage() - 1) * $categories->perPage() + $loop->iteration }}
                                </td>
                                <td class="px-4 sm:px-6 py-3.5">
                                    <div class="text-sm font-bold text-slate-800">{{ $cat->name }}</div>
                                    <div class="text-[10px] text-emerald-600 font-bold uppercase mt-0.5">Active Category
                                    </div>
                                </td>
                                <td class="max-w-md px-4 sm:px-6 py-3.5 leading-relaxed text-slate-500">
                                    {{ $cat->description ?: 'No description provided.' }}
                                </td>
                                @if (Auth::user()->role === 'superadmin')
                                    <td class="px-4 sm:px-6 py-3.5 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button" onclick="openEditCategoryModal(this)"
                                                data-item="{{ json_encode($cat) }}"
                                                class="p-1.5 text-blue-600 bg-blue-50 hover:bg-blue-600 hover:text-white rounded-lg transition-colors"
                                                title="Edit Category">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </button>

                                            <form action="{{ route('categories.destroy', $cat->id) }}" method="POST"
                                                class="inline-block"
                                                onsubmit="return confirm('Are you sure you want to delete this category? This action cannot be undone.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="p-1.5 text-rose-600 bg-rose-50 hover:bg-rose-600 hover:text-white rounded-lg transition-colors"
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
                                <td colspan="4" class="p-10 text-center text-slate-400">
                                    <i class="block mb-2 text-3xl opacity-50 fa-solid fa-tags"></i>
                                    <p class="text-sm font-bold text-slate-700">No categories found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($categories->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                    {{ $categories->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- MODAL REUSABLE STYLES --}}
    @php $modalClasses = "fixed inset-0 z-50 flex items-center justify-center hidden p-3 sm:p-4 bg-slate-900/60 backdrop-blur-xs transition-all duration-300"; @endphp

    {{-- MODAL CREATE --}}
    <div id="modal-create-category" class="{{ $modalClasses }}">
        <div
            class="relative w-full max-w-md overflow-hidden bg-white shadow-2xl rounded-2xl sm:rounded-3xl max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 shrink-0">
                <h3 class="text-base font-extrabold text-slate-900">Create Category</h3>
                <button onclick="closeCreateCategoryModal()"
                    class="p-2 transition-colors rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form action="{{ route('categories.store') }}" method="POST" class="p-6 space-y-4 overflow-y-auto">
                @csrf
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Category Name</label>
                    <input type="text" name="name" required placeholder="e.g. Mechanical Parts"
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all bg-slate-50 focus:bg-white">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Description <span
                            class="font-normal lowercase text-slate-400">(optional)</span></label>
                    <textarea name="description" rows="3" placeholder="What kind of items belong here?"
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all bg-slate-50 focus:bg-white"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeCreateCategoryModal()"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">Discard</button>
                    <button type="submit"
                        class="px-5 py-2.5 text-xs font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 shadow-md shadow-emerald-600/20 active:scale-95 transition-all">Save
                        Category</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL EDIT --}}
    <div id="modal-edit-category" class="{{ $modalClasses }}">
        <div
            class="relative w-full max-w-md overflow-hidden bg-white shadow-2xl rounded-2xl sm:rounded-3xl max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 shrink-0">
                <h3 class="text-base font-extrabold text-slate-900">Update Category</h3>
                <button onclick="closeEditCategoryModal()"
                    class="p-2 transition-colors rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="form-edit-category" method="POST" class="p-6 space-y-4 overflow-y-auto">
                @csrf
                @method('PUT')
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Category Name</label>
                    <input type="text" id="edit_category_name" name="name" required
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Description</label>
                    <textarea id="edit_category_description" name="description" rows="3"
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeEditCategoryModal()"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">Cancel</button>
                    <button type="submit"
                        class="px-5 py-2.5 text-xs font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-md shadow-blue-600/20 active:scale-95 transition-all">Update
                        Category</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCreateCategoryModal() {
            const m = document.getElementById('modal-create-category');
            m.classList.remove('hidden');
            m.classList.add('flex');
        }

        function closeCreateCategoryModal() {
            const m = document.getElementById('modal-create-category');
            m.classList.add('hidden');
            m.classList.remove('flex');
        }

        function openEditCategoryModal(btn) {
            const item = JSON.parse(btn.getAttribute('data-item'));
            const m = document.getElementById('modal-edit-category');
            document.getElementById('form-edit-category').action = `/categories/${item.id}`;
            document.getElementById('edit_category_name').value = item.name;
            document.getElementById('edit_category_description').value = item.description || '';
            m.classList.remove('hidden');
            m.classList.add('flex');
        }

        function closeEditCategoryModal() {
            const m = document.getElementById('modal-edit-category');
            m.classList.add('hidden');
            m.classList.remove('flex');
        }

        window.onclick = function(event) {
            if (event.target === document.getElementById('modal-create-category')) closeCreateCategoryModal();
            if (event.target === document.getElementById('modal-edit-category')) closeEditCategoryModal();
        }
    </script>
@endsection
