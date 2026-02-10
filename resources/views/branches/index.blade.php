{{-- @extends('layouts.app')
@section('content')
    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
        <div class="p-6 bg-white rounded-lg shadow">
            <h3 class="mb-4 font-bold">Tambah Branch</h3>
            <form action="{{ route('branches.store') }}" method="POST">
                @csrf
                <input type="text" name="branch_name" placeholder="Nama Cabang" class="w-full p-2 mb-3 border rounded">
                <input type="text" name="branch_code" placeholder="Kode (SBY, JKT)"
                    class="w-full p-2 mb-3 border rounded">
                <textarea name="address" placeholder="Alamat" class="w-full p-2 mb-3 border rounded"></textarea>
                <button class="w-full py-2 text-white bg-blue-600 rounded">Simpan</button>
            </form>
        </div>
        <div class="p-6 text-sm bg-white rounded-lg shadow md:col-span-2">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="p-2 border">Kode</th>
                        <th class="p-2 border">Nama</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($branches as $b)
                        <tr>
                            <td class="p-2 border">{{ $b->branch_code }}</td>
                            <td class="p-2 border">{{ $b->branch_name }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection --}}

@extends('layout.master')

@section('content')
    <div class="w-full px-6 py-6">

        <div class="bg-white shadow rounded-2xl">

            {{-- HEADER --}}
            <div class="px-6 py-4 border-b">
                <h2 class="text-xl font-semibold uppercase">
                    Branch
                </h2>
                <div class="w-32 mt-2 border-b-4 border-red-600"></div>
            </div>

            {{-- ACTION --}}
            <div class="p-6">
                <div class="flex flex-col gap-3 mb-4 md:flex-row md:items-center md:justify-between">

                    <div class="flex flex-wrap gap-2">
                        @if (Auth::user()->role === 'admin')
                            <button id="openModal"
                                class="px-4 py-2 font-semibold text-white transition bg-green-600 rounded-lg hover:bg-green-700 active:scale-95">
                                + Tambah Branch
                            </button>

                            {{-- <button id="btn-delete"
                                class="px-4 py-2 font-semibold text-white transition bg-red-600 rounded-lg hover:bg-red-700 active:scale-95">
                                Delete Selected
                            </button> --}}
                        @endif
                    </div>

                    {{-- SEARCH --}}
                    <div class="w-full md:w-72">
                        <input type="text" id="search" name="search" placeholder="Search item..." autocomplete="off"
                            class="w-full px-4 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-blue-400 focus:outline-none">
                    </div>
                </div>
            </div>

            {{-- TABLE --}}
            <div id="table-container">
                @include('branches.table')
            </div>

        </div>

    </div>

    {{-- ================= MODAL ================= --}}

    <div id="modal"
        class="fixed inset-0 z-50 flex items-center justify-center invisible transition-all duration-200 opacity-0 bg-black/40 backdrop-blur-sm">

        {{-- CARD --}}
        <div id="modalBox"
            class="w-full max-w-lg p-0 mx-4 transition-all duration-200 scale-95 translate-y-6 bg-white shadow-2xl rounded-2xl">

            {{-- HEADER --}}
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h3 class="text-lg font-bold text-gray-800">Tambah Branch</h3>
                <button id="closeModal"
                    class="text-2xl font-bold text-gray-400 transition hover:text-red-500">&times;</button>
            </div>

            {{-- FORM --}}
            <form action="{{ route('branches.store') }}" method="POST" class="px-6 py-5 space-y-4">
                @csrf

                <div>
                    <label class="block mb-1 text-sm font-semibold">Nama Cabang</label>
                    <input type="text" name="branch_name"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 focus:outline-none"
                        required>
                </div>

                <div>
                    <label class="block mb-1 text-sm font-semibold">Kode Cabang</label>
                    <input type="text" name="branch_code"
                        class="w-full px-4 py-2 uppercase border rounded-lg focus:ring-2 focus:ring-blue-400 focus:outline-none"
                        placeholder="SBY / JKT" required>
                </div>

                <div>
                    <label class="block mb-1 text-sm font-semibold">Alamat</label>
                    <textarea name="address" rows="3"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 focus:outline-none"></textarea>
                </div>

                {{-- ACTION --}}
                <div class="flex justify-end gap-3 pt-3">
                    <button type="button" id="cancelModal"
                        class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                        Batal
                    </button>

                    <button type="submit"
                        class="px-5 py-2 font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                        Simpan
                    </button>
                </div>
            </form>

        </div>

    </div>

    {{-- ================= SCRIPT ================= --}}

    <script>
        const modal = document.getElementById('modal');
        const modalBox = document.getElementById('modalBox');

        const openBtn = document.getElementById('openModal');
        const closeBtn = document.getElementById('closeModal');
        const cancelBtn = document.getElementById('cancelModal');

        function openModal() {
            modal.classList.remove('invisible', 'opacity-0');
            modal.classList.add('opacity-100');
            modalBox.classList.remove('scale-95', 'translate-y-6');
            modalBox.classList.add('scale-100', 'translate-y-0');
            document.body.classList.add('overflow-hidden');
        }

        function closeModal() {
            modal.classList.add('opacity-0');
            modalBox.classList.add('scale-95', 'translate-y-6');
            document.body.classList.remove('overflow-hidden');
            setTimeout(() => modal.classList.add('invisible'), 200);
        }

        openBtn.onclick = openModal;
        closeBtn.onclick = closeModal;
        cancelBtn.onclick = closeModal;

        // klik background
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });

        // ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === "Escape") closeModal();
        });
    </script>
@endsection
