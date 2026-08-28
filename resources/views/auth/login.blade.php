@extends('layout.master')

@section('content')
    <section
        class="relative flex items-center justify-center w-screen h-screen min-h-screen p-4 overflow-hidden font-sans bg-slate-950 sm:p-6 lg:p-8">

        {{-- Ambient Decorative Glows (radial-gradient, jauh lebih ringan dari filter:blur) --}}
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[700px] h-[700px] pointer-events-none"
            style="background: radial-gradient(circle, rgba(37,99,235,0.14) 0%, transparent 70%);">
        </div>
        <div class="absolute -bottom-10 -right-10 w-[500px] h-[500px] pointer-events-none"
            style="background: radial-gradient(circle, rgba(79,70,229,0.14) 0%, transparent 70%);">
        </div>

        {{-- Main Container Card --}}
        <div
            class="relative z-10 w-full max-w-4xl bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl shadow-black/80 overflow-hidden grid grid-cols-1 md:grid-cols-12 min-h-[540px]">

            {{-- LEFT PANEL: Hero Showcase (Dark Theme) --}}
            <div
                class="relative flex flex-col justify-between p-8 overflow-hidden border-b md:col-span-5 lg:p-10 md:border-b-0 md:border-r border-slate-800 bg-slate-950/60">

                {{-- Background Image: opacity + gradient overlay saja, tanpa mix-blend-mode & transform scale --}}
                <img src="{{ asset('img/nuctech-building.jpg') }}" alt="Building"
                    class="absolute inset-0 object-cover w-full h-full opacity-25" loading="eager" decoding="async">
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/70 to-slate-950/40"></div>

                {{-- Top Branding --}}
                <div class="relative z-10 flex items-center gap-3">
                    <div
                        class="flex items-center justify-center w-10 h-10 text-xl font-black text-white bg-blue-600 border shadow-lg rounded-xl shadow-blue-600/30 border-blue-400/20">
                        W
                    </div>
                    <div>
                        <h2 class="text-base font-black leading-none tracking-tight text-white">WORKFORCE</h2>
                        <span class="text-[10px] font-bold text-blue-400 tracking-widest uppercase">HRIS & Management</span>
                    </div>
                </div>

                {{-- Middle Content Text --}}
                <div class="relative z-10 my-8 space-y-3">
                    <div
                        class="inline-flex items-center gap-2 px-3 py-1 text-xs font-bold text-blue-400 border rounded-full bg-blue-500/10 border-blue-500/20">
                        <i class="fa-solid fa-shield-halved text-[10px]"></i> Enterprise Gateway
                    </div>
                    <h1 class="text-2xl font-extrabold leading-tight tracking-tight text-white sm:text-3xl">
                        Integrated Operations Portal
                    </h1>
                    <p class="text-xs font-normal leading-relaxed text-slate-400">
                        Secure gateway for global workforce management, attendance tracking, and asset analytics.
                    </p>
                </div>

                {{-- Bottom Status Badge --}}
                <div class="relative z-10 flex items-center justify-between pt-4 border-t border-slate-800/80">
                    <div class="flex items-center gap-2 text-xs font-medium text-slate-400">
                        {{-- animate-ping dihapus dari elemen dekat blur/gradient besar untuk kurangi repaint terus-menerus di Safari; cukup dot statis --}}
                        <span class="relative flex w-2 h-2">
                            <span
                                class="absolute inline-flex w-full h-full rounded-full opacity-75 bg-emerald-400 animate-ping"></span>
                            <span class="relative inline-flex w-2 h-2 rounded-full bg-emerald-400"></span>
                        </span>
                        <span>Status: <strong class="text-emerald-400">Operational</strong></span>
                    </div>
                    <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">v2.4 PRO</span>
                </div>
            </div>

            {{-- RIGHT PANEL: Clean Light Form --}}
            <div class="flex flex-col justify-center p-8 bg-white md:col-span-7 sm:p-10 lg:p-12">

                {{-- Form Header & Logo --}}
                <div class="mb-8">
                    <div class="flex items-center justify-between mb-2">
                        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Sign In</h2>
                        <img src="{{ asset('img/logo-txt-removebg.png') }}" alt="Nuctech Logo"
                            class="hidden object-contain h-8 sm:block">
                    </div>
                    <p class="text-xs font-medium text-slate-500">
                        Enter your administrative or employee credentials below.
                    </p>
                </div>

                {{-- Login Form --}}
                <form action="{{ route('auth.login') }}" method="POST" up-target="false">
                    @csrf

                    {{-- Username Input --}}
                    <div class="space-y-1.5">
                        <label for="username" class="block text-[11px] font-bold text-slate-700 tracking-wider uppercase">
                            Username / Account ID
                        </label>
                        <div class="relative flex items-center">
                            <span
                                class="absolute left-0 z-10 flex items-center justify-center w-10 pl-1 pointer-events-none text-slate-400">
                                <i class="text-xs fa-solid fa-user text-slate-400"></i>
                            </span>
                            <input type="text" id="username" name="username" value="{{ old('username') }}" required
                                autofocus placeholder="e.g. admin_hris"
                                class="w-full py-3 pl-10 pr-4 text-sm font-semibold transition-all border text-slate-900 bg-slate-50 border-slate-200 rounded-xl focus:bg-white focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10 focus:outline-none placeholder:text-slate-400">
                        </div>
                        @error('username')
                            <p class="text-[11px] font-semibold text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Password Input --}}
                    <div class="space-y-1.5 mt-4">
                        <label for="password" class="block text-[11px] font-bold text-slate-700 tracking-wider uppercase">
                            Password
                        </label>
                        <div class="relative flex items-center" x-data="{ showPass: false }" x-cloak>
                            <span
                                class="absolute left-0 z-10 flex items-center justify-center w-10 pl-1 pointer-events-none text-slate-400">
                                <i class="text-xs fa-solid fa-lock text-slate-400"></i>
                            </span>
                            <input :type="showPass ? 'text' : 'password'" id="password" name="password" required
                                placeholder="••••••••"
                                class="w-full py-3 pl-10 pr-10 text-sm font-semibold transition-all border text-slate-900 bg-slate-50 border-slate-200 rounded-xl focus:bg-white focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10 focus:outline-none placeholder:text-slate-400">
                            <button type="button" @click="showPass = !showPass"
                                class="absolute right-0 z-10 flex items-center justify-center w-10 pr-1 text-slate-400 hover:text-slate-600 focus:outline-none">
                                <i class="text-xs fa-solid" :class="showPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                        @error('password')
                            <p class="text-[11px] font-semibold text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Submit Button --}}
                    <button type="submit"
                        class="w-full py-3.5 px-6 bg-slate-900 hover:bg-blue-600 text-white text-xs font-bold uppercase tracking-widest rounded-xl shadow-lg shadow-slate-900/20 hover:shadow-blue-600/30 active:scale-[0.99] transition-all flex items-center justify-center gap-2 group mt-6">
                        <span>Authenticate Account</span>
                        <i class="text-xs transition-transform fa-solid fa-arrow-right group-hover:translate-x-1"></i>
                    </button>
                </form>

                {{-- Footer Legal Notice --}}
                <div class="pt-5 mt-8 text-center border-t border-slate-100">
                    <p class="text-[11px] font-semibold text-slate-400">
                        Protected by Enterprise Encryption &copy; {{ date('Y') }} Workforce Systems.
                    </p>
                </div>

            </div>

        </div>
    </section>
@endsection
