@extends('layout.master')

@section('content')
    <div class="w-full p-6">
        <form action="{{ route('branches.update', $branch->id) }}" method="POST" class="px-6 py-5 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block mb-1 text-sm font-semibold">Nama Cabang</label>
                <input type="text" name="branch_name" value="{{ old('branch_name', $branch->branch_name) }}"
                    class="w-full px-4 py-2 border rounded-lg" required>
            </div>

            <div>
                <label class="block mb-1 text-sm font-semibold">Kode Cabang</label>
                <input type="text" name="branch_code" value="{{ old('branch_code', $branch->branch_code) }}"
                    class="w-full px-4 py-2 uppercase border rounded-lg" required>
            </div>

            <div>
                <label class="block mb-1 text-sm font-semibold">Alamat</label>
                <textarea name="branch_address" rows="3" class="w-full px-4 py-2 border rounded-lg">{{ old('branch_address', $branch->branch_address) }}</textarea>
            </div>

            <div class="flex justify-end gap-3 pt-3">
                <button type="submit" class="px-5 py-2 font-semibold text-white bg-blue-600 rounded-lg">
                    Simpan
                </button>
            </div>
        </form>
    </div>
@endsection
