@extends('layout.master')

@section('title', 'Branch Management')

@section('content')
    <div class="w-full space-y-6">

        {{-- PAGE HEADER --}}
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">Branch Management</h1>
                <p class="text-xs sm:text-sm font-medium text-slate-500 mt-0.5">Organize and monitor all business branch
                    locations from one place.</p>
            </div>
            @if (Auth::user()->role === 'superadmin')
                <button id="openModal"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-xs sm:text-sm font-bold text-white transition-all bg-blue-600 rounded-xl hover:bg-blue-700 shadow-md shadow-blue-600/20 active:scale-95 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Add New Branch</span>
                </button>
            @endif
        </div>

        {{-- TABLE CARD CONTAINER --}}
        <div class="overflow-hidden bg-white shadow-sm ring-1 ring-slate-200/80 rounded-2xl">

            {{-- SEARCH HEADER --}}
            <div class="p-4 border-b sm:p-6 border-slate-100 bg-slate-50/50">
                <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                    <div class="relative w-full sm:w-80">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" name="search" id="search" placeholder="Search branch name or code..."
                            value="{{ request('search') }}"
                            class="block w-full py-2.5 pl-10 pr-3.5 text-xs sm:text-sm border-slate-200 rounded-xl bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none border shadow-2xs">
                    </div>

                    <div class="self-end text-xs font-semibold text-slate-400 sm:self-center">
                        Live Data View
                    </div>
                </div>
            </div>

            {{-- AJAX CONTAINER TABLE --}}
            <div id="table-container" class="overflow-x-auto bg-white">
                @include('branches.table')
            </div>

        </div>
    </div>

    {{-- ================= MODAL ADD BRANCH ================= --}}
    <div id="modal"
        class="fixed inset-0 z-50 flex items-center justify-center invisible p-3 transition-all duration-300 opacity-0 sm:p-4 bg-slate-900/60 backdrop-blur-xs">

        {{-- MODAL BOX --}}
        <div id="modalBox"
            class="w-full max-w-lg overflow-hidden transition-all duration-300 transform scale-95 translate-y-8 bg-white shadow-2xl rounded-2xl sm:rounded-3xl max-h-[90vh] flex flex-col">

            {{-- MODAL HEADER --}}
            <div class="relative px-6 pt-6 pb-4 border-b sm:px-8 sm:pt-8 border-slate-100">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-extrabold sm:text-xl text-slate-900">Create New Branch</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Fill in the details to register a new location.</p>
                    </div>
                    <button id="closeModal" type="button"
                        class="p-2 transition-colors rounded-full text-slate-400 bg-slate-100 hover:bg-rose-50 hover:text-rose-500">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- FORM --}}
            <form action="{{ route('branches.store') }}" method="POST" class="p-6 space-y-4 overflow-y-auto sm:p-8">
                @csrf

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-1 space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Branch Name</label>
                        <input type="text" name="branch_name" required
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white"
                            placeholder="e.g. Jakarta HQ">
                    </div>

                    <div class="sm:col-span-1 space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Branch Code</label>
                        <input type="text" name="branch_code" required
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm uppercase border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white"
                            placeholder="e.g. JKT01">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Detailed Address</label>
                    <textarea name="address" rows="3"
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white"
                        placeholder="Street name, Building, City..."></textarea>
                </div>

                {{-- MODAL FOOTER --}}
                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100 mt-6">
                    <button type="button" id="cancelModal"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 transition-colors bg-white border border-slate-200 rounded-xl hover:bg-slate-50">
                        Discard
                    </button>

                    <button type="submit"
                        class="px-6 py-2.5 text-xs font-bold text-white transition-all bg-blue-600 rounded-xl hover:bg-blue-700 shadow-md shadow-blue-600/20 active:scale-95">
                        Confirm & Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('modal');
        const modalBox = document.getElementById('modalBox');
        const openBtn = document.getElementById('openModal');
        const closeBtn = document.getElementById('closeModal');
        const cancelBtn = document.getElementById('cancelModal');

        function openModal() {
            modal.classList.remove('invisible');
            modal.classList.add('flex', 'opacity-100');
            setTimeout(() => {
                modalBox.classList.remove('scale-95', 'translate-y-8', 'opacity-0');
                modalBox.classList.add('scale-100', 'translate-y-0', 'opacity-100');
            }, 10);
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modalBox.classList.add('scale-95', 'translate-y-8', 'opacity-0');
            modal.classList.remove('opacity-100');
            modal.classList.add('opacity-0');
            document.body.style.overflow = 'auto';
            setTimeout(() => {
                modal.classList.add('invisible');
            }, 300);
        }

        if (openBtn) openBtn.onclick = openModal;
        [closeBtn, cancelBtn].forEach(btn => {
            if (btn) btn.onclick = closeModal;
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === "Escape") closeModal();
        });
    </script>

    <script>
        // --- LIVE SEARCH AJAX ---
        const searchInput = document.getElementById('search');
        const tableContainer = document.getElementById('table-container');
        let delayTimer;

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(delayTimer);
                delayTimer = setTimeout(() => {
                    const query = searchInput.value;
                    fetch(`{{ route('branches.index') }}?search=${encodeURIComponent(query)}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => response.text())
                        .then(html => {
                            tableContainer.innerHTML = html;
                        })
                        .catch(error => console.error('Error fetching search results:', error));
                }, 300);
            });
        }
    </script>
@endsection
