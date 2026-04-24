@extends('layout.master')

@section('content')
    <div class="w-full px-6 py-6">
        <div class="bg-white shadow-sm rounded-2xl">
            {{-- HEADER --}}
            <div class="flex flex-col justify-between gap-4 px-6 py-6 border-b md:flex-row md:items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Global Inventory</h1>
                    <p class="text-sm text-gray-500">Monitoring seluruh stok sparepart di semua site</p>
                </div>

                {{-- SEARCH & FILTER --}}
                <div class="flex items-center gap-2">
                    <div class="relative w-full md:w-80">
                        <input type="text" id="global-search" value="{{ request('search') }}"
                            placeholder="Cari nama, SN, atau lokasi..."
                            class="w-full px-10 py-2 transition-all border outline-none rounded-xl focus:ring-2 focus:ring-blue-500">
                        <div class="absolute left-3 top-2.5 text-gray-400">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </div>
                        {{-- Loading Spinner --}}
                        <div id="search-loader" class="absolute hidden right-3 top-2.5 text-blue-500">
                            <i class="fa-solid fa-circle-notch fa-spin"></i>
                        </div>
                    </div>
                    <button class="p-2 bg-gray-100 rounded-xl hover:bg-gray-200">
                        <i class="text-gray-600 fa-solid fa-filter"></i>
                    </button>
                </div>
            </div>

            {{-- TABLE CONTAINER --}}
            <div id="table-container">
                @include('spareparts.all_table')
            </div>
        </div>
    </div>

    <script>
        const searchInput = document.getElementById('global-search');
        const loader = document.getElementById('search-loader');
        const container = document.getElementById('table-container');
        let searchTimer;

        // Fungsi utama Ajax
        function fetchSpareparts(url) {
            loader.classList.remove('hidden');
            container.style.opacity = '0.6';

            fetch(url, {
                    headers: {
                        "X-Requested-With": "XMLHttpRequest"
                    }
                })
                .then(response => response.text())
                .then(html => {
                    container.innerHTML = html;
                    initPagination(); // Re-bind event listener pagination
                })
                .catch(error => console.error('Error:', error))
                .finally(() => {
                    loader.classList.add('hidden');
                    container.style.opacity = '1';
                });
        }

        // Event listener Input (Real-time)
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                const searchValue = this.value;
                const url = new URL('{{ route('sparepart.all') }}');

                if (searchValue) url.searchParams.set('search', searchValue);

                // Update URL di browser tanpa reload (pushState)
                window.history.pushState({}, '', url);

                fetchSpareparts(url);
            }, 400); // 400ms delay
        });

        // Ajax untuk Pagination agar tidak reload page
        function initPagination() {
            const paginationLinks = document.querySelectorAll('#table-container .pagination a');
            paginationLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const url = this.getAttribute('href');
                    window.history.pushState({}, '', url);
                    fetchSpareparts(url);
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                });
            });
        }

        // Jalankan saat pertama kali load
        document.addEventListener('DOMContentLoaded', initPagination);

        // Handle tombol "Back" di browser
        window.onpopstate = function() {
            fetchSpareparts(window.location.href);
        };
    </script>
@endsection
