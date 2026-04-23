@extends('layout.master')

@section('content')
    <div class="w-full px-6 py-6">
        {{-- PAGE HEADER --}}
        <div
            class="flex flex-col gap-4 p-6 mb-6 bg-white shadow-sm md:flex-row md:items-center md:justify-between rounded-2xl">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Daftar Machine Site</h1>
                <p class="text-sm text-gray-500">Kelola lokasi mesin dan inventaris seluruh cabang</p>
            </div>

            @if (auth()->user()->role === 'superadmin')
                <button onclick="openCreateModal()"
                    class="flex items-center justify-center gap-2 px-5 py-2.5 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-all shadow-lg shadow-blue-100">
                    <i class="fa-solid fa-plus"></i>
                    Tambah Machine
                </button>
            @endif
        </div>

        {{-- STATS SUMMARY (Optional) --}}
        <div class="grid grid-cols-1 gap-6 mb-6 md:grid-cols-3">
            <div class="p-5 bg-white border-l-4 border-blue-500 shadow-sm rounded-2xl">
                <p class="text-xs font-bold tracking-wider text-gray-400 uppercase">Total Machine</p>
                <p class="text-2xl font-black text-gray-800">{{ $sites->count() }}</p>
            </div>
            <div class="p-5 bg-white border-l-4 border-green-500 shadow-sm rounded-2xl">
                <p class="text-xs font-bold tracking-wider text-gray-400 uppercase">Active Branch</p>
                <p class="text-2xl font-black text-gray-800">{{ $sites->unique('branch_id')->count() }}</p>
            </div>
            <div class="p-5 bg-white border-l-4 border-orange-500 shadow-sm rounded-2xl">
                <p class="text-xs font-bold tracking-wider text-gray-400 uppercase">Total Spareparts</p>
                <p class="text-2xl font-black text-gray-800">{{ \App\Models\SparepartStock::sum('qty') }}</p>
            </div>
        </div>

        {{-- TABLE SECTION --}}
        <div class="overflow-hidden bg-white shadow-sm rounded-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50">
                            <th class="px-6 py-4 text-xs font-bold tracking-wider text-gray-500 uppercase">Machine Info</th>
                            <th class="px-6 py-4 text-xs font-bold tracking-wider text-gray-500 uppercase">Branch</th>
                            <th class="px-6 py-4 text-xs font-bold tracking-wider text-gray-500 uppercase">Slug / Identifier
                            </th>
                            <th class="px-6 py-4 text-xs font-bold tracking-wider text-center text-gray-500 uppercase">Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($sites as $site)
                            <tr class="transition-colors hover:bg-gray-50/80 group">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex items-center justify-center w-10 h-10 text-blue-600 bg-blue-100 rounded-lg shadow-sm">
                                            <i class="fa-solid fa-microchip"></i>
                                        </div>
                                        <div>
                                            <span
                                                class="block text-sm font-bold text-gray-800">{{ $site->machine_name }}</span>
                                            <span class="text-xs text-gray-500">Created:
                                                {{ $site->created_at->format('d M Y') }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="px-3 py-1 text-xs font-semibold text-gray-600 bg-gray-100 border rounded-full">
                                        {{ $site->branch->name ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <code
                                        class="px-2 py-1 font-mono text-xs text-pink-600 rounded bg-pink-50">/{{ $site->slug }}</code>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('sparepart.index', $site->slug) }}"
                                            class="px-4 py-2 text-xs font-bold text-white transition-all bg-gray-800 rounded-lg hover:bg-black">
                                            <i class="mr-1 fa-solid fa-eye"></i> Buka Inventory
                                        </a>

                                        @if (auth()->user()->role === 'superadmin')
                                            <button class="p-2 text-blue-600 transition-colors rounded-lg hover:bg-blue-50">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <form action="{{ route('site.destroy', $site->id) }}" method="POST"
                                                onsubmit="return confirm('Hapus machine ini?')">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="p-2 text-red-500 transition-colors rounded-lg hover:bg-red-50">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-gray-400">
                                    <i class="block mb-3 text-4xl fa-solid fa-inbox"></i>
                                    Belum ada data machine terdaftar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            @if ($sites->hasPages())
                <div class="px-6 py-4 border-t bg-gray-50/50">
                    {{ $sites->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
