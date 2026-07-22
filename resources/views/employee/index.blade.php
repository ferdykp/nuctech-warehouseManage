@extends('layout.master')

@section('title', 'Daftar Karyawan')

@section('content')
    <div class="w-full px-6 py-8">

        {{-- ============ FLASH MESSAGES ============ --}}
        @if (session('success'))
            <div
                class="flex items-center gap-2 p-4 mb-6 text-sm text-green-800 border border-green-200 bg-green-50 rounded-xl">
                <i class="text-base fa-solid fa-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <div class="flex flex-col gap-2 mb-6">
            <h1 class="text-3xl font-extrabold tracking-tighter text-black">
                Employee Management
            </h1>
            @if (Auth::user()->role === 'admin_site')
                <p class="text-xs font-semibold text-blue-600">
                    <i class="mr-1 fa-solid fa-building-user"></i> Site Admin: {{ Auth::user()->site->machine_name ?? '-' }}
                </p>
            @endif
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
                        <input type="text" name="search" id="search" placeholder="Type to search Employee..."
                            value="{{ request('search') }}"
                            class="block w-full py-2.5 pl-10 pr-3 text-sm border-gray-200 rounded-xl bg-white focus:border-blue-500 focus:ring-blue-500 transition-all outline-none border shadow-sm">
                    </div>

                    <div class="flex items-center gap-3">
                        {{-- IZINKAN KEDUA ROLE (SUPERADMIN & SITE ADMIN) UNTUK ADD EMPLOYEE --}}
                        <a href="{{ route('employee.create') }}"
                            class="flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-bold text-white transition-all bg-blue-600 rounded-xl hover:bg-blue-700 hover:shadow-lg active:scale-95">
                            <i class="fa-solid fa-user"></i>Add Employee
                        </a>
                    </div>

                </div>
            </div>

            <div id="table-container">
                @include('employee.table', ['employees' => $employees])
            </div>
        </div>
    </div>

    <script>
        const searchInput = document.getElementById('search');
        const tableContainer = document.getElementById('table-container');
        let delayTimer;

        searchInput.addEventListener('input', function() {
            clearTimeout(delayTimer);

            delayTimer = setTimeout(() => {
                const query = searchInput.value;

                fetch(`{{ route('employee.index') }}?search=${encodeURIComponent(query)}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.text())
                    .then(html => {
                        tableContainer.innerHTML = html;
                    })
                    .catch(error => console.error('Error Fetching', error));
            }, 300);
        });
    </script>
@endsection
