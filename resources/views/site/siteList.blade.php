@extends('layout.master')

@section('title', 'Machine Registry List')

@section('content')
    <div class="w-full space-y-6">

        {{-- 1. HEADER CARD (TERPISAH) --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <div
                        class="inline-flex items-center gap-2 px-3 py-1 mb-2 text-xs font-bold text-blue-700 border border-blue-100 rounded-full bg-blue-50">
                        <i class="fa-solid fa-server text-[10px]"></i> Registry Database
                    </div>
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                        Machine Registry List
                    </h1>
                    <p class="mt-1 text-xs font-medium sm:text-sm text-slate-500">
                        Manage operational machines registered in the platform.
                    </p>
                </div>
                <a href="{{ route('site.create') }}"
                    class="inline-flex items-center justify-center gap-2 px-5 py-3 text-xs font-bold text-white transition-all shadow-md sm:text-sm bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-emerald-600/20 active:scale-95 shrink-0">
                    <i class="text-xs fa-solid fa-plus"></i>
                    <span>Add Machine</span>
                </a>
            </div>
        </div>

        {{-- 2. TABLE CARD CONTAINER --}}
        <div class="overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[650px]">
                    <thead>
                        <tr
                            class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 bg-slate-50 border-b border-slate-100">
                            <th class="w-16 px-6 py-4 text-center">No</th>
                            <th class="px-6 py-4 text-center w-36">Site Code</th>
                            <th class="px-6 py-4">Machine Name</th>
                            <th class="px-6 py-4">Machine Type</th>
                            <th class="px-6 py-4 text-center w-28">Status</th>
                            <th class="px-6 py-4 text-center w-36">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="text-xs font-medium divide-y divide-slate-100 text-slate-700">
                        @forelse ($site as $index => $item)
                            <tr class="transition-colors hover:bg-slate-50/60">
                                <td class="px-6 py-4 font-bold text-center text-slate-400">
                                    {{ $index + 1 }}
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="px-3 py-1 font-mono font-extrabold text-[11px] text-blue-700 bg-blue-50 border border-blue-200/80 rounded-lg">
                                        {{ $item->code }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-sm font-bold text-slate-900">
                                    {{ $item->name }}
                                </td>

                                <td class="px-6 py-4 font-semibold text-slate-600">
                                    {{ $item->machine_type }}
                                </td>

                                <td class="px-6 py-4 text-center">
                                    @if ($item->is_active)
                                        <span
                                            class="px-2.5 py-1 text-[10px] font-extrabold text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-full uppercase">
                                            Active
                                        </span>
                                    @else
                                        <span
                                            class="px-2.5 py-1 text-[10px] font-extrabold text-slate-700 bg-slate-100 border border-slate-200 rounded-full uppercase">
                                            Inactive
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        {{-- EDIT --}}
                                        <a href="{{ route('site.edit', $item->id) }}"
                                            class="flex items-center justify-center w-8 h-8 text-blue-600 transition-all border border-blue-100 rounded-xl bg-blue-50 hover:bg-blue-600 hover:text-white active:scale-95"
                                            title="Edit Machine">
                                            <i class="text-xs fa-solid fa-pen-to-square"></i>
                                        </a>

                                        {{-- DELETE --}}
                                        <form action="{{ route('site.destroy', $item->id) }}" method="POST" class="inline"
                                            onsubmit="return confirm('Are you sure you want to delete this site?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="flex items-center justify-center w-8 h-8 transition-all border rounded-xl text-rose-600 bg-rose-50 border-rose-100 hover:bg-rose-600 hover:text-white active:scale-95"
                                                title="Delete Machine">
                                                <i class="text-xs fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-12 text-center text-slate-400">
                                    <div
                                        class="flex items-center justify-center w-12 h-12 mx-auto mb-3 text-xl rounded-2xl bg-slate-100 text-slate-400">
                                        <i class="fa-solid fa-server"></i>
                                    </div>
                                    <p class="text-sm font-bold text-slate-800">No Registered Machines Found</p>
                                    <p class="mt-1 text-xs text-slate-400">Start registering hardware to build your fleet
                                        list.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
