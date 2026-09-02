@extends('layout.master')

@section('title', 'Branch Management')

@section('content')
    <div class="w-full space-y-6">

        {{-- PAGE HEADER & STATS SUMMARY --}}
        <div
            class="flex flex-col justify-between gap-4 p-6 bg-white border shadow-xs md:flex-row md:items-center rounded-3xl border-slate-200/80">
            <div>
                <div
                    class="inline-flex items-center gap-2 px-3 py-1 mb-2 text-xs font-bold text-blue-700 border border-blue-100 rounded-full bg-blue-50">
                    <i class="fa-solid fa-building text-[10px]"></i> Business Locations
                </div>
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">Branch Management</h1>
                <p class="mt-1 text-xs font-medium sm:text-sm text-slate-500">Organize and monitor all business branch
                    locations from a centralized dashboard.</p>
            </div>

            @if (Auth::user()?->role === 'superadmin')
                <button id="openModal" type="button"
                    class="inline-flex items-center justify-center gap-2 px-5 py-3 text-xs sm:text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 active:scale-[0.98] transition-all rounded-xl shadow-lg shadow-blue-600/20 shrink-0 cursor-pointer">
                    <i class="text-sm fa-solid fa-plus"></i>
                    <span>Add New Branch</span>
                </button>
            @endif
        </div>

        {{-- MAIN TABLE CARD CONTAINER --}}
        <div class="overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">

            {{-- SEARCH & FILTER BAR --}}
            <div class="p-4 border-b sm:p-6 border-slate-100 bg-slate-50/50">
                <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">

                    {{-- Live Search Input --}}
                    <div class="relative w-full sm:w-80">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 pointer-events-none">
                            <i class="text-xs fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" name="search" id="branchSearchInput"
                            placeholder="Search branch name or code..." value="{{ request('search') }}" autocomplete="off"
                            class="w-full pl-10 pr-8 py-2.5 text-xs sm:text-sm font-medium text-slate-800 bg-white border border-slate-200 rounded-xl focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10 focus:outline-none transition-all placeholder:text-slate-400 shadow-2xs">

                        <button type="button" id="clearSearchBtn" onclick="clearSearch()"
                            class="{{ request('search') ? '' : 'hidden' }} absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
                            <i class="text-xs fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="flex items-center self-end gap-2 text-xs font-semibold text-slate-500 sm:self-center">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Live Data Sync</span>
                    </div>
                </div>
            </div>

            {{-- AJAX CONTAINER TABLE --}}
            <div id="table-container" class="overflow-x-auto min-h-[300px] transition-opacity duration-200">
                @include('branches.table')
            </div>

        </div>
    </div>

    {{-- ================= MODAL ADD BRANCH ================= --}}
    <div id="modal"
        class="fixed inset-0 z-50 flex items-center justify-center invisible p-4 transition-all duration-200 opacity-0 bg-slate-900/60 backdrop-blur-xs">

        {{-- MODAL BOX --}}
        <div id="modalBox"
            class="w-full max-w-lg overflow-hidden transition-all duration-300 transform scale-95 translate-y-4 bg-white border border-slate-100 shadow-2xl rounded-3xl max-h-[90vh] flex flex-col">

            {{-- MODAL HEADER --}}
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Create New Branch</h3>
                    <p class="text-xs font-medium text-slate-500">Fill in the details to register a new location.</p>
                </div>
                <button id="closeModal" type="button"
                    class="flex items-center justify-center w-8 h-8 transition-colors rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                    <i class="text-base fa-solid fa-xmark"></i>
                </button>
            </div>

            {{-- FORM --}}
            <form action="{{ route('branches.store') }}" method="POST" class="p-6 space-y-4 overflow-y-auto">
                @csrf

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Branch Name <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative flex items-center">
                            <span class="absolute left-3.5 text-slate-400 pointer-events-none text-xs">
                                <i class="fa-solid fa-building"></i>
                            </span>
                            <input type="text" name="branch_name" required
                                class="w-full pl-10 pr-3.5 py-2.5 text-xs sm:text-sm font-medium text-slate-800 border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10 focus:outline-none transition-all placeholder:text-slate-400"
                                placeholder="e.g. Jakarta HQ">
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Branch Code <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative flex items-center">
                            <span class="absolute left-3.5 text-slate-400 pointer-events-none text-xs">
                                <i class="fa-solid fa-hashtag"></i>
                            </span>
                            <input type="text" name="branch_code" required
                                class="w-full pl-10 pr-3.5 py-2.5 text-xs sm:text-sm uppercase font-bold text-slate-800 border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10 focus:outline-none transition-all placeholder:text-slate-400"
                                placeholder="e.g. JKT01">
                        </div>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Detailed Address</label>
                    <div class="relative">
                        <span class="absolute top-3 left-3.5 text-slate-400 pointer-events-none text-xs">
                            <i class="fa-solid fa-location-dot"></i>
                        </span>
                        <textarea name="branch_address" rows="3"
                            class="w-full pl-10 pr-3.5 py-2.5 text-xs sm:text-sm font-medium text-slate-800 border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10 focus:outline-none transition-all placeholder:text-slate-400"
                            placeholder="Street name, Building, City..."></textarea>
                    </div>
                </div>

                {{-- MODAL FOOTER --}}
                <div class="flex items-center justify-end gap-3 pt-4 mt-6 border-t border-slate-100">
                    <button type="button" id="cancelModal"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">
                        Discard
                    </button>

                    <button type="submit"
                        class="px-5 py-2.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 active:scale-[0.98] transition-all rounded-xl shadow-md shadow-blue-600/20">
                        <i class="mr-1.5 fa-solid fa-floppy-disk"></i> Confirm & Save
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Modal Handlers
        const modal = document.getElementById('modal');
        const modalBox = document.getElementById('modalBox');
        const openBtn = document.getElementById('openModal');
        const closeBtn = document.getElementById('closeModal');
        const cancelBtn = document.getElementById('cancelModal');

        function openModal() {
            if (!modal || !modalBox) return;
            modal.classList.remove('invisible');
            modal.classList.add('flex', 'opacity-100');
            setTimeout(() => {
                modalBox.classList.remove('scale-95', 'translate-y-4', 'opacity-0');
                modalBox.classList.add('scale-100', 'translate-y-0', 'opacity-100');
            }, 10);
            document.body.classList.add('overflow-hidden');
        }

        function closeModal() {
            if (!modal || !modalBox) return;
            modalBox.classList.add('scale-95', 'translate-y-4', 'opacity-0');
            modal.classList.remove('opacity-100');
            modal.classList.add('opacity-0');
            document.body.classList.remove('overflow-hidden');
            setTimeout(() => {
                modal.classList.add('invisible');
            }, 200);
        }

        if (openBtn) openBtn.onclick = openModal;
        [closeBtn, cancelBtn].forEach(btn => {
            if (btn) btn.onclick = closeModal;
        });

        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === "Escape") closeModal();
        });

        // Live Search AJAX
        const searchInput = document.getElementById('branchSearchInput');
        const tableContainer = document.getElementById('table-container');
        let delayTimer;

        function fetchBranchData(targetUrl = null) {
            if (!tableContainer) return;
            const query = searchInput ? searchInput.value.trim() : '';
            tableContainer.classList.add('opacity-50');

            let url = targetUrl ? new URL(targetUrl, window.location.origin) : new URL("{{ route('branches.index') }}",
                window.location.origin);
            if (query) url.searchParams.set('search', query);

            const clearBtn = document.getElementById('clearSearchBtn');
            if (clearBtn) clearBtn.classList.toggle('hidden', !query);

            fetch(url.href, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    tableContainer.innerHTML = html;
                    tableContainer.classList.remove('opacity-50');
                    window.history.pushState({}, '', url.href);
                })
                .catch(error => {
                    console.error('Error fetching branch search results:', error);
                    tableContainer.classList.remove('opacity-50');
                });
        }

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(delayTimer);
                delayTimer = setTimeout(() => {
                    fetchBranchData();
                }, 350);
            });
        }

        function clearSearch() {
            if (searchInput) {
                searchInput.value = '';
                fetchBranchData();
            }
        }
    </script>
@endpush
