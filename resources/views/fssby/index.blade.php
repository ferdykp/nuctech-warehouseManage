@extends('layout.master')

@section('content')
    <div class="w-full px-6 py-6">

        <div class="bg-white shadow rounded-2xl">

            {{-- HEADER --}}
            <div class="px-6 py-4 border-b">
                <h2 class="text-xl font-semibold uppercase">
                    Sparepart {{ $siteData->name }}
                </h2>
                <div class="w-32 mt-2 border-b-4 border-red-600"></div>

            </div>

            {{-- ACTION --}}
            <div class="p-6">

                <div class="flex flex-col gap-3 mb-4 md:flex-row md:items-center md:justify-between">

                    <div class="flex flex-wrap gap-2">
                        @if (Auth::user()->role === 'admin')
                            <a href="/{{ $site }}/create"
                                class="p-3 text-sm font-semibold text-white bg-green-600 rounded-lg hover:bg-green-700">
                                Tambah Item
                            </a>

                            <button id="btn-delete"
                                class="p-3 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700">
                                Delete Selected
                            </button>
                            <a href="/{{ $site }}/export"
                                class="p-3 text-sm font-semibold text-white rounded-lg bg-emerald-600 hover:bg-emerald-700">
                                Export Excel
                            </a>
                        @endif
                    </div>

                    {{-- SEARCH --}}
                    <div class="w-full md:w-72">
                        <input type="text" id="search" name="search"
                            data-route="{{ route('sparepart.search', ['site' => $site]) }}" placeholder="Search item..."
                            autocomplete="off"
                            class="w-full px-4 py-2 text-sm border rounded-lg focus:ring focus:ring-blue-200 focus:outline-none">
                    </div>
                </div>
            </div>

            {{-- TABLE --}}
            <div id="table-container">
                @include(match ($site) {
                        'fs6000sby' => 'fssby.table',
                        default => $site . '.table',
                    })
            </div>

        </div>
    </div>
@endsection
