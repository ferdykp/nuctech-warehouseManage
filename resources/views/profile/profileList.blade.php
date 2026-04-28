@extends('layout.master')

@section('content')
    <div class="min-h-screen p-6 bg-slate-50">

        {{-- PAGE HEADER --}}
        <div class="flex flex-col gap-4 mb-8 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">User Management</h1>
                <p class="text-sm font-medium text-slate-500">Manage your team members and their account permissions.</p>
            </div>

            <a href="{{ route('users.create') }}"
                class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-bold text-white transition-all bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-sm hover:shadow-indigo-200 active:scale-95">
                <i class="mr-2 fa-solid fa-user-plus"></i>
                Add New User
            </a>
        </div>

        {{-- TABLE CARD --}}
        <div class="overflow-hidden bg-white border shadow-sm border-slate-200 rounded-2xl">

            <div class="p-6 overflow-x-auto">
                <table id="datatable" class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="px-4 py-4 text-xs font-bold tracking-wider uppercase text-slate-400">#</th>
                            <th class="px-4 py-4 text-xs font-bold tracking-wider uppercase text-slate-400">User Information
                            </th>
                            <th class="px-4 py-4 text-xs font-bold tracking-wider uppercase text-slate-400">Email</th>
                            <th class="px-4 py-4 text-xs font-bold tracking-wider uppercase text-slate-400">Role</th>
                            {{-- KOLOM BARU --}}
                            <th class="px-4 py-4 text-xs font-bold tracking-wider uppercase text-slate-400">Site Assignment
                            </th>
                            <th class="px-4 py-4 text-xs font-bold tracking-wider text-center uppercase text-slate-400">
                                Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-50">
                        @forelse ($users as $index => $user)
                            <tr class="transition-colors group hover:bg-slate-50/80">
                                <td class="px-4 py-4 text-sm font-medium text-slate-400">
                                    {{ $index + 1 }}
                                </td>

                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex items-center justify-center w-10 h-10 font-bold text-indigo-700 bg-indigo-100 rounded-full shrink-0">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-slate-900">{{ $user->name }}</div>
                                            <div class="font-mono text-xs text-slate-500">@ {{ $user->username }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <span class="text-sm font-medium text-slate-600">{{ $user->email }}</span>
                                </td>

                                <td class="px-4 py-4">
                                    @php
                                        $roleClasses =
                                            $user->role === 'superadmin'
                                                ? 'bg-purple-50 text-purple-700 border-purple-100'
                                                : 'bg-blue-50 text-blue-700 border-blue-100';
                                    @endphp
                                    <span
                                        class="px-3 py-1 text-[10px] font-black uppercase tracking-widest border rounded-full {{ $roleClasses }}">
                                        {{ str_replace('_', ' ', $user->role) }}
                                    </span>
                                </td>

                                {{-- ISI KOLOM SITE ASSIGNMENT --}}
                                <td class="px-4 py-4">
                                    @if ($user->site)
                                        <div class="flex flex-col">
                                            <span
                                                class="text-sm font-bold text-slate-700">{{ $user->site->machine_name ?? 'All Role' }}</span>
                                        </div>
                                    @else
                                        <span class="text-sm italic text-slate-500">Admin</span>
                                    @endif
                                </td>

                                <td class="px-4 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('users.edit', $user->id) }}"
                                            class="flex items-center justify-center text-blue-600 transition-colors border border-blue-100 w-9 h-9 rounded-xl hover:bg-blue-600 hover:text-white"
                                            title="Edit User">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>

                                        <form action="{{ route('users.destroy', $user->id) }}" method="POST"
                                            onsubmit="return confirm('Are you sure you want to delete this user?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="flex items-center justify-center text-red-600 transition-colors border border-red-100 w-9 h-9 rounded-xl hover:bg-red-600 hover:text-white"
                                                title="Delete User">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-20 text-center">
                                    <div class="flex items-center justify-center w-16 h-16 mb-4 rounded-full bg-slate-100">
                                        <i class="text-2xl text-slate-400 fa-solid fa-users-slash"></i>
                                    </div>
                                    <h3 class="text-lg font-bold text-slate-900">No users found</h3>
                                    <p class="text-sm text-slate-500">Currently, there are no registered users in the
                                        database.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t bg-slate-50 border-slate-100">
                <p class="text-xs font-bold tracking-widest uppercase text-slate-400">
                    Showing {{ $users->count() }} Total Entries
                </p>
            </div>

        </div>
    </div>
@endsection
