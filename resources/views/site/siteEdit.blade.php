@extends('layout.master')

@section('content')
    <div class="min-h-screen px-6 py-6 ">

        {{-- PAGE HEADER --}}
        <div class="p-6 mb-6 bg-white shadow-lg rounded-2xl">
            <h1 class="text-2xl font-bold text-gray-800">Edit Site</h1>
            <p class="text-sm text-gray-500">Kelola dan perbarui data pengguna</p>
        </div>

        {{-- FORM WRAPPER --}}
        <div class="bg-white shadow-lg rounded-2xl">

            {{-- FORM HEADER --}}
            <div class="px-6 py-4 border-b">
                <h2 class="text-lg font-semibold text-gray-700">Site Information</h2>
            </div>

            {{-- FORM BODY --}}
            <form action="{{ route('site.update', $user->id) }}" method="POST" class="p-6 space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                    {{-- USERNAME --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">
                            Code Site
                        </label>
                        <input type="text" name="code" value="{{ old('code', $user->code) }}" required
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none">

                        @error('code')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- EMAIL --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">
                            Machine Name
                        </label>
                        <input type="name" name="name" value="{{ old('name', $user->name) }}" required
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none">

                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">
                            Machine Type
                        </label>
                        <input type="machine_type" name="machine_type" value="{{ old('machine_type', $user->name) }}"
                            required
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-600 focus:outline-none">

                        @error('machine_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- ACTION BUTTON --}}
                <div class="flex justify-end gap-3 pt-6 border-t">
                    <a href="{{ route('site.index') }}"
                        class="px-5 py-2 text-sm font-medium text-gray-700 transition bg-gray-200 rounded-lg hover:bg-gray-300">
                        Back
                    </a>

                    <button type="submit"
                        class="px-6 py-2 text-sm font-semibold text-white transition bg-blue-600 rounded-lg hover:bg-blue-700">
                        Update Site
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function togglePasswords() {
            document.querySelectorAll("input[type='password']").forEach(field => {
                field.type = field.type === "password" ? "text" : "password";
            });
        }
    </script>
@endsection
