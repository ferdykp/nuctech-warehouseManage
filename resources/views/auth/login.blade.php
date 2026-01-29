@extends('layout.master')

@section('content')
    <section
        class="relative flex items-center justify-center min-h-screen px-4 bg-gradient-to-br from-[#0f2027] via-[#203a43] to-[#2c5364]">

        {{-- BACKGROUND GLOW --}}
        <div class="absolute inset-0 opacity-20 bg-[radial-gradient(circle_at_top,_#ffffff,_transparent_60%)]"></div>

        <div class="relative grid w-full max-w-5xl overflow-hidden bg-white shadow-2xl rounded-3xl md:grid-cols-2">

            {{-- LEFT IMAGE --}}
            <div class="relative hidden md:block">
                <img src="img/nuctech-building.jpg" alt="Login Image" class="object-cover w-full h-full">
                <div class="absolute inset-0 bg-black/40"></div>

                <div class="absolute text-white bottom-6 left-6 right-6">
                    <h3 class="text-xl font-semibold tracking-wide">
                        Secure Internal System
                    </h3>
                    <p class="mt-1 text-sm text-gray-200">
                        Authorized personnel only
                    </p>
                </div>

            </div>

            {{-- RIGHT FORM --}}
            <div class="flex items-center justify-center px-6 py-10 sm:px-12">
                <div class="w-full max-w-md">

                    {{-- LOGO --}}
                    <div class="flex justify-center mb-6">
                        <img src="img/logo-txt-removebg.png" alt="Logo" class="h-16">
                    </div>

                    <h2 class="mb-2 text-2xl font-bold tracking-wide text-center text-gray-900">
                        Welcome Back
                    </h2>
                    <p class="mb-8 text-sm text-center text-gray-500">
                        Please sign in to continue
                    </p>

                    <form action="{{ route('auth.login') }}" method="POST" class="space-y-6">
                        @csrf

                        {{-- USERNAME --}}
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">
                                Username
                            </label>
                            <input type="text" name="username" value="{{ old('username') }}" required
                                class="w-full px-4 py-3 text-gray-900 transition duration-200 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent"
                                placeholder="Enter your username">
                        </div>

                        {{-- PASSWORD --}}
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">
                                Password
                            </label>
                            <div class="relative">
                                <input type="password" name="password" id="password" required
                                    autocomplete="current-password"
                                    class="w-full px-4 py-3 pr-12 text-gray-900 transition duration-200 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent"
                                    placeholder="••••••••">

                                <button type="button" onclick="togglePassword()"
                                    class="absolute text-gray-500 -translate-y-1/2 right-4 top-1/2 hover:text-gray-700">

                                    <i id="eyeIcon" class="fa-solid fa-eye"></i>
                                </button>
                            </div>

                        </div>

                        {{-- BUTTON --}}
                        <button type="submit"
                            class="w-full py-3 font-semibold text-white transition-all duration-200 shadow-lg rounded-xl bg-gradient-to-r from-blue-600 to-blue-800 hover:from-blue-700 hover:to-blue-900 hover:shadow-xl">
                            Sign In
                        </button>
                    </form>

                    {{-- FOOTER --}}
                    <p class="mt-8 text-xs text-center text-gray-400">
                        © {{ date('Y') }} Internal System. All rights reserved.
                    </p>

                </div>
            </div>
        </div>

        {{-- SCRIPT --}}
        <script>
            function togglePassword() {
                const password = document.getElementById('password');
                const icon = document.getElementById('eyeIcon');

                if (password.type === 'password') {
                    password.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    password.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }
        </script>

    </section>
@endsection
