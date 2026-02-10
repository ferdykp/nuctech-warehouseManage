@extends('layout.master')

@section('content')
    <div class="min-h-screen px-6 py-6">

        {{-- PAGE HEADER --}}
        <div class="p-6 mb-6 bg-white shadow-lg rounded-2xl">
            <h1 class="text-2xl font-bold text-gray-800">Add Site</h1>
            <p class="text-sm text-gray-500">Tambah data site baru</p>
        </div>

        {{-- FORM --}}
        <div class="bg-white shadow-md rounded-2xl">
            <div class="px-6 py-4 border-b">
                <h2 class="text-lg font-semibold text-gray-700">Site Information</h2>
            </div>

            <form action="{{ route('site.store') }}" method="POST" class="p-6 space-y-6">
                @csrf

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                    {{-- CODE SITE --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">
                            Code Site
                        </label>
                        <input type="text" name="code" id="code" readonly
                            class="w-full px-4 py-2 bg-gray-100 border rounded-lg">
                    </div>

                    {{-- MACHINE NAME --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">
                            Machine Name
                        </label>
                        <input type="text" name="name" id="name"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-600"
                            placeholder="FS6000 Jakarta" required>
                    </div>

                    {{-- MACHINE TYPE --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">
                            Machine Type
                        </label>
                        <input type="text" name="machine_type" id="machine_type" readonly
                            class="w-full px-4 py-2 bg-gray-100 border rounded-lg">
                    </div>

                    {{-- IS ACTIVE --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">
                            Is Active
                        </label>
                        <select name="is_active"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-600">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>

                {{-- BUTTON --}}
                <div class="flex justify-end gap-3 pt-6 border-t">
                    <a href="{{ route('site.index') }}" class="px-5 py-2 text-sm bg-gray-200 rounded-lg hover:bg-gray-300">
                        Batal
                    </a>
                    <button type="submit"
                        class="px-6 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                        Submit
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- AUTO GENERATE SCRIPT --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const machineName = document.getElementById('name');
            const machineType = document.getElementById('machine_type');
            const code = document.getElementById('code');

            machineName.addEventListener('input', function() {
                const value = this.value.trim();

                if (!value) {
                    machineType.value = '';
                    code.value = '';
                    return;
                }

                const firstWord = value.split(' ')[0]; // FS6000

                machineType.value = firstWord.toLowerCase(); // fs6000
                code.value = 'IDN_' + firstWord.toUpperCase(); // IDN_FS6000
            });
        });
    </script>
@endsection
