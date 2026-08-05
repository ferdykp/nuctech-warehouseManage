@extends('layout.master')

@section('title', 'User Account Management')

@section('content')
    <div class="w-full space-y-6">

        {{-- PAGE HEADER --}}
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">User Management</h1>
                <p class="mt-0.5 text-xs sm:text-sm font-medium text-slate-500">Manage system users, access roles, and site
                    permissions.</p>
            </div>

            <a href="{{ route('profile.create') }}"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-white transition-all bg-blue-600 rounded-xl hover:bg-blue-700 shadow-md shadow-blue-600/20 active:scale-95 shrink-0">
                <i class="fa-solid fa-user-plus"></i>
                <span>Add New User</span>
            </a>
        </div>

        {{-- TABLE CARD --}}
        <div class="overflow-hidden bg-white border shadow-sm border-slate-200/80 rounded-2xl sm:rounded-3xl">

            <div class="overflow-x-auto">
                <table id="datatable" class="w-full text-left border-collapse min-w-[700px]">
                    <thead>
                        <tr
                            class="border-b bg-slate-100/70 border-slate-200/80 text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">
                            <th scope="col" class="px-6 py-3.5 w-12">#</th>
                            <th scope="col" class="px-6 py-3.5">User Information</th>
                            <th scope="col" class="px-6 py-3.5">Email</th>
                            <th scope="col" class="px-6 py-3.5">Role</th>
                            <th scope="col" class="px-6 py-3.5">Site Assignment</th>
                            <th scope="col" class="px-6 py-3.5 text-center w-28">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="text-xs font-medium divide-y divide-slate-100 sm:text-sm text-slate-700">
                        @forelse ($users as $index => $user)
                            <tr class="transition-colors hover:bg-slate-50/80">
                                <td class="px-6 py-4 font-bold text-slate-400">
                                    {{ $index + 1 }}
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex items-center justify-center font-extrabold text-blue-700 border rounded-full w-9 h-9 bg-blue-50 border-blue-200/80 shrink-0">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900">{{ $user->name }}</div>
                                            <div class="font-mono text-xs text-slate-400">@ {{ $user->username }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="font-semibold text-slate-600">{{ $user->email }}</span>
                                </td>

                                <td class="px-6 py-4">
                                    @php
                                        $roleClasses =
                                            $user->role === 'superadmin'
                                                ? 'bg-purple-50 text-purple-700 border-purple-200/60'
                                                : 'bg-blue-50 text-blue-700 border-blue-200/60';
                                    @endphp
                                    <span
                                        class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider border rounded-full {{ $roleClasses }}">
                                        {{ str_replace('_', ' ', $user->role) }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    @if ($user->site)
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200 rounded-lg">
                                            <i class="fa-solid fa-location-dot text-slate-400 text-[10px]"></i>
                                            {{ $user->site->machine_name }}
                                        </span>
                                    @else
                                        <span
                                            class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider bg-purple-50 text-purple-700 border border-purple-200/60 rounded-full">
                                            All Sites (Head Office)
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('profile.profileUpdate', $user->id) }}"
                                            class="p-1.5 text-amber-600 transition-colors bg-amber-50 rounded-lg hover:bg-amber-600 hover:text-white"
                                            title="Edit User">
                                            <i class="text-xs fa-solid fa-pen-to-square"></i>
                                        </a>

                                        <form action="{{ route('profile.destroy', $user->id) }}" method="POST"
                                            onsubmit="return confirm('Are you sure you want to delete this user account?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="p-1.5 text-rose-600 transition-colors bg-rose-50 rounded-lg hover:bg-rose-600 hover:text-white"
                                                title="Delete User">
                                                <i class="text-xs fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-10 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <i class="text-3xl opacity-50 text-slate-300 fa-solid fa-users-slash"></i>
                                        <p class="text-sm font-bold text-slate-700">No registered users found.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t bg-slate-50/50 border-slate-100">
                <p class="text-xs font-bold tracking-wider uppercase text-slate-400">
                    Showing {{ $users->count() }} Total User(s)
                </p>
            </div>

        </div>
    </div>
@endsection
