@extends('layout.master')

@section('title', 'Global Inventory Repository')

@section('content')
    <div class="w-full space-y-6">

        {{-- BREADCRUMB & PAGE HEADER --}}
        <div
            class="flex flex-col justify-between gap-4 p-6 bg-white border shadow-xs md:flex-row md:items-center rounded-3xl border-slate-200/80">
            <div>
                <nav class="flex items-center gap-2 mb-1.5 text-xs font-bold tracking-wider text-slate-400 uppercase">
                    <span class="transition-colors cursor-pointer hover:text-blue-600">Warehouse</span>
                    <i class="fa-solid fa-chevron-right text-[9px]"></i>
                    <span class="font-extrabold text-blue-600">Global Inventory</span>
                </nav>
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">Stock Repository</h1>
                <p class="mt-0.5 text-xs sm:text-sm font-medium text-slate-500">Real-time overview and asset distribution of
                    spare parts across all machine sites.</p>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <button type="button" onclick="exportGlobalReport()"
                    class="inline-flex items-center justify-center gap-2 px-5 py-3 text-xs sm:text-sm font-bold text-white transition-all bg-slate-900 shadow-md hover:bg-blue-600 rounded-xl active:scale-[0.98] cursor-pointer">
                    <i class="text-xs fa-solid fa-file-export"></i>
                    <span>Export Global Report</span>
                </button>
            </div>
        </div>

        {{-- QUICK STATS CARDS --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @php
                $statsData = $stats ?? [
                    'uniqueParts' => \App\Models\Sparepart::count(),
                    'totalUnits' => \App\Models\SparepartStock::sum('qty'),
                    'lowStock' => \App\Models\SparepartStock::where('qty', '>', 0)->where('qty', '<=', 5)->count(),
                    'damagedStock' => \App\Models\SparepartStock::where('condition', 'damaged')->sum('qty'),
                ];

                $quickStats = [
                    [
                        'label' => 'Unique Parts',
                        'value' => number_format($statsData['uniqueParts'] ?? 0),
                        'icon' => 'fa-box-archive',
                        'bg' => 'bg-blue-50',
                        'text' => 'text-blue-600',
                        'border' => 'border-blue-100',
                    ],
                    [
                        'label' => 'Total Units',
                        'value' => number_format($statsData['totalUnits'] ?? 0),
                        'icon' => 'fa-cubes',
                        'bg' => 'bg-emerald-50',
                        'text' => 'text-emerald-600',
                        'border' => 'border-emerald-100',
                    ],
                    [
                        'label' => 'Low Stock Items',
                        'value' => number_format($statsData['lowStock'] ?? 0),
                        'icon' => 'fa-triangle-exclamation',
                        'bg' => 'bg-amber-50',
                        'text' => 'text-amber-600',
                        'border' => 'border-amber-100',
                    ],
                    [
                        'label' => 'Damaged Stock',
                        'value' => number_format($statsData['damagedStock'] ?? 0),
                        'icon' => 'fa-heart-crack',
                        'bg' => 'bg-rose-50',
                        'text' => 'text-rose-600',
                        'border' => 'border-rose-100',
                    ],
                ];
            @endphp

            @foreach ($quickStats as $stat)
                <div
                    class="p-5 transition-all bg-white border shadow-xs border-slate-200/80 rounded-3xl hover:border-slate-300">
                    <div class="flex items-center gap-4">
                        <div
                            class="flex items-center justify-center w-12 h-12 rounded-2xl {{ $stat['bg'] }} {{ $stat['text'] }} {{ $stat['border'] }} border shrink-0">
                            <i class="fa-solid {{ $stat['icon'] }} text-lg"></i>
                        </div>
                        <div class="min-w-0">
                            <p
                                class="text-[10px] sm:text-[11px] font-bold tracking-wider text-slate-400 uppercase truncate">
                                {{ $stat['label'] }}
                            </p>
                            <p class="text-2xl font-black text-slate-900 mt-0.5 tracking-tight">
                                {{ $stat['value'] }}
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- MAIN INVENTORY CARD --}}
        <div class="overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">

            {{-- SEARCH & TOOLBAR SECTION --}}
            <div
                class="flex flex-col items-center justify-between gap-4 p-5 border-b sm:flex-row border-slate-100 bg-slate-50/50">
                <div class="relative w-full sm:w-80 md:w-96">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 pointer-events-none">
                        <i class="text-xs fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" id="global-search" value="{{ request('search') }}" autocomplete="off"
                        placeholder="Search SN, Part Name, or Site Location..."
                        class="w-full py-2.5 pl-10 pr-10 text-xs sm:text-sm font-medium text-slate-800 bg-white border border-slate-200 outline-none rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all shadow-2xs">

                    <button type="button" id="clearSearchBtn" onclick="clearSearch()"
                        class="{{ request('search') ? '' : 'hidden' }} absolute inset-y-0 right-3.5 flex items-center text-slate-400 hover:text-slate-600">
                        <i class="text-xs fa-solid fa-xmark"></i>
                    </button>

                    <div id="search-loader" class="absolute hidden right-3.5 top-3 text-blue-600 text-xs">
                        <i class="fa-solid fa-circle-notch fa-spin"></i>
                    </div>
                </div>

                <div class="flex items-center self-end gap-2 text-xs font-semibold text-slate-500 sm:self-center">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Live Global Query</span>
                </div>
            </div>

            {{-- AJAX TABLE CONTAINER --}}
            <div id="table-container" class="min-h-[300px] transition-opacity duration-200 overflow-x-auto">
                @include('spareparts.all_table')
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const searchInput = document.getElementById('global-search');
        const loader = document.getElementById('search-loader');
        const container = document.getElementById('table-container');
        const clearBtn = document.getElementById('clearSearchBtn');
        let searchTimer;

        function fetchSpareparts(url) {
            if (loader) loader.classList.remove('hidden');
            if (clearBtn) clearBtn.classList.add('hidden');
            container.classList.add('opacity-50');

            fetch(url, {
                    headers: {
                        "X-Requested-With": "XMLHttpRequest"
                    }
                })
                .then(response => response.text())
                .then(html => {
                    container.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error fetching global inventory:', error);
                    container.innerHTML =
                        '<div class="p-12 text-xs font-bold text-center text-rose-500">Failed to load inventory data. Please try again.</div>';
                })
                .finally(() => {
                    if (loader) loader.classList.add('hidden');
                    if (clearBtn && searchInput.value.trim()) clearBtn.classList.remove('hidden');
                    container.classList.remove('opacity-50');
                });
        }

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => {
                    const searchValue = this.value.trim();
                    const url = new URL('{{ route('sparepart.all') }}', window.location.origin);
                    if (searchValue) url.searchParams.set('search', searchValue);

                    window.history.pushState({}, '', url);
                    fetchSpareparts(url.href);
                }, 350);
            });
        }

        function clearSearch() {
            if (searchInput) {
                searchInput.value = '';
                const url = new URL('{{ route('sparepart.all') }}', window.location.origin);
                window.history.pushState({}, '', url);
                fetchSpareparts(url.href);
            }
        }

        // Global Event Delegation for Pagination
        document.addEventListener('click', function(e) {
            const paginationLink = e.target.closest('#table-container .pagination a');
            if (paginationLink) {
                e.preventDefault();
                const url = paginationLink.getAttribute('href');
                window.history.pushState({}, '', url);
                fetchSpareparts(url);
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }
        });

        function exportGlobalReport() {
            const search = searchInput ? searchInput.value.trim() : '';
            const url = new URL('{{ route('report.export_all') }}', window.location.origin);
            if (search) url.searchParams.set('search', search);
            window.location.href = url.href;
        }

        window.onpopstate = function() {
            fetchSpareparts(window.location.href);
        };
    </script>
@endpush
