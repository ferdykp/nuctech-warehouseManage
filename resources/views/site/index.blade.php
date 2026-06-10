@extends('layout.master')

@section('content')
    <div class="w-full px-4 py-6 md:px-6 md:py-8">

        {{-- BREADCRUMB & TITLES --}}
        <div class="flex flex-col gap-4 mb-8 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <nav class="flex mb-2 text-xs font-bold tracking-widest text-gray-400 uppercase">
                    <span class="transition-colors cursor-pointer hover:text-blue-600">Infrastructure</span>
                    <span class="mx-2">/</span>
                    <span class="text-blue-600">Machine Sites</span>
                </nav>
                <h1 class="text-2xl font-extrabold tracking-tight text-gray-900 md:text-3xl">Site Management</h1>
                <p class="mt-1 text-sm text-gray-500">Monitor inventory status and machine distribution across all regions.
                </p>
            </div>

            @if (auth()->user()->role === 'superadmin')
                <button onclick="openCreateModal()"
                    class="inline-flex items-center justify-center w-full gap-2 px-5 py-3 text-sm font-bold text-white transition-all bg-blue-600 shadow-xl rounded-xl hover:bg-blue-700 shadow-blue-200 active:scale-95 group sm:w-auto">
                    <svg class="w-5 h-5 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Register New Site
                </button>
            @endif
        </div>

        {{-- STATS GRID --}}
        <div class="grid grid-cols-1 gap-4 mb-8 sm:grid-cols-2 lg:grid-cols-3 md:gap-6">
            @php
                $stats = [
                    [
                        'label' => 'Registered Machines',
                        'val' => $sites->count(),
                        'color' => 'blue',
                        'icon' =>
                            'M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z',
                    ],
                    [
                        'label' => 'Operational Branches',
                        'val' => $sites->unique('branch_id')->count(),
                        'color' => 'emerald',
                        'icon' =>
                            'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                    ],
                    [
                        'label' => 'Stock Volume',
                        'val' => \App\Models\SparepartStock::sum('qty'),
                        'color' => 'orange',
                        'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                    ],
                ];
            @endphp

            @foreach ($stats as $s)
                <div
                    class="relative p-6 overflow-hidden transition-all bg-white shadow-sm ring-1 ring-gray-200 rounded-3xl hover:shadow-md">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-{{ $s['color'] }}-50 rounded-2xl text-{{ $s['color'] }}-600 flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="{{ $s['icon'] }}" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold tracking-widest text-gray-400 uppercase">{{ $s['label'] }}</p>
                            <p class="mt-1 text-2xl font-black leading-none text-gray-900 md:text-3xl">
                                {{ number_format($s['val']) }}</p>
                        </div>
                    </div>
                    <div class="absolute -right-2 -bottom-2 opacity-5 text-{{ $s['color'] }}-600 pointer-events-none">
                        <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24">
                            <path d="{{ $s['icon'] }}" />
                        </svg>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- TABLE SECTION --}}
        <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-200 rounded-3xl">
            <div class="overflow-x-auto scrollbar-thin">
                <table class="w-full text-left whitespace-nowrap lg:whitespace-normal">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/50">
                            <th class="px-6 py-4 text-xs font-bold tracking-widest text-gray-500 uppercase md:px-8 md:py-5">
                                Machine Identity</th>
                            <th class="px-6 py-4 text-xs font-bold tracking-widest text-gray-500 uppercase md:px-8 md:py-5">
                                Branch</th>
                            <th class="px-6 py-4 text-xs font-bold tracking-widest text-gray-500 uppercase md:px-8 md:py-5">
                                Location / Address</th>
                            <th
                                class="px-6 py-4 text-xs font-bold tracking-widest text-right text-gray-500 uppercase md:px-8 md:py-5">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($sites as $site)
                            <tr class="transition-colors group hover:bg-gray-50/50">
                                <td class="px-6 py-4 md:px-8 md:py-5">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="flex items-center justify-center flex-shrink-0 text-blue-600 transition-colors w-11 h-11 md:w-12 md:h-12 rounded-2xl bg-blue-50 group-hover:bg-blue-600 group-hover:text-white">
                                            <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <span
                                                class="block text-sm font-bold text-gray-900 truncate md:text-base">{{ $site->machine_name }}</span>
                                            <span class="block text-[11px] font-medium text-gray-400 truncate">ID:
                                                #{{ str_pad($site->id, 4, '0', STR_PAD_LEFT) }} •
                                                {{ $site->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4 md:px-8 md:py-5">
                                    <div
                                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span
                                            class="text-xs font-bold tracking-wider uppercase">{{ $site->branch->branch_name ?? 'Unassigned' }}</span>
                                    </div>
                                </td>

                                <td class="px-6 py-4 md:px-8 md:py-5">
                                    <div class="flex flex-col max-w-[220px] md:max-w-[300px]">
                                        <div class="flex items-center gap-1.5 text-gray-900 mb-0.5">
                                            <svg class="flex-shrink-0 w-4 h-4 text-gray-400" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <span class="text-sm font-semibold truncate">{{ $site->location }}</span>
                                        </div>
                                        <span class="text-xs leading-relaxed text-gray-500 whitespace-normal line-clamp-2">
                                            {{ $site->address ?? 'No detailed address provided' }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-6 py-4 md:px-8 md:py-5">
                                    <div class="flex items-center justify-end gap-2 md:gap-3">
                                        <button onclick="openDetailModal({{ json_encode($site->load('branch')) }})"
                                            class="p-2 text-gray-400 transition-colors rounded-xl hover:bg-gray-100 hover:text-gray-900"
                                            title="View Details">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <a href="{{ route('sparepart.index', $site->slug) }}"
                                            class="flex items-center flex-shrink-0 gap-2 px-3 py-2 text-xs font-bold text-gray-700 transition-all bg-white border border-gray-200 rounded-xl hover:bg-gray-900 hover:text-white hover:border-gray-900">
                                            Open Vault
                                        </a>

                                        @if (auth()->user()->role === 'superadmin')
                                            <div class="w-px h-6 bg-gray-100 mx-0.5"></div>
                                            <button onclick="openEditModal({{ json_encode($site) }})"
                                                class="p-2 text-gray-400 transition-colors rounded-xl hover:bg-blue-50 hover:text-blue-600">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2.5 2.5 0 113.536 3.536L12 14.232l-4 1 1-4 9.732-9.732z" />
                                                </svg>
                                            </button>

                                            <button type="button" onclick="openDeleteModal({{ json_encode($site) }})"
                                                class="p-2 text-gray-400 transition-colors rounded-xl hover:bg-red-50 hover:text-red-600">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-8 py-12 text-sm italic text-center text-gray-400">No machine
                                    sites registered yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $sites->links() }}
        </div>
    </div>

    {{-- MODAL STYLING BASE --}}
    @php $modalBase = "fixed inset-0 z-[100] flex items-center justify-center hidden px-4 bg-gray-900/60 backdrop-blur-sm transition-all duration-300"; @endphp

    {{-- MODAL DETAIL --}}
    <div id="modal-detail" class="{{ $modalBase }}">
        <div
            class="w-full max-w-2xl overflow-hidden transition-all transform scale-100 bg-white shadow-2xl rounded-3xl max-h-[calc(100vh-2rem)] flex flex-col">
            <div class="relative flex-shrink-0 px-6 py-5 border-b md:px-8 md:pt-8 md:pb-4 border-gray-50">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center justify-center w-12 h-12 text-blue-600 rounded-2xl bg-blue-50">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h3 id="det_title" class="text-lg font-black text-gray-900 truncate md:text-xl">Site
                                Information</h3>
                            <p id="det_subtitle"
                                class="text-[10px] font-medium tracking-widest text-gray-400 uppercase truncate"></p>
                        </div>
                    </div>
                    <button onclick="closeDetailModal()"
                        class="p-2 text-gray-400 transition-colors bg-gray-100 rounded-full hover:text-red-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="flex-1 p-6 space-y-6 overflow-y-auto md:p-8">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div class="space-y-4">
                        <div>
                            <label
                                class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-1">Branch
                                Office</label>
                            <p id="det_branch" class="text-sm font-bold text-gray-800"></p>
                        </div>
                        <div>
                            <label
                                class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-1">Registration
                                Date</label>
                            <p id="det_created" class="text-sm font-medium text-gray-600"></p>
                        </div>
                    </div>
                    <div>
                        <div>
                            <label
                                class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-1">Status</label>
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-600 text-xs font-bold">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Active Online
                            </span>
                        </div>
                    </div>
                    <div class="col-span-1 p-4 border border-gray-100 sm:col-span-2 bg-gray-50/50 rounded-2xl">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-2">Detailed
                            Location / Address</label>
                        <p id="det_location"
                            class="text-sm italic font-medium leading-relaxed text-gray-700 whitespace-normal"></p>
                    </div>
                </div>

                <div class="flex flex-col gap-3 pt-4 border-t sm:flex-row border-gray-50">
                    <button onclick="closeDetailModal()"
                        class="order-2 w-full px-4 py-3 text-sm font-bold text-gray-600 transition-colors bg-gray-100 rounded-2xl hover:bg-gray-200 sm:order-1">
                        Close Preview
                    </button>
                    <a id="det_vault_link" href="#"
                        class="order-1 w-full px-4 py-3 text-sm font-bold text-center text-white transition-all bg-blue-600 shadow-lg rounded-2xl hover:bg-blue-700 shadow-blue-100 sm:order-2">
                        Access Inventory Vault
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL CREATE --}}
    <div id="modal-create" class="{{ $modalBase }}">
        <div
            class="w-full max-w-lg overflow-hidden transition-all transform scale-100 bg-white shadow-2xl rounded-3xl max-h-[calc(100vh-2rem)] flex flex-col">
            <div class="flex-shrink-0 px-6 py-5 md:px-8 md:pt-8 md:pb-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-black text-gray-900">New Machine Site</h3>
                        <p class="mt-1 text-xs text-gray-500">Define location and machine identification</p>
                    </div>
                    <button onclick="closeCreateModal()"
                        class="p-2 text-gray-400 transition-colors bg-gray-100 rounded-full hover:text-red-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
            <form action="{{ route('site.store') }}" method="POST"
                class="flex-1 px-6 pb-6 space-y-5 overflow-y-auto md:px-8 md:pb-8">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block mb-1.5 text-xs font-black text-gray-700 uppercase tracking-widest">Site
                            Label</label>
                        <input type="text" name="machine_name" required placeholder="e.g. Area 51 - Maintenance"
                            class="w-full px-4 py-3 transition-all border-gray-200 outline-none bg-gray-50 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block mb-1.5 text-xs font-black text-gray-700 uppercase tracking-widest">Assign
                            Branch</label>
                        <select name="branch_id" required
                            class="w-full px-4 py-3 transition-all border-gray-200 outline-none appearance-none bg-gray-50 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500">
                            <option value="">Choose regional branch...</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1.5 text-xs font-black text-gray-700 uppercase tracking-widest">Physical
                            Location</label>
                        <textarea name="location" rows="3" placeholder="Full coordinates or address..." required
                            class="w-full px-4 py-3 transition-all border-gray-200 outline-none bg-gray-50 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500"></textarea>
                    </div>
                </div>
                <div class="flex flex-col gap-3 pt-4 border-t sm:flex-row border-gray-50">
                    <button type="button" onclick="closeCreateModal()"
                        class="order-2 w-full px-4 py-3 text-sm font-bold text-gray-500 transition-colors bg-white border border-gray-200 rounded-2xl hover:bg-gray-50 sm:order-1">Discard</button>
                    <button type="submit"
                        class="order-1 w-full px-4 py-3 text-sm font-bold text-white transition-all bg-blue-600 shadow-lg rounded-2xl hover:bg-blue-700 shadow-blue-100 sm:order-2">Register
                        Site</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL EDIT --}}
    <div id="modal-edit" class="{{ $modalBase }}">
        <div
            class="w-full max-w-lg overflow-hidden transition-all transform scale-100 bg-white shadow-2xl rounded-3xl max-h-[calc(100vh-2rem)] flex flex-col">
            <div class="flex-shrink-0 px-6 py-5 md:px-8 md:pt-8 md:pb-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-black text-gray-900">Update Machine Site</h3>
                        <p class="mt-1 text-xs text-gray-500">Modify regional site information and identification</p>
                    </div>
                    <button onclick="closeEditModal()"
                        class="p-2 text-gray-400 transition-colors bg-gray-100 rounded-full hover:text-red-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
            <form id="form-edit" method="POST" class="flex-1 px-6 pb-6 space-y-5 overflow-y-auto md:px-8 md:pb-8">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    <div>
                        <label class="block mb-1.5 text-xs font-black text-gray-700 uppercase tracking-widest">Site
                            Label</label>
                        <input type="text" id="edit_machine_name" name="machine_name" required
                            class="w-full px-4 py-3 transition-all border-gray-200 outline-none bg-gray-50 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block mb-1.5 text-xs font-black text-gray-700 uppercase tracking-widest">Branch
                            Assignment</label>
                        <div class="relative">
                            <select id="edit_branch_id" name="branch_id" required
                                class="w-full px-4 py-3 transition-all border-gray-200 outline-none appearance-none bg-gray-50 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500">
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option>
                                @endforeach
                            </select>
                            <div
                                class="absolute inset-y-0 right-0 flex items-center px-4 text-gray-400 pointer-events-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block mb-1.5 text-xs font-black text-gray-700 uppercase tracking-widest">Physical
                            Location</label>
                        <textarea id="edit_location" name="location" rows="3"
                            class="w-full px-4 py-3 transition-all border-gray-200 outline-none bg-gray-50 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500"></textarea>
                    </div>
                </div>
                <div class="flex flex-col gap-3 pt-4 border-t sm:flex-row border-gray-50">
                    <button type="button" onclick="closeEditModal()"
                        class="order-2 w-full px-4 py-3 text-sm font-bold text-gray-500 transition-colors bg-white border border-gray-200 rounded-2xl hover:bg-gray-50 sm:order-1">Cancel</button>
                    <button type="submit"
                        class="order-1 w-full px-4 py-3 text-sm font-bold text-white transition-all bg-blue-600 shadow-lg rounded-2xl hover:bg-blue-700 shadow-blue-100 sm:order-2">Update
                        Data</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL DELETE CONFIRMATION --}}
    <div id="modal-delete" class="{{ $modalBase }}">
        <div class="w-full max-w-sm overflow-hidden transition-all transform scale-100 bg-white shadow-2xl rounded-3xl">
            <div class="p-6 text-center md:p-8">
                <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 text-red-500 bg-red-50 rounded-2xl">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>

                <h3 class="text-xl font-black text-gray-900">Archive Site?</h3>
                <p class="mt-2 text-sm text-gray-500">
                    Are you sure you want to archive <span id="delete_site_name" class="font-bold text-gray-800"></span>?
                    This action can be undone by an administrator later.
                </p>

                <div class="flex flex-col gap-3 mt-8 sm:flex-row">
                    <button type="button" onclick="closeDeleteModal()"
                        class="order-2 w-full px-4 py-3 text-sm font-bold text-gray-500 transition-colors bg-gray-50 rounded-2xl hover:bg-gray-100 sm:order-1">
                        Cancel
                    </button>
                    <form id="form-delete" method="POST" class="order-1 w-full sm:order-2">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="w-full px-4 py-3 text-sm font-bold text-white transition-all bg-red-600 shadow-lg rounded-2xl hover:bg-red-700 shadow-red-100">
                            Yes, Archive
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- SCRIPTS (PERBAIKAN SYSTEM LOGIC & EVENT CLOSER) --}}
    <script>
        // === 1. MODAL DETAIL LOGIC ===
        function openDetailModal(site) {
            const modal = document.getElementById('modal-detail');

            document.getElementById('det_title').innerText = site.machine_name;
            document.getElementById('det_subtitle').innerText = `System ID: #ST-${String(site.id).padStart(4, '0')}`;
            document.getElementById('det_branch').innerText = site.branch ? site.branch.branch_name : 'Unassigned';
            document.getElementById('det_location').innerText = site.location || 'No address specified.';
            document.getElementById('det_created').innerText = new Date(site.created_at).toLocaleDateString('id-ID', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            document.getElementById('det_vault_link').href = `/spareparts/${site.slug}`;

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeDetailModal() {
            const modal = document.getElementById('modal-detail');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // === 2. MODAL CREATE LOGIC ===
        function openCreateModal() {
            const modal = document.getElementById('modal-create');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeCreateModal() {
            const modal = document.getElementById('modal-create');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // === 3. MODAL EDIT LOGIC ===
        function openEditModal(site) {
            const modal = document.getElementById('modal-edit');
            const form = document.getElementById('form-edit');

            form.action = `/sites/${site.id}`;

            document.getElementById('edit_machine_name').value = site.machine_name;
            document.getElementById('edit_branch_id').value = site.branch_id;
            document.getElementById('edit_location').value = site.location || '';

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeEditModal() {
            const modal = document.getElementById('modal-edit');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // === 4. MODAL DELETE LOGIC ===
        function openDeleteModal(site) {
            const modal = document.getElementById('modal-delete');
            const form = document.getElementById('form-delete');
            const siteNameDisplay = document.getElementById('delete_site_name');

            form.action = `/sites/${site.id}`;
            siteNameDisplay.innerText = site.machine_name;

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeDeleteModal() {
            const modal = document.getElementById('modal-delete');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // === 5. SINGLE GLOBAL CLICK LISTENER ===
        // Mengamankan penutupan overlay agar bekerja untuk semua modal secara konsisten
        window.onclick = function(event) {
            const modalDetail = document.getElementById('modal-detail');
            const modalCreate = document.getElementById('modal-create');
            const modalEdit = document.getElementById('modal-edit');
            const modalDelete = document.getElementById('modal-delete');

            if (event.target === modalDetail) closeDetailModal();
            if (event.target === modalCreate) closeCreateModal();
            if (event.target === modalEdit) closeEditModal();
            if (event.target === modalDelete) closeDeleteModal();
        }
    </script>
@endsection
