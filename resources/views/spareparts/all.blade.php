@extends('layout.master')

@section('content')
    <div class="w-full px-4 py-6 md:px-6 md:py-8">

        {{-- BREADCRUMB & HEADER --}}
        <div class="flex flex-col gap-4 mb-8 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <nav class="flex mb-2 text-xs font-bold tracking-widest text-gray-400 uppercase">
                    <span class="transition-colors cursor-pointer hover:text-blue-600">Warehouse</span>
                    <span class="mx-2">/</span>
                    <span class="text-blue-600">Global Inventory</span>
                </nav>
                <h1 class="text-2xl font-extrabold tracking-tight text-gray-900 md:text-3xl">Stock Repository</h1>
                <p class="mt-1 text-sm text-gray-500">Real-time overview of spare parts across all machine sites.</p>
            </div>

            <div class="flex items-center w-full gap-3 sm:w-auto">
                <button onclick="location.reload()"
                    class="p-3 text-gray-400 transition-all bg-white border border-gray-200 shadow-sm rounded-2xl hover:text-blue-600 hover:bg-blue-50"
                    title="Refresh Data">
                    <i class="fa-solid fa-rotate"></i>
                </button>
                <a href="javascript:void(0)" onclick="exportGlobalReport()"
                    class="inline-flex items-center justify-center flex-1 gap-2 px-6 py-3 text-sm font-bold text-white transition-all bg-gray-900 shadow-xl sm:flex-none rounded-2xl hover:bg-black shadow-gray-200 active:scale-95">
                    <i class="fa-solid fa-file-export"></i>
                    Export Report
                </a>
            </div>
        </div>

        {{-- QUICK STATS (REAL DATA) --}}
        <div class="grid grid-cols-1 gap-4 mb-8 sm:grid-cols-2 lg:grid-cols-4 md:gap-6">
            @php
                $uniqueParts = \App\Models\Sparepart::count();
                $totalUnits = \App\Models\SparepartStock::sum('qty');
                $lowStock = \App\Models\SparepartStock::where('qty', '>', 0)->where('qty', '<=', 5)->count();
                $outOfStock = \App\Models\SparepartStock::where('qty', '<=', 0)->count();

                $quickStats = [
                    [
                        'label' => 'Unique Parts',
                        'value' => number_format($uniqueParts),
                        'icon' => 'fa-box',
                        'color' => 'blue',
                    ],
                    [
                        'label' => 'Total Units',
                        'value' => number_format($totalUnits),
                        'icon' => 'fa-cubes',
                        'color' => 'emerald',
                    ],
                    [
                        'label' => 'Low Stock',
                        'value' => number_format($lowStock),
                        'icon' => 'fa-triangle-exclamation',
                        'color' => 'orange',
                    ],
                    [
                        'label' => 'Out of Stock',
                        'value' => number_format($outOfStock),
                        'icon' => 'fa-circle-xmark',
                        'color' => 'red',
                    ],
                ];
            @endphp

            @foreach ($quickStats as $stat)
                <div class="p-5 transition-all bg-white border border-gray-100 shadow-sm rounded-3xl hover:shadow-md">
                    <div class="flex items-center gap-4">
                        <div
                            class="flex items-center justify-center w-12 h-12 rounded-2xl bg-{{ $stat['color'] }}-50 text-{{ $stat['color'] }}-600 flex-shrink-0">
                            <i class="fa-solid {{ $stat['icon'] }} text-lg"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-bold tracking-wider text-gray-400 uppercase truncate">
                                {{ $stat['label'] }}</p>
                            <p class="text-xl font-black leading-tight text-gray-900 md:text-2xl mt-0.5">
                                {{ $stat['value'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- MAIN INVENTORY CARD --}}
        <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-200 rounded-3xl">

            {{-- SEARCH & FILTER BAR --}}
            <div
                class="flex flex-col gap-4 px-6 py-5 border-b border-gray-100 sm:flex-row sm:items-center sm:justify-between bg-gray-50/50">
                <div class="relative w-full sm:w-80 md:w-96 group">
                    <div
                        class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 transition-colors pointer-events-none group-focus-within:text-blue-500">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>
                    <input type="text" id="global-search" value="{{ request('search') }}"
                        placeholder="Search SN, Part Name, or Site..."
                        class="w-full py-3 pr-12 text-sm font-medium text-gray-700 transition-all bg-white border border-gray-200 outline-none pl-11 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500">

                    <div id="search-loader" class="absolute hidden right-4 top-3.5 text-blue-500">
                        <i class="fa-solid fa-circle-notch fa-spin"></i>
                    </div>
                </div>

                <div class="flex items-center w-full gap-3 sm:w-auto">
                    <button
                        class="flex items-center justify-center w-full gap-2 px-5 py-3 text-sm font-bold text-gray-600 transition-colors bg-white border border-gray-200 sm:w-auto rounded-2xl hover:bg-gray-50">
                        <i class="text-blue-500 fa-solid fa-filter"></i>
                        Advanced Filters
                    </button>
                </div>
            </div>

            {{-- AJAX TABLE CONTAINER --}}
            <div id="table-container" class="min-h-[300px] transition-opacity duration-300">
                @include('spareparts.all_table')
            </div>
        </div>
    </div>

    {{-- SCRIPTS (OPTIMIZED & CLEANED UP) --}}
    <script>
        const searchInput = document.getElementById('global-search');
        const loader = document.getElementById('search-loader');
        const container = document.getElementById('table-container');
        let searchTimer;

        // Fungsi fetch data via AJAX
        function fetchSpareparts(url) {
            loader.classList.remove('hidden');
            container.style.opacity = '0.5';

            fetch(url, {
                    headers: {
                        "X-Requested-With": "XMLHttpRequest"
                    }
                })
                .then(response => response.text())
                .then(html => {
                    container.innerHTML = html;
                    initPagination();
                })
                .catch(error => {
                    console.error('Error:', error);
                    container.innerHTML =
                        '<div class="p-8 font-bold text-center text-red-500">Failed to load data.</div>';
                })
                .finally(() => {
                    loader.classList.add('hidden');
                    container.style.opacity = '1';
                });
        }

        // Event listener untuk search (dengan debounce)
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                const searchValue = this.value;
                const url = new URL('{{ route('sparepart.all') }}');
                if (searchValue) url.searchParams.set('search', searchValue);

                window.history.pushState({}, '', url);
                fetchSpareparts(url);
            }, 400);
        });

        // Inisialisasi ulang event listener pagination setelah tabel di-update
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

        // Trigger export report
        function exportGlobalReport() {
            const search = document.getElementById('global-search').value;
            const url = new URL('{{ route('report.export_all') }}');

            if (search) {
                url.searchParams.set('search', search);
            }

            window.location.href = url.href;
        }

        // Jalankan saat load pertama kali
        document.addEventListener('DOMContentLoaded', initPagination);

        // Handle tombol back/forward di browser
        window.onpopstate = function() {
            fetchSpareparts(window.location.href);
        };
    </script>
@endsection
