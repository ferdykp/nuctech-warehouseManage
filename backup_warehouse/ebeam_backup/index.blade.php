@extends('layout.master')

@section('content')
    <div class="w-full px-6 py-6">

        <div class="bg-white shadow-lg rounded-2xl">

            {{-- HEADER --}}
            <div class="px-6 py-4 border-b">
                <h4 class="text-xl font-semibold text-gray-800">
                    List Sparepart E-Beam Jakarta
                </h4>
                <div class="w-32 mt-2 border-b-4 border-red-600"></div>
            </div>

            {{-- BODY --}}
            <div class="p-6">

                {{-- ACTION --}}
                <div class="flex flex-col gap-3 mb-4 md:flex-row md:items-center md:justify-between">

                    <div class="flex flex-wrap gap-2">
                        @if (Auth::user()->role === 'admin')
                            <a href="{{ route('ebeam.create') }}"
                                class="px-4 py-2 text-sm font-semibold text-white bg-green-600 rounded-lg hover:bg-green-700">
                                Tambah Item
                            </a>

                            <button id="delete_selected"
                                class="px-4 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700">
                                Delete Selected
                            </button>
                            <a href="{{ route('ebeam.exportExcel') }}"
                                class="px-4 py-2 text-sm font-semibold text-white rounded-lg bg-emerald-600 hover:bg-emerald-700">
                                Export Excel
                            </a>
                        @endif
                    </div>

                    {{-- SEARCH --}}
                    <div class="w-full md:w-72">
                        <input type="text" id="search" name="search" data-route="{{ route('ebeam.search') }}"
                            placeholder="Search item..." autocomplete="off"
                            class="w-full px-4 py-2 text-sm border rounded-lg focus:ring focus:ring-blue-200 focus:outline-none">
                    </div>
                </div>

                {{-- TABLE --}}
                <div class="overflow-x-auto border rounded-xl">
                    <table class="w-full text-sm border-collapse">
                        <thead class="text-gray-700 bg-gray-100">
                            <tr>
                                @if (Auth::user()->role === 'admin')
                                    <th class="px-4 py-3 text-center">
                                        <input type="checkbox" id="select_all_id">
                                    </th>
                                @endif
                                <th class="px-4 py-3 text-center">No</th>
                                <th class="px-4 py-3 text-center">Item Name</th>
                                <th class="px-4 py-3 text-center">Type</th>
                                <th class="px-4 py-3 text-center">Stock</th>
                                {{-- <th class="px-4 py-3 text-center">Date Update</th>
                                <th class="px-4 py-3 text-center">Location</th>
                                <th class="px-4 py-3 text-center">Note</th>
                                <th class="px-4 py-3 text-center">Image</th> --}}
                                @if (Auth::user()->role === 'admin')
                                    <th class="px-4 py-3 text-center">Action</th>
                                @endif
                            </tr>
                        </thead>

                        {{-- <tbody id="table-body" class="divide-y">
                            @include('ebeam.table', ['data' => $ebeams])
                        </tbody> --}}
                        <tbody id="table-body">
                            @include('ebeam.table', [
                                'data' => $ebeams,
                                'routePrefix' => 'ebeam',
                            ])

                        </tbody>
                    </table>
                </div>

                {{-- FOOTER --}}
                <div class="flex flex-col gap-3 mt-4 md:flex-row md:items-center md:justify-between">
                    <div class="text-sm text-gray-600">
                        Showing {{ $ebeams->firstItem() }} to {{ $ebeams->lastItem() }} of
                        {{ $ebeams->total() }} entries
                    </div>

                    <div>
                        {{ $ebeams->links() }}
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
