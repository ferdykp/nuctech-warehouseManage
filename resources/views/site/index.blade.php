@extends('layout.master')

@section('title', 'Site Management')

@section('content')
    <div class="w-full space-y-6">

        {{-- 1. HEADER CARD --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <nav class="flex items-center gap-2 mb-1.5 text-xs font-bold tracking-wider text-slate-400 uppercase">
                        <span class="transition-colors cursor-pointer hover:text-blue-600">Infrastructure</span>
                        <i class="fa-solid fa-chevron-right text-[9px]"></i>
                        <span class="font-extrabold text-blue-600">Machine Sites</span>
                    </nav>
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                        Site Management
                    </h1>
                    <p class="mt-1 text-xs font-semibold sm:text-sm text-slate-500">
                        Monitor inventory status and machine distribution across all regions.
                    </p>
                </div>

                @if (auth()->user()?->role === 'superadmin')
                    <div class="flex items-center gap-3 shrink-0">
                        <button type="button" onclick="openCreateModal()"
                            class="flex items-center justify-center gap-2 px-5 py-3 text-xs font-bold text-white transition-all bg-blue-600 shadow-md cursor-pointer sm:text-sm hover:bg-blue-700 rounded-xl shadow-blue-600/20 active:scale-95 group shrink-0">
                            <i class="text-xs transition-transform fa-solid fa-plus group-hover:rotate-90"></i>
                            <span>Register New Site</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>

        {{-- 2. STATS GRID --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @php
                $stats = [
                    [
                        'label' => 'Registered Machines',
                        'val' => $sites->count(),
                        'color' => 'blue',
                        'icon' => 'fa-solid fa-server',
                    ],
                    [
                        'label' => 'Operational Branches',
                        'val' => $sites->unique('branch_id')->count(),
                        'color' => 'emerald',
                        'icon' => 'fa-solid fa-building',
                    ],
                    [
                        'label' => 'Stock Volume',
                        'val' => \App\Models\SparepartStock::sum('qty'),
                        'color' => 'amber',
                        'icon' => 'fa-solid fa-boxes-stacked',
                    ],
                ];
            @endphp

            @foreach ($stats as $s)
                <div
                    class="p-5 transition-all bg-white border shadow-xs sm:p-6 border-slate-200/80 rounded-3xl hover:shadow-md">
                    <div class="flex items-center gap-4">
                        <div
                            class="flex items-center justify-center w-12 h-12 rounded-2xl bg-{{ $s['color'] }}-50 text-{{ $s['color'] }}-600 border border-{{ $s['color'] }}-100 shrink-0 text-lg">
                            <i class="{{ $s['icon'] }}"></i>
                        </div>
                        <div>
                            <p class="text-[10px] sm:text-[11px] font-extrabold tracking-widest text-slate-400 uppercase">
                                {{ $s['label'] }}
                            </p>
                            <p class="mt-0.5 text-2xl font-black text-slate-900">{{ number_format($s['val']) }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- 3. MAIN TABLE CARD CONTAINER --}}
        <div class="overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[700px]">
                    <thead>
                        <tr
                            class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 bg-slate-50 border-b border-slate-100">
                            <th scope="col" class="px-6 py-4">Machine Identity</th>
                            <th scope="col" class="px-6 py-4">Branch</th>
                            <th scope="col" class="px-6 py-4">Location / Address</th>
                            <th scope="col" class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs font-medium divide-y divide-slate-100 text-slate-700">
                        @forelse($sites as $site)
                            <tr class="transition-colors hover:bg-slate-50/60">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3.5">
                                        <div
                                            class="flex items-center justify-center w-10 h-10 text-blue-600 transition-colors border shrink-0 rounded-2xl bg-blue-50 border-blue-100/80">
                                            <i class="text-sm fa-solid fa-microchip"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="block text-sm font-extrabold truncate text-slate-900">
                                                {{ $site->machine_name }}
                                            </span>
                                            <span class="block text-[11px] text-slate-400 font-semibold truncate mt-0.5">
                                                ID: #{{ str_pad($site->id, 4, '0', STR_PAD_LEFT) }} &bull;
                                                {{ $site->created_at ? $site->created_at->diffForHumans() : '-' }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <div
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200/80 text-[10px] font-extrabold uppercase tracking-wide">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span>{{ $site->branch->branch_name ?? 'Unassigned' }}</span>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex flex-col max-w-[260px]">
                                        <div class="flex items-center gap-1.5 text-slate-800 mb-0.5 font-bold">
                                            <i class="text-xs fa-solid fa-location-dot text-slate-400"></i>
                                            <span class="truncate">{{ $site->location ?? '-' }}</span>
                                        </div>
                                        <span class="text-[11px] text-slate-400 font-medium line-clamp-1">
                                            {{ $site->address ?? 'No detailed address provided' }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        {{-- VIEW DETAIL --}}
                                        <button type="button"
                                            onclick="openDetailModal({{ json_encode($site->load('branch'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }})"
                                            class="flex items-center justify-center w-8 h-8 transition-colors cursor-pointer rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100"
                                            title="View Details">
                                            <i class="text-xs fa-solid fa-eye"></i>
                                        </button>

                                        {{-- OPEN VAULT --}}
                                        <a href="{{ route('sparepart.index', $site->slug ?? $site->id) }}"
                                            class="inline-flex items-center px-3 py-1.5 text-xs font-extrabold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-900 hover:text-white transition-all active:scale-95 shrink-0 shadow-2xs">
                                            Open Vault
                                        </a>

                                        @if (auth()->user()?->role === 'superadmin')
                                            <div class="w-px h-4 mx-1 bg-slate-200"></div>

                                            {{-- EDIT --}}
                                            <button type="button"
                                                onclick="openEditModal({{ json_encode($site, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }})"
                                                class="flex items-center justify-center w-8 h-8 text-blue-600 transition-all border border-blue-100 cursor-pointer rounded-xl bg-blue-50 hover:bg-blue-600 hover:text-white active:scale-95"
                                                title="Edit Site">
                                                <i class="text-xs fa-solid fa-pen-to-square"></i>
                                            </button>

                                            {{-- DELETE --}}
                                            <button type="button"
                                                onclick="openDeleteModal({{ json_encode($site, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }})"
                                                class="flex items-center justify-center w-8 h-8 transition-all border cursor-pointer rounded-xl text-rose-600 bg-rose-50 border-rose-100 hover:bg-rose-600 hover:text-white active:scale-95"
                                                title="Delete Site">
                                                <i class="text-xs fa-solid fa-trash-can"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-12 text-center text-slate-400">
                                    <div
                                        class="flex items-center justify-center w-12 h-12 mx-auto mb-3 text-xl rounded-2xl bg-slate-100 text-slate-400">
                                        <i class="fa-solid fa-server"></i>
                                    </div>
                                    <p class="text-sm font-bold text-slate-800">No Machine Sites Registered Yet</p>
                                    <p class="mt-1 text-xs text-slate-400">Register a new site to start tracking inventory.
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t sm:p-6 border-slate-100 bg-slate-50/30">
                {{ $sites->links() }}
            </div>
        </div>
    </div>

    {{-- BASE MODAL STYLING --}}
    @php $modalBase = "fixed inset-0 z-50 flex items-center justify-center hidden p-4 bg-slate-900/60 backdrop-blur-xs transition-all duration-300"; @endphp

    {{-- MODAL DETAIL --}}
    <div id="modal-detail" onclick="if(event.target===this) closeDetailModal()" class="{{ $modalBase }}">
        <div
            class="w-full max-w-xl overflow-hidden bg-white border border-slate-100 shadow-2xl rounded-3xl max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 bg-slate-50/50 shrink-0">
                <div class="flex items-center gap-3">
                    <div
                        class="flex items-center justify-center w-10 h-10 text-blue-600 border border-blue-100 rounded-2xl bg-blue-50">
                        <i class="fa-solid fa-circle-info"></i>
                    </div>
                    <div>
                        <h3 id="det_title" class="text-base font-extrabold truncate text-slate-900">Site Information</h3>
                        <p id="det_subtitle"
                            class="text-[10px] font-extrabold tracking-widest text-slate-400 uppercase truncate"></p>
                    </div>
                </div>
                <button type="button" onclick="closeDetailModal()"
                    class="flex items-center justify-center w-8 h-8 transition-colors rounded-lg cursor-pointer text-slate-400 hover:text-slate-600 bg-slate-100 hover:bg-slate-200">&times;</button>
            </div>

            <div class="p-6 space-y-5 overflow-y-auto text-xs sm:text-sm">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="p-3.5 border border-slate-200/80 bg-slate-50/50 rounded-2xl">
                        <label class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-1">Branch
                            Office</label>
                        <p id="det_branch" class="font-bold text-slate-800"></p>
                    </div>
                    <div class="p-3.5 border border-slate-200/80 bg-slate-50/50 rounded-2xl">
                        <label
                            class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-1">Registration
                            Date</label>
                        <p id="det_created" class="font-semibold text-slate-600"></p>
                    </div>
                    <div class="sm:col-span-2 p-3.5 border border-slate-200/80 bg-slate-50/50 rounded-2xl">
                        <label
                            class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-1">Detailed
                            Address</label>
                        <p id="det_location" class="font-medium leading-relaxed text-slate-700"></p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50/50 shrink-0">
                <button type="button" onclick="closeDetailModal()"
                    class="px-4 py-2.5 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-100 active:scale-95 shadow-2xs cursor-pointer">Close</button>
                <a id="det_vault_link" href="#"
                    class="px-5 py-2.5 text-xs font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-md shadow-blue-600/20 active:scale-95 transition-all">Access
                    Vault</a>
            </div>
        </div>
    </div>

    {{-- MODAL CREATE --}}
    <div id="modal-create" onclick="if(event.target===this) closeCreateModal()" class="{{ $modalBase }}">
        <div
            class="w-full max-w-lg overflow-hidden bg-white border border-slate-100 shadow-2xl rounded-3xl max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 bg-slate-50/50 shrink-0">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">New Machine Site</h3>
                    <p class="text-xs font-medium text-slate-500">Define location and machine identification.</p>
                </div>
                <button type="button" onclick="closeCreateModal()"
                    class="flex items-center justify-center w-8 h-8 transition-colors rounded-lg cursor-pointer text-slate-400 hover:text-slate-600 bg-slate-100 hover:bg-slate-200">&times;</button>
            </div>
            <form action="{{ route('site.store') }}" method="POST" class="p-6 space-y-4 overflow-y-auto">
                @csrf
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Site Label <span
                            class="text-rose-500">*</span></label>
                    <input type="text" name="machine_name" required placeholder="e.g. Area 51 - Maintenance"
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 placeholder-slate-400">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Assign Branch <span
                            class="text-rose-500">*</span></label>
                    <select name="branch_id" required
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-bold border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 cursor-pointer">
                        <option value="">Choose regional branch...</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Physical Location <span
                            class="text-rose-500">*</span></label>
                    <textarea name="location" rows="3" placeholder="Full coordinates or address..." required
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 placeholder-slate-400"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeCreateModal()"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors cursor-pointer">Discard</button>
                    <button type="submit"
                        class="px-6 py-2.5 text-xs font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-md shadow-blue-600/20 active:scale-95 transition-all cursor-pointer">Register
                        Site</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL EDIT --}}
    <div id="modal-edit" onclick="if(event.target===this) closeEditModal()" class="{{ $modalBase }}">
        <div
            class="w-full max-w-lg overflow-hidden bg-white border border-slate-100 shadow-2xl rounded-3xl max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 bg-slate-50/50 shrink-0">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Update Machine Site</h3>
                    <p class="text-xs font-medium text-slate-500">Modify regional site information.</p>
                </div>
                <button type="button" onclick="closeEditModal()"
                    class="flex items-center justify-center w-8 h-8 transition-colors rounded-lg cursor-pointer text-slate-400 hover:text-slate-600 bg-slate-100 hover:bg-slate-200">&times;</button>
            </div>
            <form id="form-edit" method="POST" class="p-6 space-y-4 overflow-y-auto">
                @csrf
                @method('PUT')
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Site Label <span
                            class="text-rose-500">*</span></label>
                    <input type="text" id="edit_machine_name" name="machine_name" required
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Branch Assignment <span
                            class="text-rose-500">*</span></label>
                    <select id="edit_branch_id" name="branch_id" required
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-bold border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 cursor-pointer">
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Physical
                        Location</label>
                    <textarea id="edit_location" name="location" rows="3"
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeEditModal()"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors cursor-pointer">Cancel</button>
                    <button type="submit"
                        class="px-6 py-2.5 text-xs font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-md shadow-blue-600/20 active:scale-95 transition-all cursor-pointer">Update
                        Data</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL DELETE --}}
    <div id="modal-delete" onclick="if(event.target===this) closeDeleteModal()" class="{{ $modalBase }}">
        <div
            class="w-full max-w-sm p-6 overflow-hidden text-center bg-white border shadow-2xl border-slate-100 rounded-3xl">
            <div
                class="flex items-center justify-center w-12 h-12 mx-auto mb-3 text-xl border text-rose-600 bg-rose-50 border-rose-100 rounded-2xl">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h3 class="text-base font-extrabold text-slate-900">Delete Site Location?</h3>
            <p class="mt-1.5 text-xs font-medium text-slate-500 leading-relaxed">
                Are you sure you want to delete <strong id="delete_site_name" class="text-slate-900"></strong>?
                <span class="block mt-1 font-bold text-rose-600">This action cannot be undone.</span>
            </p>

            <div class="flex items-center justify-end gap-2.5 mt-6 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeDeleteModal()"
                    class="w-full py-2.5 text-xs font-bold text-slate-700 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors cursor-pointer">Cancel</button>
                <form id="form-delete" method="POST" class="w-full">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="w-full py-2.5 text-xs font-bold text-white bg-rose-600 rounded-xl hover:bg-rose-700 shadow-md shadow-rose-600/20 active:scale-95 transition-all cursor-pointer">Delete</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function openDetailModal(site) {
            const modal = document.getElementById('modal-detail');
            if (!modal) return;
            document.getElementById('det_title').innerText = site.machine_name || '-';
            document.getElementById('det_subtitle').innerText = `System ID: #ST-${String(site.id).padStart(4, '0')}`;
            document.getElementById('det_branch').innerText = site.branch ? site.branch.branch_name : 'Unassigned';
            document.getElementById('det_location').innerText = site.location || site.address || 'No address specified.';

            const createdDate = site.created_at ? new Date(site.created_at) : null;
            document.getElementById('det_created').innerText = createdDate && !isNaN(createdDate) ?
                createdDate.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                }) :
                '-';

            document.getElementById('det_vault_link').href = `/spareparts/${site.slug || site.id}`;

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeDetailModal() {
            const modal = document.getElementById('modal-detail');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
            document.body.classList.remove('overflow-hidden');
        }

        function openCreateModal() {
            const modal = document.getElementById('modal-create');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
            document.body.classList.add('overflow-hidden');
        }

        function closeCreateModal() {
            const modal = document.getElementById('modal-create');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
            document.body.classList.remove('overflow-hidden');
        }

        function openEditModal(site) {
            const modal = document.getElementById('modal-edit');
            const form = document.getElementById('form-edit');
            if (!modal || !form) return;

            form.action = `/sites/${site.id}`;
            document.getElementById('edit_machine_name').value = site.machine_name || '';
            document.getElementById('edit_branch_id').value = site.branch_id || '';
            document.getElementById('edit_location').value = site.location || site.address || '';

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeEditModal() {
            const modal = document.getElementById('modal-edit');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
            document.body.classList.remove('overflow-hidden');
        }

        function openDeleteModal(site) {
            const modal = document.getElementById('modal-delete');
            const form = document.getElementById('form-delete');
            if (!modal || !form) return;

            form.action = `/sites/${site.id}`;
            document.getElementById('delete_site_name').innerText = site.machine_name || 'this site';

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeDeleteModal() {
            const modal = document.getElementById('modal-delete');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
            document.body.classList.remove('overflow-hidden');
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeDetailModal();
                    closeCreateModal();
                    closeEditModal();
                    closeDeleteModal();
                }
            });
        });
    </script>
@endpush
