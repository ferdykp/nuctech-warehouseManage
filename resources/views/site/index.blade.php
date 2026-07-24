@extends('layout.master')

@section('content')
    <div class="w-full space-y-6">

        {{-- BREADCRUMB & TITLES --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <nav class="flex mb-1.5 text-xs font-bold tracking-widest text-slate-400 uppercase">
                    <span class="transition-colors cursor-pointer hover:text-blue-600">Infrastructure</span>
                    <span class="mx-2">/</span>
                    <span class="text-blue-600">Machine Sites</span>
                </nav>
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">Site Management</h1>
                <p class="mt-0.5 text-xs sm:text-sm font-medium text-slate-500">Monitor inventory status and machine
                    distribution across all regions.</p>
            </div>

            @if (auth()->user()->role === 'superadmin')
                <button onclick="openCreateModal()"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-xs sm:text-sm font-bold text-white transition-all bg-blue-600 shadow-md shadow-blue-600/20 rounded-xl hover:bg-blue-700 active:scale-95 group shrink-0">
                    <svg class="w-4 h-4 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Register New Site</span>
                </button>
            @endif
        </div>

        {{-- STATS GRID --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
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
                        'color' => 'amber',
                        'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                    ],
                ];
            @endphp

            @foreach ($stats as $s)
                <div
                    class="relative p-5 overflow-hidden transition-all bg-white border shadow-sm border-slate-100 rounded-2xl hover:shadow-md">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-{{ $s['color'] }}-50 rounded-xl text-{{ $s['color'] }}-600 shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="{{ $s['icon'] }}" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[10px] sm:text-[11px] font-bold tracking-widest text-slate-400 uppercase">
                                {{ $s['label'] }}</p>
                            <p class="mt-0.5 text-2xl font-black text-slate-800">{{ number_format($s['val']) }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- TABLE SECTION --}}
        <div class="overflow-hidden bg-white shadow-sm ring-1 ring-slate-200/80 rounded-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[700px]">
                    <thead>
                        <tr
                            class="border-b border-slate-200/80 bg-slate-100/70 text-[11px] font-extrabold tracking-wider text-slate-500 uppercase">
                            <th class="px-6 py-3.5">Machine Identity</th>
                            <th class="px-6 py-3.5">Branch</th>
                            <th class="px-6 py-3.5">Location / Address</th>
                            <th class="px-6 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs font-medium divide-y divide-slate-100 sm:text-sm text-slate-700">
                        @forelse($sites as $site)
                            <tr class="transition-colors hover:bg-slate-50/80">
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-3.5">
                                        <div
                                            class="flex items-center justify-center w-10 h-10 text-blue-600 transition-colors shrink-0 rounded-xl bg-blue-50">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <span
                                                class="block font-bold truncate text-slate-800">{{ $site->machine_name }}</span>
                                            <span class="block text-[11px] text-slate-400 font-medium truncate">
                                                ID: #{{ str_pad($site->id, 4, '0', STR_PAD_LEFT) }} &bull;
                                                {{ $site->created_at->diffForHumans() }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-3.5">
                                    <div
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200/60 text-[11px] font-bold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span
                                            class="tracking-wider uppercase">{{ $site->branch->branch_name ?? 'Unassigned' }}</span>
                                    </div>
                                </td>

                                <td class="px-6 py-3.5">
                                    <div class="flex flex-col max-w-[260px]">
                                        <div class="flex items-center gap-1 text-slate-800 mb-0.5 font-semibold">
                                            <svg class="shrink-0 w-3.5 h-3.5 text-slate-400" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <span class="truncate">{{ $site->location }}</span>
                                        </div>
                                        <span class="text-[11px] text-slate-400 line-clamp-1 font-normal">
                                            {{ $site->address ?? 'No detailed address provided' }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-6 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button onclick="openDetailModal({{ json_encode($site->load('branch')) }})"
                                            class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors"
                                            title="View Details">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <a href="{{ route('sparepart.index', $site->slug) }}"
                                            class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-900 hover:text-white transition-all active:scale-95 shrink-0">
                                            Open Vault
                                        </a>

                                        @if (auth()->user()->role === 'superadmin')
                                            <div class="w-px h-5 mx-1 bg-slate-200"></div>
                                            <button onclick="openEditModal({{ json_encode($site) }})"
                                                class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2.5 2.5 0 113.536 3.536L12 14.232l-4 1 1-4 9.732-9.732z" />
                                                </svg>
                                            </button>

                                            <button type="button" onclick="openDeleteModal({{ json_encode($site) }})"
                                                class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
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
                                <td colspan="4" class="p-10 text-center text-slate-400">
                                    <i class="block mb-2 text-3xl opacity-50 fa-solid fa-server"></i>
                                    <p class="text-sm font-bold text-slate-700">No machine sites registered yet</p>
                                </td>
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

    {{-- BASE MODAL STYLING --}}
    @php $modalBase = "fixed inset-0 z-50 flex items-center justify-center hidden p-3 sm:p-4 bg-slate-900/60 backdrop-blur-xs transition-all duration-300"; @endphp

    {{-- MODAL DETAIL --}}
    <div id="modal-detail" class="{{ $modalBase }}">
        <div
            class="w-full max-w-xl overflow-hidden bg-white shadow-2xl rounded-2xl sm:rounded-3xl max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 text-blue-600 rounded-xl bg-blue-50">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 id="det_title" class="text-base font-extrabold truncate text-slate-900">Site Information</h3>
                        <p id="det_subtitle"
                            class="text-[10px] font-bold tracking-widest text-slate-400 uppercase truncate"></p>
                    </div>
                </div>
                <button onclick="closeDetailModal()"
                    class="p-2 rounded-full text-slate-400 hover:text-slate-600 bg-slate-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-6 space-y-5 overflow-y-auto text-xs sm:text-sm">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Branch
                            Office</label>
                        <p id="det_branch" class="font-bold text-slate-800"></p>
                    </div>
                    <div>
                        <label
                            class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Registration
                            Date</label>
                        <p id="det_created" class="font-medium text-slate-600"></p>
                    </div>
                    <div class="sm:col-span-2 p-3.5 border border-slate-200/80 bg-slate-50 rounded-xl">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Detailed
                            Address</label>
                        <p id="det_location" class="font-medium leading-relaxed text-slate-700"></p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                    <button onclick="closeDetailModal()"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200">Close</button>
                    <a id="det_vault_link" href="#"
                        class="px-5 py-2.5 text-xs font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-md shadow-blue-600/20">Access
                        Vault</a>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL CREATE --}}
    <div id="modal-create" class="{{ $modalBase }}">
        <div
            class="w-full max-w-lg overflow-hidden bg-white shadow-2xl rounded-2xl sm:rounded-3xl max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 shrink-0">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">New Machine Site</h3>
                    <p class="text-xs text-slate-500">Define location and machine identification.</p>
                </div>
                <button onclick="closeCreateModal()"
                    class="p-2 rounded-full text-slate-400 hover:text-slate-600 bg-slate-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form action="{{ route('site.store') }}" method="POST" class="p-6 space-y-4 overflow-y-auto">
                @csrf
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Site Label</label>
                    <input type="text" name="machine_name" required placeholder="e.g. Area 51 - Maintenance"
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Assign Branch</label>
                    <select name="branch_id" required
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white">
                        <option value="">Choose regional branch...</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Physical
                        Location</label>
                    <textarea name="location" rows="3" placeholder="Full coordinates or address..." required
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeCreateModal()"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50">Discard</button>
                    <button type="submit"
                        class="px-6 py-2.5 text-xs font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-md shadow-blue-600/20 active:scale-95">Register
                        Site</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL EDIT --}}
    <div id="modal-edit" class="{{ $modalBase }}">
        <div
            class="w-full max-w-lg overflow-hidden bg-white shadow-2xl rounded-2xl sm:rounded-3xl max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 shrink-0">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Update Machine Site</h3>
                    <p class="text-xs text-slate-500">Modify regional site information.</p>
                </div>
                <button onclick="closeEditModal()"
                    class="p-2 rounded-full text-slate-400 hover:text-slate-600 bg-slate-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="form-edit" method="POST" class="p-6 space-y-4 overflow-y-auto">
                @csrf
                @method('PUT')
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Site Label</label>
                    <input type="text" id="edit_machine_name" name="machine_name" required
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Branch
                        Assignment</label>
                    <select id="edit_branch_id" name="branch_id" required
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white">
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Physical
                        Location</label>
                    <textarea id="edit_location" name="location" rows="3"
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeEditModal()"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50">Cancel</button>
                    <button type="submit"
                        class="px-6 py-2.5 text-xs font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-md shadow-blue-600/20 active:scale-95">Update
                        Data</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL DELETE --}}
    <div id="modal-delete" class="{{ $modalBase }}">
        <div class="w-full max-w-sm p-6 overflow-hidden text-center bg-white shadow-2xl rounded-2xl">
            <div class="flex items-center justify-center w-12 h-12 mx-auto mb-3 text-rose-600 bg-rose-100 rounded-xl">
                <i class="text-lg fa-solid fa-triangle-exclamation"></i>
            </div>
            <h3 class="text-base font-extrabold text-slate-800">Delete Site Location?</h3>
            <p class="mt-1.5 text-xs text-slate-500 leading-relaxed">
                Are you sure you want to delete <strong id="delete_site_name" class="text-slate-800"></strong>?
                <span class="block mt-1 font-semibold text-rose-600">This action cannot be undone.</span>
            </p>

            <div class="flex items-center justify-end gap-2.5 mt-6 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeDeleteModal()"
                    class="w-full py-2.5 text-xs font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200">Cancel</button>
                <form id="form-delete" method="POST" class="w-full">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="w-full py-2.5 text-xs font-bold text-white bg-rose-600 rounded-xl hover:bg-rose-700 shadow-md shadow-rose-600/20 active:scale-95">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openDetailModal(site) {
            const modal = document.getElementById('modal-detail');
            document.getElementById('det_title').innerText = site.machine_name;
            document.getElementById('det_subtitle').innerText = `System ID: #ST-${String(site.id).padStart(4, '0')}`;
            document.getElementById('det_branch').innerText = site.branch ? site.branch.branch_name : 'Unassigned';
            document.getElementById('det_location').innerText = site.location || 'No address specified.';
            document.getElementById('det_created').innerText = new Date(site.created_at).toLocaleDateString('en-US', {
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

        function openDeleteModal(site) {
            const modal = document.getElementById('modal-delete');
            const form = document.getElementById('form-delete');
            form.action = `/sites/${site.id}`;
            document.getElementById('delete_site_name').innerText = site.machine_name;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeDeleteModal() {
            const modal = document.getElementById('modal-delete');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        window.onclick = function(event) {
            if (event.target === document.getElementById('modal-detail')) closeDetailModal();
            if (event.target === document.getElementById('modal-create')) closeCreateModal();
            if (event.target === document.getElementById('modal-edit')) closeEditModal();
            if (event.target === document.getElementById('modal-delete')) closeDeleteModal();
        }
    </script>
@endsection
