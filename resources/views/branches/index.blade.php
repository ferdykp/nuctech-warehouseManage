@extends('layout.master')

@section('content')
    <div class="w-full px-6 py-8">

        <div class="flex flex-col gap-2 mb-6">
            <h1 class="text-3xl font-extrabold tracking-tight text-gray-900">Branch Management</h1>
            <p class="text-sm text-gray-500">Organize and monitor all business branch locations from one place.</p>
        </div>

        <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-200 rounded-2xl">

            <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

                    <div class="relative w-full md:w-80">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        {{-- Ganti container search input Anda menjadi seperti ini --}}
                        <div class="relative w-full md:w-80">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" name="search" id="search" placeholder="Type to search branch..."
                                value="{{ request('search') }}"
                                class="block w-full py-2.5 pl-10 pr-3 text-sm border-gray-200 rounded-xl bg-white focus:border-blue-500 focus:ring-blue-500 transition-all outline-none border shadow-sm">
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        @if (Auth::user()->role === 'superadmin')
                            <button id="openModal"
                                class="flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-bold text-white transition-all bg-blue-600 rounded-xl hover:bg-blue-700 hover:shadow-lg active:scale-95">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                Add New Branch
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <div id="table-container" class="bg-white">
                @include('branches.table')
            </div>

        </div>
    </div>

    {{-- ================= MODAL REDESIGN ================= --}}

    <div id="modal"
        class="fixed inset-0 z-50 flex items-center justify-center invisible p-4 transition-all duration-300 opacity-0 bg-gray-900/60 backdrop-blur-sm">

        {{-- MODAL BOX --}}
        <div id="modalBox"
            class="w-full max-w-lg overflow-hidden transition-all duration-300 transform scale-95 translate-y-8 bg-white shadow-2xl rounded-3xl">

            {{-- MODAL HEADER --}}
            <div class="relative px-8 pt-8 pb-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Create New Branch</h3>
                        <p class="text-sm text-gray-500">Fill in the details to register a new location.</p>
                    </div>
                    <button id="closeModal"
                        class="p-2 text-gray-400 transition-colors bg-gray-100 rounded-full hover:bg-red-50 hover:text-red-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- FORM --}}
            <form action="{{ route('branches.store') }}" method="POST" class="px-8 py-6 space-y-5">
                @csrf

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="md:col-span-1">
                        <label class="block mb-2 text-sm font-bold text-gray-700">Branch Name</label>
                        <input type="text" name="branch_name" required
                            class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all"
                            placeholder="e.g. Jakarta HQ">
                    </div>

                    <div class="md:col-span-1">
                        <label class="block mb-2 text-sm font-bold text-gray-700">Branch Code</label>
                        <input type="text" name="branch_code" required
                            class="w-full px-4 py-2.5 text-sm uppercase border border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all"
                            placeholder="e.g. JKT01">
                    </div>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-bold text-gray-700">Detailed Address</label>
                    <textarea name="address" rows="3"
                        class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all"
                        placeholder="Street name, Building, City..."></textarea>
                </div>

                {{-- MODAL FOOTER --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" id="cancelModal"
                        class="px-5 py-2.5 text-sm font-bold text-gray-600 transition-colors bg-white border border-gray-200 rounded-xl hover:bg-gray-50">
                        Discard
                    </button>

                    <button type="submit"
                        class="px-6 py-2.5 text-sm font-bold text-white transition-all bg-blue-600 rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-200">
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
        // --- FITUR LIVE SEARCH AJAX ---
        const searchInput = document.getElementById('search');
        const tableContainer = document.getElementById('table-container');
        let delayTimer;

        searchInput.addEventListener('input', function() {
            clearTimeout(delayTimer);

            // Menunggu 300ms setelah ketikan terakhir agar tidak spam request
            delayTimer = setTimeout(() => {
                const query = searchInput.value;

                // Kirim request AJAX ke route index yang sama
                fetch(`{{ route('branches.index') }}?search=${encodeURIComponent(query)}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest' // Menandai bahwa ini adalah request AJAX
                        }
                    })
                    .then(response => response.text())
                    .then(html => {
                        // Ganti isi container tabel dengan html parser baru
                        tableContainer.innerHTML = html;
                    })
                    .catch(error => console.error('Error fetching search results:', error));
            }, 300);
        });
    </script>
@endsection
