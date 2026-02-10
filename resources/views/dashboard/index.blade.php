@extends('layout.master')

@section('content')
    <div class="px-4 py-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">

            {{-- Card --}}
            <div class="bg-white border border-gray-100 shadow-sm rounded-xl">
                <div class="flex items-center justify-between p-6">
                    <div>
                        <p class="text-xs font-semibold tracking-wide text-gray-400 uppercase">
                            Total Laporan Kerusakan
                        </p>
                        <p class="mt-2 text-3xl font-bold text-gray-800">{{ $totalReport }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-full"></div>
                </div>
            </div>

            <div class="bg-white border border-gray-100 shadow-sm rounded-xl">
                <div class="flex items-center justify-between p-6">
                    <div>
                        <p class="text-xs font-semibold tracking-wide text-gray-400 uppercase">
                            Total Sparepart
                        </p>
                        <p class="mt-2 text-3xl font-bold text-gray-800">{{ $totalSparepart }}</p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 rounded-full"></div>
                </div>
            </div>

            <div class="bg-white border border-gray-100 shadow-sm rounded-xl">
                <div class="flex items-center justify-between p-6">
                    <div>
                        <p class="text-xs font-semibold tracking-wide text-gray-400 uppercase">
                            Jumlah Mesin
                        </p>
                        <p class="mt-2 text-3xl font-bold text-gray-800"> {{ $totalMachine }}
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-full"></div>
                </div>
            </div>

        </div>
        <div class="p-6 bg-white shadow-md rounded-xl">
            <h3 class="mb-4 text-xl font-bold text-gray-800">Quick Access to Sites</h3>
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach (\App\Models\Site::with('branch')->get() as $site)
                    {{-- <a href="{{ route('sites.inventory', $site->slug) }}" --}}
                    <a href="{{ route('sites.index', $site->slug) }}"
                        class="p-4 transition border rounded-lg hover:bg-blue-50 hover:border-blue-300 group">
                        <span
                            class="block font-bold text-blue-600 group-hover:text-blue-800">{{ $site->machine_name }}</span>
                        <span class="text-xs text-gray-500">{{ $site->branch->branch_name }}</span>
                    </a>
                @endforeach
            </div>
        </div>

    </div>
@endsection
