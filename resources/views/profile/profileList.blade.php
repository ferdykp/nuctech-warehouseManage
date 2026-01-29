@extends('layout.master')

@section('content')
    <div class="min-h-screen p-4 ">

        <div class="mx-auto bg-white shadow-lg border-1 max-w-7xl rounded-2xl">

            {{-- HEADER ACTION --}}
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h4 class="text-lg font-semibold text-gray-800">
                    List Pengguna
                </h4>

                <a href="{{ route('users.create') }}"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white transition bg-green-600 rounded-lg hover:bg-green-700">
                    + Tambahkan User
                </a>
            </div>

            {{-- TABLE --}}
            <div class="p-6 overflow-x-auto">
                <table id="datatable" class="min-w-full overflow-hidden border border-gray-200 rounded-lg">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-sm font-semibold text-center text-gray-600">No</th>
                            <th class="px-4 py-3 text-sm font-semibold text-center text-gray-600">Username</th>
                            <th class="px-4 py-3 text-sm font-semibold text-center text-gray-600">Name</th>
                            <th class="px-4 py-3 text-sm font-semibold text-center text-gray-600">Email</th>
                            <th class="px-4 py-3 text-sm font-semibold text-center text-gray-600">Role</th>
                            <th class="px-4 py-3 text-sm font-semibold text-center text-gray-600">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        @forelse ($users as $index => $user)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-center text-gray-700">
                                    {{ $index + 1 }}
                                </td>

                                <td class="px-4 py-3 text-sm text-center text-gray-700">
                                    {{ $user->username }}
                                </td>

                                <td class="px-4 py-3 text-sm text-center text-gray-700">
                                    {{ $user->name }}
                                </td>

                                <td class="px-4 py-3 text-sm text-center text-gray-700">
                                    {{ $user->email }}
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="px-3 py-1 text-xs font-medium rounded-full
                                    {{ $user->role === 'admin' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700' }}">
                                        {{ ucfirst($user->role) }}
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('users.edit', $user->id) }}"
                                            class="px-3 py-1.5 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">
                                            Edit
                                        </a>

                                        <form action="{{ route('users.destroy', $user->id) }}" method="POST"
                                            onsubmit="return confirm('Apakah Anda Yakin ?');">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                class="px-3 py-1.5 text-sm text-white bg-red-600 rounded-lg hover:bg-red-700 transition">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center">
                                    <div class="py-3 text-sm text-red-600 rounded-lg bg-red-50">
                                        Tidak ada user yang terdaftar
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
@endsection
