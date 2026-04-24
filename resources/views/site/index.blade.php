@extends('layout.master')

@section('content')
    <div class="w-full px-6 py-8">

        {{-- BREADCRUMB & TITLES --}}
        <div class="flex flex-col gap-4 mb-8 md:flex-row md:items-end md:justify-between">
            <div>
                <nav class="flex mb-2 text-xs font-bold tracking-widest text-gray-400 uppercase">
                    <span class="transition-colors cursor-pointer hover:text-blue-600">Infrastructure</span>
                    <span class="mx-2">/</span>
                    <span class="text-blue-600">Machine Sites</span>
                </nav>
                <h1 class="text-3xl font-extrabold tracking-tight text-gray-900">Site Management</h1>
                <p class="mt-1 text-sm text-gray-500">Monitor inventory status and machine distribution across all regions.
                </p>
            </div>

            @if (auth()->user()->role === 'superadmin')
                <button onclick="openCreateModal()"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3 text-sm font-bold text-white transition-all bg-blue-600 shadow-xl rounded-xl hover:bg-blue-700 shadow-blue-200 active:scale-95 group">
                    <svg class="w-5 h-5 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Register New Site
                </button>
            @endif
        </div>

        {{-- STATS GRID --}}
        <div class="grid grid-cols-1 gap-6 mb-8 md:grid-cols-3">
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
                        <div class="p-3 bg-{{ $s['color'] }}-50 rounded-2xl text-{{ $s['color'] }}-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="{{ $s['icon'] }}" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold tracking-widest text-gray-400 uppercase">{{ $s['label'] }}</p>
                            <p class="mt-1 text-3xl font-black leading-none text-gray-900">{{ number_format($s['val']) }}
                            </p>
                        </div>
                    </div>
                    <div class="absolute -right-2 -bottom-2 opacity-5 text-{{ $s['color'] }}-600">
                        <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24">
                            <path d="{{ $s['icon'] }}" />
                        </svg>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- TABLE SECTION --}}
        <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-200 rounded-3xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/50">
                            <th class="px-8 py-5 text-xs font-bold tracking-widest text-gray-500 uppercase">Machine Identity
                            </th>
                            <th class="px-8 py-5 text-xs font-bold tracking-widest text-gray-500 uppercase">Location</th>
                            <th class="px-8 py-5 text-xs font-bold tracking-widest text-gray-500 uppercase">System Slug</th>
                            <th class="px-8 py-5 text-xs font-bold tracking-widest text-right text-gray-500 uppercase">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($sites as $site)
                            <tr class="transition-colors group hover:bg-gray-50/50">
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="flex items-center justify-center w-12 h-12 text-blue-600 transition-colors rounded-2xl bg-blue-50 group-hover:bg-blue-600 group-hover:text-white">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <span
                                                class="block text-base font-bold text-gray-900">{{ $site->machine_name }}</span>
                                            <span class="text-xs font-medium text-gray-400">ID:
                                                #{{ str_pad($site->id, 4, '0', STR_PAD_LEFT) }} • Registered
                                                {{ $site->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-5">
                                    <div
                                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-gray-100 text-gray-700 border border-gray-200">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                        <span
                                            class="text-xs font-bold">{{ $site->branch->branch_name ?? 'Unassigned' }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-5">
                                    <span
                                        class="px-3 py-1 font-mono text-xs text-blue-600 border border-blue-100 rounded-lg bg-blue-50">
                                        {{ $site->slug }}
                                    </span>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('sparepart.index', $site->slug) }}"
                                            class="flex items-center gap-2 px-4 py-2 text-xs font-bold text-gray-700 transition-all bg-white border border-gray-200 rounded-xl hover:bg-gray-900 hover:text-white hover:border-gray-900">
                                            Open Vault
                                        </a>

                                        @if (auth()->user()->role === 'superadmin')
                                            <div class="w-px h-8 mx-1 bg-gray-100"></div>
                                            <button onclick="openEditModal({{ json_encode($site) }})"
                                                class="p-2.5 text-gray-400 transition-colors rounded-xl hover:bg-blue-50 hover:text-blue-600">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2.5 2.5 0 113.536 3.536L12 14.232l-4 1 1-4 9.732-9.732z" />
                                                </svg>
                                            </button>

                                            <form action="{{ route('site.destroy', $site->id) }}" method="POST"
                                                class="inline" onsubmit="return confirm('Archive this site?')">
                                                @csrf @method('DELETE')
                                                {{-- <button type="submit"
                                                    class="p-2.5 text-gray-400 transition-colors rounded-xl hover:bg-red-50 hover:text-red-600">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button> --}}
                                                {{-- Ganti form delete lama dengan button ini --}}
                                                <button type="button" onclick="openDeleteModal({{ json_encode($site) }})"
                                                    class="p-2.5 text-gray-400 transition-colors rounded-xl hover:bg-red-50 hover:text-red-600">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-8 py-20 text-center">
                                    <div class="flex flex-col items-center max-w-xs mx-auto">
                                        <div class="p-4 mb-4 rounded-full bg-gray-50">
                                            <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-3m-3 0h-3m-3 0H4" />
                                            </svg>
                                        </div>
                                        <h3 class="text-sm font-bold text-gray-900">No sites found</h3>
                                        <p class="mt-1 text-xs text-gray-500">Start by registering your first machine site
                                            to the system.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- MODAL STYLING (SAME FOR BOTH) --}}
    @php $modalBase = "fixed inset-0 z-50 flex items-center justify-center hidden px-4 bg-gray-900/60 backdrop-blur-sm transition-all duration-300"; @endphp

    {{-- MODAL CREATE --}}
    <div id="modal-create" class="{{ $modalBase }}">
        <div class="w-full max-w-lg overflow-hidden transition-all transform scale-100 bg-white shadow-2xl rounded-3xl">
            <div class="px-8 pt-8 pb-4">
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
            <form action="{{ route('site.store') }}" method="POST" class="p-8 space-y-5">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block mb-1.5 text-xs font-black text-gray-700 uppercase tracking-widest">Site
                            Label</label>
                        <input type="text" name="machine_name" required placeholder="e.g. Area 51 - Maintenance"
                            class="w-full px-4 py-3 transition-all border-gray-200 outline-none bg-gray-50 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500">
                    </div>
                    <div class="col-span-2">
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
                </div>
                <div>
                    <label class="block mb-1.5 text-xs font-black text-gray-700 uppercase tracking-widest">Physical
                        Location</label>
                    <textarea name="location" rows="3" placeholder="Full coordinates or address..."
                        class="w-full px-4 py-3 transition-all border-gray-200 outline-none bg-gray-50 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500"></textarea>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeCreateModal()"
                        class="flex-1 px-4 py-3 text-sm font-bold text-gray-500 transition-colors bg-white border border-gray-200 rounded-2xl hover:bg-gray-50">Discard</button>
                    <button type="submit"
                        class="flex-1 px-4 py-3 text-sm font-bold text-white transition-all bg-blue-600 shadow-lg rounded-2xl hover:bg-blue-700 shadow-blue-100">Register
                        Site</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL EDIT --}}
    <div id="modal-edit" class="{{ $modalBase }}">
        <div class="w-full max-w-lg overflow-hidden transition-all transform scale-100 bg-white shadow-2xl rounded-3xl">
            <div class="px-8 pt-8 pb-4">
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
            <form id="form-edit" method="POST" class="p-8 space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block mb-1.5 text-xs font-black text-gray-700 uppercase tracking-widest">Site
                            Label</label>
                        <input type="text" id="edit_machine_name" name="machine_name" required
                            class="w-full px-4 py-3 transition-all border-gray-200 outline-none bg-gray-50 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500">
                    </div>
                    <div class="col-span-2">
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
                </div>
                <div>
                    <label class="block mb-1.5 text-xs font-black text-gray-700 uppercase tracking-widest">Physical
                        Location</label>
                    <textarea id="edit_location" name="location" rows="3"
                        class="w-full px-4 py-3 transition-all border-gray-200 outline-none bg-gray-50 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500"></textarea>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeEditModal()"
                        class="flex-1 px-4 py-3 text-sm font-bold text-gray-500 transition-colors bg-white border border-gray-200 rounded-2xl hover:bg-gray-50">Cancel</button>
                    <button type="submit"
                        class="flex-1 px-4 py-3 text-sm font-bold text-white transition-all bg-blue-600 shadow-lg rounded-2xl hover:bg-blue-700 shadow-blue-100">Update
                        Data</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL DELETE CONFIRMATION --}}
    <div id="modal-delete" class="{{ $modalBase }}">
        <div class="w-full max-w-sm overflow-hidden transition-all transform scale-100 bg-white shadow-2xl rounded-3xl">
            <div class="p-8 text-center">
                {{-- Icon Warning --}}
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

                <div class="flex gap-3 mt-8">
                    <button type="button" onclick="closeDeleteModal()"
                        class="flex-1 px-4 py-3 text-sm font-bold text-gray-500 transition-colors bg-gray-50 rounded-2xl hover:bg-gray-100">
                        Cancel
                    </button>
                    <form id="form-delete" method="POST" class="flex-1">
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

    <script>
        // Create Modal Logic
        function openCreateModal() {
            const modal = document.getElementById('modal-create');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.add('opacity-100');
            }, 10);
        }

        function closeCreateModal() {
            const modal = document.getElementById('modal-create');
            modal.classList.add('hidden');
        }

        // Edit Modal Logic
        function openEditModal(site) {
            const modal = document.getElementById('modal-edit');
            const form = document.getElementById('form-edit');

            // Set Form Action URL (Ganti 'site.update' dengan route yang sesuai)
            form.action = `/sites/${site.id}`;

            // Set Input Values
            document.getElementById('edit_machine_name').value = site.machine_name;
            document.getElementById('edit_branch_id').value = site.branch_id;
            document.getElementById('edit_location').value = site.location || '';

            // Show Modal
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.add('opacity-100');
            }, 10);
        }

        function closeEditModal() {
            const modal = document.getElementById('modal-edit');
            modal.classList.add('hidden');
        }

        // Close Modal on Overlay Click
        window.onclick = function(event) {
            const modalCreate = document.getElementById('modal-create');
            const modalEdit = document.getElementById('modal-edit');
            if (event.target == modalCreate) closeCreateModal();
            if (event.target == modalEdit) closeEditModal();
        }

        // Delete Modal Logic
        function openDeleteModal(site) {
            const modal = document.getElementById('modal-delete');
            const form = document.getElementById('form-delete');
            const siteNameDisplay = document.getElementById('delete_site_name');

            // Set Action URL
            form.action = `/sites/${site.id}`; // Sesuaikan dengan route destroy Anda

            // Set Nama Site di teks konfirmasi
            siteNameDisplay.innerText = site.machine_name;

            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.add('opacity-100');
            }, 10);
        }

        function closeDeleteModal() {
            const modal = document.getElementById('modal-delete');
            modal.classList.add('hidden');
        }

        // Update window.onclick agar mencakup modal delete
        window.onclick = function(event) {
            const modalCreate = document.getElementById('modal-create');
            const modalEdit = document.getElementById('modal-edit');
            const modalDelete = document.getElementById('modal-delete');

            if (event.target == modalCreate) closeCreateModal();
            if (event.target == modalEdit) closeEditModal();
            if (event.target == modalDelete) closeDeleteModal();
        }
    </script>
@endsection
