@extends('layout.master')

@section('content')
    <section
        class="relative w-full h-full min-h-screen flex items-center justify-center bg-[#080f1a] overflow-hidden p-4 sm:p-6">

        {{-- Background grid --}}
        <div
            class="absolute inset-0 bg-[linear-gradient(rgba(55,138,221,0.06)_1px,transparent_1px),linear-gradient(90deg,rgba(55,138,221,0.06)_1px,transparent_1px)] bg-[size:48px_48px]">
        </div>

        {{-- Glow accents --}}
        <div
            class="absolute -top-32 -left-32 w-[500px] h-[500px] bg-[#185FA5] rounded-full opacity-20 blur-[100px] pointer-events-none">
        </div>
        <div
            class="absolute -bottom-24 -right-24 w-[400px] h-[400px] bg-[#0F6E56] rounded-full opacity-20 blur-[100px] pointer-events-none">
        </div>

        {{-- Card Container --}}
        <div
            class="relative z-10 w-full max-w-[900px] grid md:grid-cols-2 rounded-3xl overflow-hidden border border-white/[0.08] shadow-[0_32px_80px_rgba(0,0,0,0.6)] my-auto max-h-[92vh] overflow-y-auto md:overflow-hidden">

            {{-- ── LEFT PANEL (IMAGE FULL) ── --}}
            <div
                class="relative flex flex-col justify-between p-8 sm:p-10 min-h-[400px] md:min-h-[520px] overflow-hidden group">

                {{-- THE FULL IMAGE --}}
                <img src="{{ asset('img/nuctech-building.jpg') }}" alt="Building"
                    class="absolute inset-0 object-cover w-full h-full transition-transform duration-700 group-hover:scale-105">

                {{-- DARK OVERLAY (Agar teks terbaca) --}}
                <div class="absolute inset-0 z-0 bg-gradient-to-b from-slate-950/60 via-slate-950/40 to-slate-950/90"></div>

                {{-- Brand (Top) --}}
                <div class="relative z-10 flex items-center gap-3">
                    <div
                        class="w-10 h-10 bg-[#185FA5] rounded-xl flex items-center justify-center shadow-lg border border-white/20">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" class="text-white">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
                        </svg>
                    </div>
                    <span class="text-lg font-bold tracking-widest text-white drop-shadow-md">NUCTECH</span>
                </div>

                {{-- Content (Middle) --}}
                <div class="relative z-10 pt-6 pb-4 my-auto">
                    <p class="text-cyan-400 text-[10px] tracking-[0.3em] uppercase font-bold mb-2">Core Infrastructure</p>
                    <h1 class="mb-3 text-3xl font-black leading-tight tracking-tight text-white sm:text-4xl drop-shadow-xl">
                        SECURE<br><span class="text-2xl font-bold text-blue-400 sm:text-3xl">GATEWAY</span>
                    </h1>
                    <div class="w-16 h-1 mb-3 bg-blue-500 rounded-full"></div>
                    <p class="text-xs sm:text-sm leading-relaxed text-slate-300 font-medium max-w-[240px]">
                        Authorized access only. System monitoring protocol is strictly active.
                    </p>
                </div>

                {{-- Footer Info (Bottom) --}}
                <div class="relative z-10 flex items-center gap-4 pt-2">
                    <div
                        class="flex items-center gap-2 bg-black/30 backdrop-blur-md px-3 py-1.5 rounded-full border border-white/10">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse shadow-[0_0_8px_#34d399]"></span>
                        <span class="text-[10px] text-white/80 font-bold uppercase tracking-wider">Node Active</span>
                    </div>
                </div>
            </div>

            {{-- ── RIGHT PANEL (FORM) ── --}}
            <div class="flex items-center justify-center p-8 bg-white sm:p-10">
                <div class="w-full max-w-sm">

                    {{-- Logo --}}
                    <div class="flex justify-center mb-6 sm:mb-8">
                        <img src="{{ asset('img/logo-txt-removebg.png') }}" alt="Logo"
                            class="object-contain h-9 sm:h-10">
                    </div>

                    <div class="mb-6">
                        <h2
                            class="mb-1 text-2xl font-black leading-none tracking-tight text-center text-slate-900 md:text-left">
                            Sign In</h2>
                        <p class="text-xs font-medium text-center text-slate-500 md:text-left">Enter system credentials to
                            continue.</p>
                    </div>

                    <form action="{{ route('auth.login') }}" method="POST" class="space-y-4">
                        @csrf

                        <div class="space-y-1.5">
                            <label for="username"
                                class="ml-1 text-[11px] font-bold tracking-wider uppercase text-slate-400">Username</label>
                            <div class="relative group">
                                <span
                                    class="absolute transition-colors -translate-y-1/2 left-4 top-1/2 text-slate-400 group-focus-within:text-blue-600">
                                    <i class="text-sm fa-solid fa-user"></i>
                                </span>
                                <input type="text" id="username" name="username" value="{{ old('username') }}" required
                                    autofocus
                                    class="w-full py-3.5 pl-11 pr-4 text-sm font-medium transition-all border outline-none bg-slate-50 border-slate-200 rounded-2xl focus:bg-white focus:border-blue-600 focus:ring-4 focus:ring-blue-600/5">
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label for="password"
                                class="ml-1 text-[11px] font-bold tracking-wider uppercase text-slate-400">Password</label>
                            <div class="relative group">
                                <span
                                    class="absolute transition-colors -translate-y-1/2 left-4 top-1/2 text-slate-400 group-focus-within:text-blue-600">
                                    <i class="text-sm fa-solid fa-lock"></i>
                                </span>
                                <input type="password" id="password" name="password" required
                                    class="w-full py-3.5 pl-11 pr-11 text-sm font-medium transition-all border outline-none bg-slate-50 border-slate-200 rounded-2xl focus:bg-white focus:border-blue-600 focus:ring-4 focus:ring-blue-600/5">
                                <button type="button" onclick="togglePassword()"
                                    class="absolute transition-colors -translate-y-1/2 right-4 top-1/2 text-slate-300 hover:text-slate-600">
                                    <i id="eye-icon" class="text-sm fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit"
                            class="w-full mt-2 py-3.5 bg-slate-950 text-white text-xs font-black uppercase tracking-[0.2em] rounded-2xl transition-all hover:bg-blue-700 hover:shadow-xl active:scale-[0.98]">
                            Login
                        </button>
                    </form>

                    <p class="mt-8 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        &copy; {{ date('Y') }} Platform Security Node
                    </p>
                </div>
            </div>

        </div>
    </section>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('eye-icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
@endsection
