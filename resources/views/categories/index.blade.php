@extends('layout.master')

@section('title', 'Category Management')

@section('content')
    <div class="w-full space-y-6">

        {{-- 1. HEADER CARD --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div
                        class="inline-flex items-center gap-2 px-3 py-1 mb-2 text-xs font-bold text-blue-700 border border-blue-100 rounded-full bg-blue-50">
                        <i class="fa-solid fa-tags text-[10px]"></i> Item Classification
                    </div>
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                        Category Management
                    </h1>
                    <p class="mt-1 text-xs font-semibold sm:text-sm text-slate-500">
                        Organize, classify, and structure your spare parts inventory items efficiently.
                    </p>
                </div>

                @if (Auth::user()?->role === 'superadmin')
                    <button onclick="openCreateCategoryModal()" type="button"
                        class="inline-flex items-center justify-center gap-2 px-5 py-3 text-xs font-bold text-white transition-all shadow-md cursor-pointer sm:text-sm bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-emerald-600/20 active:scale-95 shrink-0">
                        <i class="text-xs fa-solid fa-plus"></i>
                        <span>Add New Category</span>
                    </button>
                @endif
            </div>
        </div>

        {{-- 2. TABLE CARD --}}
        <div class="overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[600px]">
                    <thead>
                        <tr
                            class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 bg-slate-50 border-b border-slate-100">
                            <th class="w-16 px-6 py-4 text-center">No</th>
                            <th class="px-6 py-4">Category Details</th>
                            <th class="px-6 py-4">Description</th>
                            @if (Auth::user()?->role === 'superadmin')
                                <th class="px-6 py-4 text-right w-36">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="text-xs font-medium divide-y divide-slate-100 text-slate-700">
                        @forelse ($categories as $cat)
                            <tr class="transition-colors hover:bg-slate-50/60">
                                {{-- Row Index --}}
                                <td class="px-6 py-4 font-bold text-center text-slate-400">
                                    {{ ($categories->currentPage() - 1) * $categories->perPage() + $loop->iteration }}
                                </td>

                                {{-- Category Details --}}
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span
                                            class="text-sm font-bold leading-snug text-slate-900">{{ $cat->name }}</span>
                                        <span
                                            class="inline-flex items-center gap-1.5 text-[10px] text-emerald-600 font-extrabold uppercase mt-0.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active Category
                                        </span>
                                    </div>
                                </td>

                                {{-- Description --}}
                                <td class="max-w-md px-6 py-4 font-medium leading-relaxed text-slate-500">
                                    {{ $cat->description ?: 'No description provided.' }}
                                </td>

                                {{-- Actions --}}
                                @if (Auth::user()?->role === 'superadmin')
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            {{-- EDIT --}}
                                            <button type="button" onclick="openEditCategoryModal(this)"
                                                data-item="{{ json_encode($cat) }}"
                                                class="flex items-center justify-center w-8 h-8 text-blue-600 transition-all border border-blue-100 cursor-pointer rounded-xl bg-blue-50 hover:bg-blue-600 hover:text-white active:scale-95"
                                                title="Edit Category">
                                                <i class="text-xs fa-solid fa-pen-to-square"></i>
                                            </button>

                                            {{-- DELETE --}}
                                            <button type="button"
                                                onclick="openDeleteCategoryModal('{{ route('categories.destroy', $cat->id) }}', '{{ addslashes($cat->name) }}')"
                                                class="flex items-center justify-center w-8 h-8 transition-all border cursor-pointer rounded-xl text-rose-600 bg-rose-50 border-rose-100 hover:bg-rose-600 hover:text-white active:scale-95"
                                                title="Delete Category">
                                                <i class="text-xs fa-solid fa-trash-can"></i>
                                            </button>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ Auth::user()?->role === 'superadmin' ? 4 : 3 }}"
                                    class="p-12 text-center text-slate-400">
                                    <div
                                        class="flex items-center justify-center w-12 h-12 mx-auto mb-3 text-xl rounded-2xl bg-slate-100 text-slate-400">
                                        <i class="fa-solid fa-tags"></i>
                                    </div>
                                    <p class="text-sm font-bold text-slate-800">No Categories Found</p>
                                    <p class="mt-1 text-xs text-slate-400">Start by adding a new item category to structure
                                        your inventory.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($categories->hasPages())
                <div class="p-4 border-t sm:p-6 border-slate-100 bg-slate-50/50">
                    {{ $categories->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- MODAL REUSABLE STYLES --}}
    @php $modalClasses = "fixed inset-0 z-50 flex items-center justify-center hidden p-4 bg-slate-900/60 backdrop-blur-xs transition-all duration-300"; @endphp

    {{-- MODAL CREATE --}}
    <div id="modal-create-category" class="{{ $modalClasses }}">
        <div
            class="relative w-full max-w-md overflow-hidden bg-white shadow-2xl rounded-3xl border border-slate-100 max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 shrink-0">
                <div class="flex items-center gap-3">
                    <div
                        class="flex items-center justify-center w-10 h-10 border text-emerald-600 bg-emerald-50 rounded-2xl border-emerald-100">
                        <i class="text-base fa-solid fa-tags"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Create Category</h3>
                        <p class="text-[10px] font-bold tracking-wider uppercase text-slate-400">Inventory Setup</p>
                    </div>
                </div>
                <button onclick="closeCreateCategoryModal()" type="button"
                    class="flex items-center justify-center w-8 h-8 transition-colors rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                    <i class="text-base fa-solid fa-xmark"></i>
                </button>
            </div>
            <form action="{{ route('categories.store') }}" method="POST" class="p-6 space-y-4 overflow-y-auto">
                @csrf
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                        Category Name <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" required placeholder="e.g. Mechanical Parts"
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 font-medium">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                        Description <span class="font-normal lowercase text-slate-400">(optional)</span>
                    </label>
                    <textarea name="description" rows="3" placeholder="What kind of items belong here?"
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 font-medium"></textarea>
                </div>
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeCreateCategoryModal()"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">Discard</button>
                    <button type="submit"
                        class="px-5 py-2.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md shadow-emerald-600/20 active:scale-[0.98] transition-all">
                        <i class="mr-1.5 fa-solid fa-floppy-disk"></i> Save Category
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL EDIT --}}
    <div id="modal-edit-category" class="{{ $modalClasses }}">
        <div
            class="relative w-full max-w-md overflow-hidden bg-white shadow-2xl rounded-3xl border border-slate-100 max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 shrink-0">
                <div class="flex items-center gap-3">
                    <div
                        class="flex items-center justify-center w-10 h-10 text-blue-600 border border-blue-100 bg-blue-50 rounded-2xl">
                        <i class="text-base fa-solid fa-pen-to-square"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Update Category</h3>
                        <p class="text-[10px] font-bold tracking-wider uppercase text-slate-400">Inventory Setup</p>
                    </div>
                </div>
                <button onclick="closeEditCategoryModal()" type="button"
                    class="flex items-center justify-center w-8 h-8 transition-colors rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                    <i class="text-base fa-solid fa-xmark"></i>
                </button>
            </div>
            <form id="form-edit-category" method="POST" class="p-6 space-y-4 overflow-y-auto">
                @csrf
                @method('PUT')
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                        Category Name <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="edit_category_name" name="name" required
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 font-medium">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Description</label>
                    <textarea id="edit_category_description" name="description" rows="3"
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 font-medium"></textarea>
                </div>
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeEditCategoryModal()"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">Cancel</button>
                    <button type="submit"
                        class="px-5 py-2.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-600/20 active:scale-[0.98] transition-all">
                        Update Category
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL DELETE CONFIRMATION --}}
    <div id="modal-delete-category"
        class="fixed inset-0 z-50 flex items-center justify-center hidden p-4 transition-all bg-slate-900/60 backdrop-blur-xs">
        <div class="relative w-full max-w-sm p-6 text-center bg-white border shadow-2xl border-slate-100 rounded-3xl">
            <div class="flex justify-center mb-4">
                <div
                    class="flex items-center justify-center w-12 h-12 border rounded-2xl bg-rose-50 border-rose-100 text-rose-600">
                    <i class="text-xl fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>
            <h3 class="mb-1 text-base font-extrabold text-slate-900">Confirm Deletion</h3>
            <p class="mb-6 text-xs font-medium text-slate-500">
                Are you sure you want to delete <strong id="delete-category-name" class="text-slate-900"></strong>?
                <span class="block mt-1 font-semibold text-rose-600">This action cannot be undone.</span>
            </p>
            <form id="form-delete-category" method="POST">
                @csrf
                @method('DELETE')
                <div class="flex items-center justify-end gap-3">
                    <button type="button" onclick="closeDeleteCategoryModal()"
                        class="w-full py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                        class="w-full py-2.5 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 active:scale-[0.98] transition-all rounded-xl shadow-md shadow-rose-600/20">
                        Yes, Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function openCreateCategoryModal() {
            const m = document.getElementById('modal-create-category');
            m.classList.remove('hidden');
            m.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeCreateCategoryModal() {
            const m = document.getElementById('modal-create-category');
            m.classList.add('hidden');
            m.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        function openEditCategoryModal(btn) {
            const item = JSON.parse(btn.getAttribute('data-item'));
            const m = document.getElementById('modal-edit-category');
            document.getElementById('form-edit-category').action = `/categories/${item.id}`;
            document.getElementById('edit_category_name').value = item.name;
            document.getElementById('edit_category_description').value = item.description || '';
            m.classList.remove('hidden');
            m.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeEditCategoryModal() {
            const m = document.getElementById('modal-edit-category');
            m.classList.add('hidden');
            m.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        function openDeleteCategoryModal(url, name) {
            const m = document.getElementById('modal-delete-category');
            document.getElementById('form-delete-category').action = url;
            document.getElementById('delete-category-name').innerText = name;
            m.classList.remove('hidden');
            m.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeDeleteCategoryModal() {
            const m = document.getElementById('modal-delete-category');
            m.classList.add('hidden');
            m.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        window.onclick = function(event) {
            if (event.target === document.getElementById('modal-create-category')) closeCreateCategoryModal();
            if (event.target === document.getElementById('modal-edit-category')) closeEditCategoryModal();
            if (event.target === document.getElementById('modal-delete-category')) closeDeleteCategoryModal();
        }
    </script>
@endpush
