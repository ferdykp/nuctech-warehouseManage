<div class="w-full overflow-x-auto">
    <table class="w-full text-left border-collapse min-w-[650px]">
        <thead
            class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 bg-slate-50 border-b border-slate-100">
            <tr>
                <th class="px-6 py-3.5 text-center w-16">No</th>
                <th class="px-6 py-3.5 w-36">Branch Code</th>
                <th class="px-6 py-3.5">Branch Name & Details</th>
                @if (Auth::user()?->role === 'superadmin')
                    <th class="px-6 py-3.5 text-center w-40">Actions</th>
                @endif
            </tr>
        </thead>

        <tbody class="text-xs font-medium divide-y divide-slate-100 text-slate-700">
            @forelse ($branches as $b)
                <tr class="transition-colors hover:bg-slate-50/60">
                    <td class="px-6 py-4 font-bold text-center text-slate-400">
                        {{ $loop->iteration + ($branches->currentPage() - 1) * $branches->perPage() }}
                    </td>
                    <td class="px-6 py-4">
                        <span
                            class="inline-flex items-center px-2.5 py-1 font-black text-[11px] text-blue-700 bg-blue-50 border border-blue-200/80 rounded-lg tracking-wider">
                            {{ $b->branch_code }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div>
                            <p class="text-sm font-bold text-slate-900">{{ $b->branch_name }}</p>
                            @if (!empty($b->branch_address))
                                <p class="text-slate-400 text-[11px] font-normal truncate max-w-md mt-0.5">
                                    <i class="mr-1 fa-solid fa-location-dot text-slate-300"></i>{{ $b->branch_address }}
                                </p>
                            @endif
                        </div>
                    </td>

                    @if (Auth::user()?->role === 'superadmin')
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('branches.edit', $b->id) }}"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-blue-600 bg-blue-50 border border-blue-100 rounded-xl hover:bg-blue-600 hover:text-white transition-all active:scale-95">
                                    <i class="fa-solid fa-pen-to-square text-[11px]"></i>
                                    <span>Edit</span>
                                </a>

                                {{-- DELETE CONFIRMATION MODAL --}}
                                <div x-data="{ open: false }">
                                    <button @click="open = true" type="button"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-rose-600 bg-rose-50 border border-rose-100 rounded-xl hover:bg-rose-600 hover:text-white transition-all active:scale-95">
                                        <i class="fa-solid fa-trash-can text-[11px]"></i>
                                        <span>Delete</span>
                                    </button>

                                    <div x-show="open" x-cloak x-transition.opacity
                                        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
                                        <div @click.outside="open = false" x-transition
                                            class="w-full max-w-md p-6 text-left bg-white border shadow-2xl border-slate-100 rounded-3xl">

                                            <div class="flex items-center gap-3 mb-4">
                                                <div
                                                    class="flex items-center justify-center w-10 h-10 border rounded-2xl bg-rose-50 border-rose-100 text-rose-600 shrink-0">
                                                    <i class="text-base fa-solid fa-triangle-exclamation"></i>
                                                </div>
                                                <div>
                                                    <h3 class="text-base font-extrabold text-slate-900">Confirm Deletion
                                                    </h3>
                                                    <p class="text-xs font-medium text-slate-400">Permanent action
                                                        warning</p>
                                                </div>
                                            </div>

                                            <p
                                                class="mb-6 text-xs font-medium leading-relaxed sm:text-sm text-slate-600">
                                                Are you sure you want to delete branch
                                                <strong
                                                    class="font-bold text-slate-900">"{{ $b->branch_name }}"</strong>?
                                                <span class="block mt-1 text-xs font-semibold text-rose-600">This action
                                                    cannot be undone.</span>
                                            </p>

                                            <div
                                                class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                                                <button @click="open = false" type="button"
                                                    class="px-4 py-2 text-xs font-bold transition-colors text-slate-600 hover:text-slate-800">
                                                    Cancel
                                                </button>
                                                <form action="{{ route('branches.destroy', $b->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="px-5 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 active:scale-[0.98] transition-all rounded-xl shadow-md shadow-rose-600/20">
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
                    <td colspan="4" class="p-12 text-center text-slate-400">
                        <div
                            class="flex items-center justify-center w-12 h-12 mx-auto mb-3 text-xl rounded-2xl bg-slate-100 text-slate-400">
                            <i class="fa-solid fa-building-circle-xmark"></i>
                        </div>
                        <p class="text-sm font-bold text-slate-800">No Branches Found</p>
                        <p class="mt-1 text-xs text-slate-400">There are no records matching your query.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="p-4 border-t sm:p-6 border-slate-100 bg-slate-50/50">
    {{ $branches->links() }}
</div>
