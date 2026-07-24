<div class="w-full overflow-x-auto">
    <table class="w-full text-left border-collapse min-w-[600px]">
        <thead
            class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 bg-slate-100/70 border-b border-slate-200/80">
            <tr>
                <th class="px-4 sm:px-6 py-3.5 text-center w-16">No</th>
                <th class="px-4 sm:px-6 py-3.5 text-center w-36">Branch Code</th>
                <th class="px-4 sm:px-6 py-3.5">Branch Name</th>
                @if (Auth::user()->role === 'superadmin')
                    <th class="px-4 sm:px-6 py-3.5 text-center w-40">Actions</th>
                @endif
            </tr>
        </thead>

        <tbody class="text-xs font-medium divide-y divide-slate-100 sm:text-sm text-slate-700">
            @forelse ($branches as $b)
                <tr class="transition-colors hover:bg-slate-50/80">
                    <td class="px-4 sm:px-6 py-3.5 text-center text-slate-400 font-bold">
                        {{ $loop->iteration + ($branches->currentPage() - 1) * $branches->perPage() }}
                    </td>
                    <td class="px-4 sm:px-6 py-3.5 text-center">
                        <span
                            class="px-2.5 py-1 font-extrabold text-[11px] text-blue-700 bg-blue-50 border border-blue-200/60 rounded-lg">
                            {{ $b->branch_code }}
                        </span>
                    </td>
                    <td class="px-4 sm:px-6 py-3.5 font-bold text-slate-800">
                        {{ $b->branch_name }}
                    </td>

                    @if (Auth::user()->role === 'superadmin')
                        <td class="px-4 sm:px-6 py-3.5 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('branches.edit', $b->id) }}"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-blue-600 bg-blue-50 rounded-xl hover:bg-blue-600 hover:text-white transition-all active:scale-95">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                    <span>Edit</span>
                                </a>

                                {{-- DELETE MODAL TRIGGER --}}
                                <div x-data="{ open: false }">
                                    <button @click="open = true" type="button"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-rose-600 bg-rose-50 rounded-xl hover:bg-rose-600 hover:text-white transition-all active:scale-95">
                                        <i class="fa-solid fa-trash-can"></i>
                                        <span>Delete</span>
                                    </button>

                                    <div x-show="open" x-cloak x-transition.opacity
                                        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
                                        <div @click.outside="open = false" x-transition
                                            class="w-full max-w-md p-6 text-left bg-white shadow-2xl rounded-2xl">
                                            <div class="flex items-center gap-3 mb-3 text-rose-600">
                                                <div class="p-2.5 bg-rose-100 rounded-xl">
                                                    <i class="text-lg fa-solid fa-triangle-exclamation"></i>
                                                </div>
                                                <h3 class="text-base font-extrabold text-slate-800">Confirm Deletion
                                                </h3>
                                            </div>
                                            <p class="mb-6 text-xs leading-relaxed sm:text-sm text-slate-600">
                                                Are you sure you want to delete branch
                                                <strong>"{{ $b->branch_name }}"</strong>?
                                                <span class="block mt-1 font-semibold text-rose-600">This action cannot
                                                    be undone.</span>
                                            </p>
                                            <div class="flex items-center justify-end gap-2.5">
                                                <button @click="open = false" type="button"
                                                    class="px-4 py-2 text-xs font-bold transition-colors text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200">
                                                    Cancel
                                                </button>
                                                <form action="{{ route('branches.destroy', $b->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="px-4 py-2 text-xs font-bold text-white transition-all shadow-md bg-rose-600 rounded-xl hover:bg-rose-700 shadow-rose-600/20 active:scale-95">
                                                        Yes, Delete
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
                    <td colspan="4" class="p-10 text-center text-slate-400">
                        <i class="block mb-2 text-3xl opacity-50 fa-solid fa-building-circle-xmark"></i>
                        <p class="text-sm font-bold text-slate-700">No branches found</p>
                        <p class="text-xs text-slate-400 mt-0.5">Try searching with a different keyword</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="p-4 border-t border-slate-100">
    {{ $branches->links() }}
</div>
