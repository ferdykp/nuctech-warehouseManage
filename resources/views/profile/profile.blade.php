@extends('layout.master')

@section('content')
    <div class="min-h-screen p-4 bg-gray-100">

        {{-- PROFILE HEADER --}}
        <div class="max-w-6xl p-6 mx-auto bg-white shadow-lg rounded-2xl">
            <div class="flex items-center gap-4">
                <div class="relative w-24 h-24">
                    <img src="{{ asset('img/profile.png') }}" alt="profile_image"
                        class="object-cover w-full h-full shadow-md rounded-xl">
                </div>

                <div>
                    <h5 class="text-xl font-semibold text-gray-800">
                        {{ auth()->user()->name }}
                    </h5>
                    <p class="text-sm font-medium text-gray-500 capitalize">
                        {{ str_replace('_', ' ', auth()->user()->role) }}
                    </p>
                </div>
            </div>
        </div>

        {{-- CONTENT --}}
        <div class="max-w-6xl mx-auto mt-6 bg-white shadow-lg rounded-2xl">
            {{-- HEADER --}}
            <div class="flex items-center justify-between px-6 py-4 border-b">
                @if (Auth::user()->role == 'admin')
                    <p class="font-semibold text-gray-700">Edit Profile</p>

                    <a href="{{ route('users.index') }}"
                        class="px-4 py-2 text-sm font-medium text-white transition bg-blue-600 rounded-lg hover:bg-blue-700">
                        Manage All Accounts
                    </a>
                    {{-- <a href="{{ route('site.index') }}"
                        class="px-4 py-2 text-sm font-medium text-white transition bg-blue-600 rounded-lg hover:bg-blue-700">
                        Add Site
                    </a> --}}
                @endif
            </div>

            {{-- BODY --}}
            <div class="p-6">
                <p class="mb-4 text-xs font-semibold text-gray-500 uppercase">
                    User Information
                </p>

                <div class="space-y-4">
                    {{-- Username --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-600">
                            Username
                        </label>
                        <p class="px-4 py-2 mt-1 text-gray-800 rounded-lg bg-gray-50">
                            {{ auth()->user()->name }}
                        </p>
                    </div>

                    {{-- Email --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-600">
                            Email Address
                        </label>
                        <p class="px-4 py-2 mt-1 text-gray-800 rounded-lg bg-gray-50">
                            {{ auth()->user()->email }}
                        </p>
                    </div>

                    {{-- Role --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-600">
                            Role
                        </label>
                        <p class="px-4 py-2 mt-1 text-gray-800 capitalize rounded-lg bg-gray-50">
                            {{ auth()->user()->role }}
                        </p>
                    </div>
                </div>

                <hr class="my-6 border-gray-200">
            </div>
        </div>
    </div>
@endsection
