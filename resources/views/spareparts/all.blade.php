@extends('layout.master')

@section('content')
    <div class="w-full space-y-6">

        {{-- BREADCRUMB & HEADER --}}
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <nav class="flex mb-1.5 text-xs font-bold tracking-widest text-slate-400 uppercase">
                    <span class="transition-colors cursor-pointer hover:text-blue-600">Warehouse</span>
                    <span class="mx-2">/</span>
                    <span class="text-blue-600">Global Inventory</span>
                </nav>
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">Stock Repository</h1>
                <p class="mt-0.5 text-xs sm:text-sm font-medium text-slate-500">Real-time overview of spare parts across all
                    machine sites.</p>
            </div>

            <div class="flex items-center gap-2 sm:w-auto shrink-0">
                <button onclick="location.reload()"
                    class="p-2.5 text-slate-400 transition-all bg-white border border-slate-200 shadow-2xs rounded-xl hover:text-blue-600 hover:bg-blue-50"
                    title="Refresh Data">
                    <i class="fa-solid fa-rotate"></i>
                </button>
                <a href="javascript:void(0)" onclick="exportGlobalReport()"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-xs sm:text-sm font-bold text-white transition-all bg-slate-900 shadow-md shadow-slate-900/20 rounded-xl hover:bg-black active:scale-95">
                    <i class="fa-solid fa-file-export"></i>
                    <span>Export Report</span>
                </a>
            </div>
        </div>

        {{-- QUICK STATS --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
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
                        'color' => 'amber',
                    ],
                    [
                        'label' => 'Out of Stock',
                        'value' => number_format($outOfStock),
                        'icon' => 'fa-circle-xmark',
                        'color' => 'rose',
                    ],
                ];
            @endphp

            @foreach ($quickStats as $stat)
                <div
                    class="p-4 transition-all bg-white border sm:p-5 border-slate-100 shadow-2xs rounded-2xl hover:shadow-md">
                    <div class="flex items-center gap-4">
                        <div
                            class="flex items-center justify-center w-10 h-10 rounded-xl bg-{{ $stat['color'] }}-50 text-{{ $stat['color'] }}-600 shrink-0">
                            <i class="fa-solid {{ $stat['icon'] }} text-base"></i>
                        </div>
                        <div class="min-w-0">
                            <p
                                class="text-[10px] sm:text-[11px] font-bold tracking-wider text-slate-400 uppercase truncate">
                                {{ $stat['label'] }}</p>
                            <p class="text-xl sm:text-2xl font-black text-slate-800 mt-0.5">
                                {{ $stat['value'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- MAIN INVENTORY CARD --}}
        <div class="overflow-hidden bg-white shadow-sm ring-1 ring-slate-200/80 rounded-2xl">

            {{-- SEARCH & FILTER BAR --}}
            <div
                class="flex flex-col items-center justify-between gap-3 p-4 border-b sm:flex-row sm:p-5 border-slate-100 bg-slate-50/50">
                <div class="relative w-full sm:w-80 md:w-96">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 pointer-events-none">
                        <i class="text-xs fa-solid fa-magnifying-glass"></i>
                    </div>
                    <input type="text" id="global-search" value="{{ request('search') }}"
                        placeholder="Search SN, Part Name, or Site..."
                        class="w-full py-2.5 pl-10 pr-10 text-xs sm:text-sm font-medium text-slate-700 bg-white border border-slate-200 outline-none rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all">

                    <div id="search-loader" class="absolute hidden right-3.5 top-3 text-blue-500 text-xs">
                        <i class="fa-solid fa-circle-notch fa-spin"></i>
                    </div>
                </div>

                <div class="self-end text-xs font-semibold text-slate-400 sm:self-center">
                    Live Global Query
                </div>
            </div>

            {{-- AJAX TABLE CONTAINER --}}
            <div id="table-container" class="min-h-[300px] transition-opacity duration-300 overflow-x-auto">
                @include('spareparts.all_table')
            </div>
        </div>
    </div>

    {{-- SCRIPTS --}}
    <script>
        const searchInput = document.getElementById('global-search');
        const loader = document.getElementById('search-loader');
        const container = document.getElementById('table-container');
        let searchTimer;

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
                        '<div class="p-8 font-bold text-center text-rose-500">Failed to load inventory data.</div>';
                })
                .finally(() => {
                    loader.classList.add('hidden');
                    container.style.opacity = '1';
                });
        }

        if (searchInput) {
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
        }

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

        function exportGlobalReport() {
            const search = document.getElementById('global-search').value;
            const url = new URL('{{ route('report.export_all') }}');
            if (search) url.searchParams.set('search', search);
            window.location.href = url.href;
        }

        document.addEventListener('DOMContentLoaded', initPagination);
        window.onpopstate = function() {
            fetchSpareparts(window.location.href);
        };
    </script>
@endsection
