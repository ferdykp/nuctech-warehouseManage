@extends('layout.master')

@section('content')
    <div class="w-full space-y-6">

        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">Machine Registry List</h1>
                <p class="text-xs sm:text-sm font-medium text-slate-500 mt-0.5">Manage operational machines registered in the
                    platform.</p>
            </div>
            <a href="{{ route('site.create') }}"
                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-xs sm:text-sm font-bold text-white transition-all bg-emerald-600 rounded-xl hover:bg-emerald-700 shadow-md shadow-emerald-600/20 active:scale-95 shrink-0">
                <i class="fa-solid fa-plus-circle"></i>
                <span>Add Machine</span>
            </a>
        </div>

        {{-- TABLE CARD --}}
        <div class="overflow-hidden bg-white border shadow-sm border-slate-200/80 rounded-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[650px]">
                    <thead
                        class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 bg-slate-100/70 border-b border-slate-200/80">
                        <tr>
                            <th class="px-4 sm:px-6 py-3.5 text-center w-16">No</th>
                            <th class="px-4 sm:px-6 py-3.5 text-center w-36">Site Code</th>
                            <th class="px-4 sm:px-6 py-3.5">Machine Name</th>
                            <th class="px-4 sm:px-6 py-3.5">Machine Type</th>
                            <th class="px-4 sm:px-6 py-3.5 text-center w-28">Status</th>
                            <th class="px-4 sm:px-6 py-3.5 text-center w-36">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="text-xs font-medium divide-y divide-slate-100 sm:text-sm text-slate-700">
                        @forelse ($site as $index => $item)
                            <tr class="transition-colors hover:bg-slate-50/80">
                                <td class="px-4 sm:px-6 py-3.5 text-center text-slate-400 font-bold">
                                    {{ $index + 1 }}
                                </td>

                                <td class="px-4 sm:px-6 py-3.5 text-center">
                                    <span
                                        class="px-2.5 py-1 font-extrabold text-[11px] text-blue-700 bg-blue-50 border border-blue-200/60 rounded-lg">
                                        {{ $item->code }}
                                    </span>
                                </td>

                                <td class="px-4 sm:px-6 py-3.5 font-bold text-slate-800">
                                    {{ $item->name }}
                                </td>

                                <td class="px-4 sm:px-6 py-3.5 text-slate-600">
                                    {{ $item->machine_type }}
                                </td>

                                <td class="px-4 sm:px-6 py-3.5 text-center">
                                    @if ($item->is_active)
                                        <span
                                            class="px-2.5 py-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200/60 rounded-lg uppercase">
                                            Active
                                        </span>
                                    @else
                                        <span
                                            class="px-2.5 py-1 text-[10px] font-bold text-slate-600 bg-slate-100 border border-slate-200 rounded-lg uppercase">
                                            Inactive
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 sm:px-6 py-3.5 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('site.edit', $item->id) }}"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold text-blue-600 bg-blue-50 rounded-xl hover:bg-blue-600 hover:text-white transition-all active:scale-95">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                            <span>Edit</span>
                                        </a>

                                        <form action="{{ route('site.destroy', $item->id) }}" method="POST"
                                            onsubmit="return confirm('Are you sure you want to delete this site?');">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold text-rose-600 bg-rose-50 rounded-xl hover:bg-rose-600 hover:text-white transition-all active:scale-95">
                                                <i class="fa-solid fa-trash-can"></i>
                                                <span>Delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-10 text-center text-slate-400">
                                    <i class="block mb-2 text-3xl opacity-50 fa-solid fa-server"></i>
                                    <p class="text-sm font-bold text-slate-700">No registered machines found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
