@extends('layout.master')

@section('title', 'User Account Management')

@section('content')
    <div class="w-full space-y-6">

        {{-- 1. HEADER CARD --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div
                        class="inline-flex items-center gap-2 px-3 py-1 mb-2 text-xs font-bold text-blue-700 border border-blue-100 rounded-full bg-blue-50">
                        <i class="fa-solid fa-users-gear text-[10px]"></i> Access Control
                    </div>
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                        User Management
                    </h1>
                    <p class="mt-1 text-xs font-semibold sm:text-sm text-slate-500">
                        Manage system user accounts, assign access roles, and set site permissions.
                    </p>
                </div>

                <a href="{{ route('profile.create') }}"
                    class="inline-flex items-center justify-center gap-2 px-5 py-3 text-xs font-bold text-white transition-all bg-blue-600 shadow-md sm:text-sm hover:bg-blue-700 rounded-xl shadow-blue-600/20 active:scale-95 shrink-0">
                    <i class="text-xs fa-solid fa-user-plus"></i>
                    <span>Add New User</span>
                </a>
            </div>
        </div>

        {{-- 2. TABLE CARD CONTAINER --}}
        <div class="overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">
            <div class="overflow-x-auto">
                <table id="datatable" class="w-full text-left border-collapse min-w-[700px]">
                    <thead>
                        <tr
                            class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 bg-slate-50 border-b border-slate-100">
                            <th scope="col" class="w-16 px-6 py-4 text-center">#</th>
                            <th scope="col" class="px-6 py-4">User Information</th>
                            <th scope="col" class="px-6 py-4">Email</th>
                            <th scope="col" class="px-6 py-4">Role</th>
                            <th scope="col" class="px-6 py-4">Site Assignment</th>
                            <th scope="col" class="w-32 px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="text-xs font-medium divide-y divide-slate-100 text-slate-700">
                        @forelse ($users as $index => $user)
                            <tr class="transition-colors hover:bg-slate-50/60">
                                <td class="px-6 py-4 font-bold text-center text-slate-400">
                                    {{ $index + 1 }}
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex items-center justify-center font-extrabold text-blue-700 border rounded-2xl w-9 h-9 bg-blue-50 border-blue-200/80 shrink-0">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold leading-snug text-slate-900">{{ $user->name }}
                                            </div>
                                            <div class="font-mono text-[11px] text-slate-400">@ {{ $user->username }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4 font-semibold text-slate-600">
                                    {{ $user->email }}
                                </td>

                                <td class="px-6 py-4">
                                    @php
                                        $roleClasses =
                                            $user->role === 'superadmin'
                                                ? 'bg-purple-50 text-purple-700 border-purple-200/60'
                                                : 'bg-blue-50 text-blue-700 border-blue-200/60';
                                    @endphp
                                    <span
                                        class="px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider border rounded-full {{ $roleClasses }}">
                                        {{ str_replace('_', ' ', $user->role) }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    @if ($user->site)
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200/80 rounded-xl">
                                            <i class="fa-solid fa-location-dot text-slate-400 text-[10px]"></i>
                                            {{ $user->site->machine_name }}
                                        </span>
                                    @else
                                        <span
                                            class="px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider bg-purple-50 text-purple-700 border border-purple-200/60 rounded-full">
                                            All Sites (Head Office)
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('profile.profileEdit', $user->id) }}"
                                            class="flex items-center justify-center w-8 h-8 transition-all border rounded-xl text-amber-600 bg-amber-50 border-amber-100 hover:bg-amber-600 hover:text-white active:scale-95"
                                            title="Edit User">
                                            <i class="text-xs fa-solid fa-pen-to-square"></i>
                                        </a>

                                        <form action="{{ route('profile.destroy', $user->id) }}" method="POST"
                                            onsubmit="return confirm('Are you sure you want to delete this user account?');"
                                            class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="flex items-center justify-center w-8 h-8 transition-all border cursor-pointer rounded-xl text-rose-600 bg-rose-50 border-rose-100 hover:bg-rose-600 hover:text-white active:scale-95"
                                                title="Delete User">
                                                <i class="text-xs fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-12 text-center text-slate-400">
                                    <div
                                        class="flex items-center justify-center w-12 h-12 mx-auto mb-3 text-xl rounded-2xl bg-slate-100 text-slate-400">
                                        <i class="fa-solid fa-users-slash"></i>
                                    </div>
                                    <p class="text-sm font-bold text-slate-800">No Registered Users Found</p>
                                    <p class="mt-1 text-xs text-slate-400">Start by registering a new account for system
                                        access.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t sm:p-6 bg-slate-50/50 border-slate-100">
                <p class="text-xs font-bold tracking-wider uppercase text-slate-400">
                    Showing {{ $users->count() }} Total User(s)
                </p>
            </div>
        </div>
    </div>
@endsection
