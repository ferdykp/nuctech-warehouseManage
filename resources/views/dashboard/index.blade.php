@extends('layout.master')

@section('content')
    <div class="px-4 py-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">

            {{-- Card --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="flex items-center justify-between p-6">
                    <div>
                        <p class="text-xs font-semibold tracking-wide text-gray-400 uppercase">
                            Total Laporan Kerusakan
                        </p>
                        <p class="mt-2 text-3xl font-bold text-gray-800">4</p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-blue-100"></div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="flex items-center justify-between p-6">
                    <div>
                        <p class="text-xs font-semibold tracking-wide text-gray-400 uppercase">
                            Total Sparepart
                        </p>
                        <p class="mt-2 text-3xl font-bold text-gray-800">4</p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-red-100"></div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="flex items-center justify-between p-6">
                    <div>
                        <p class="text-xs font-semibold tracking-wide text-gray-400 uppercase">
                            Jumlah Mesin
                        </p>
                        <p class="mt-2 text-3xl font-bold text-gray-800">3</p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-green-100"></div>
                </div>
            </div>

        </div>

    </div>
@endsection
