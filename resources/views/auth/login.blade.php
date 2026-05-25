@extends('layout.master')

@section('content')
    <section class="relative min-h-screen flex items-center justify-center bg-[#080f1a] overflow-hidden px-4 py-10">

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
            class="relative w-full max-w-[900px] grid md:grid-cols-2 rounded-3xl overflow-hidden border border-white/[0.08] shadow-[0_32px_80px_rgba(0,0,0,0.6)]">

            {{-- ── LEFT PANEL (IMAGE FULL) ── --}}
            <div class="relative flex flex-col justify-between p-10 min-h-[550px] overflow-hidden group">

                {{-- THE FULL IMAGE --}}
                <img src="img/nuctech-building.jpg" alt="Building"
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
                <div class="relative z-10 mt-20">
                    <p class="text-cyan-400 text-[10px] tracking-[0.3em] uppercase font-bold mb-3">Core Infrastructure</p>
                    <h1 class="mb-4 text-4xl font-black leading-tight tracking-tight text-white drop-shadow-xl">
                        SECURE<br><span class="text-3xl font-bold text-blue-400">GATEWAY</span>
                    </h1>
                    <div class="w-16 h-1 mb-4 bg-blue-500 rounded-full"></div>
                    <p class="text-sm leading-relaxed text-slate-300 font-medium max-w-[240px]">
                        Authorized access only. System monitoring protocol is strictly active.
                    </p>
                </div>

                {{-- Footer Info (Bottom) --}}
                <div class="relative z-10 flex items-center gap-4">
                    <div
                        class="flex items-center gap-2 bg-black/30 backdrop-blur-md px-3 py-1.5 rounded-full border border-white/10">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse shadow-[0_0_8px_#34d399]"></span>
                        <span class="text-[10px] text-white/80 font-bold uppercase tracking-wider">Node Active</span>
                    </div>
                </div>
            </div>

            {{-- ── RIGHT PANEL (FORM) ── --}}
            <div class="flex items-center justify-center p-10 bg-white">
                <div class="w-full max-w-sm">

                    {{-- Logo --}}
                    <div class="flex justify-center mb-10">
                        <img src="img/logo-txt-removebg.png" alt="Logo" class="object-contain h-10">
                    </div>

                    <div class="mb-8">
                        <h2
                            class="text-[26px] font-black text-slate-900 tracking-tight leading-none mb-2 text-center md:text-left">
                            Sign In</h2>
                        <p class="text-sm font-medium text-center text-slate-500 md:text-left">Enter system credentials to
                            continue.</p>
                    </div>

                    {{-- Error alert --}}
                    {{-- @if ($errors->any())
                        <div
                            class="flex items-center gap-2 px-4 py-3 mb-6 text-xs font-bold text-red-600 border border-red-100 bg-red-50 rounded-xl">
                            <svg class="flex-shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2.5">
                                <circle cx="12" cy="12" r="10" />
                                <line x1="12" y1="8" x2="12" y2="12" />
                                <line x1="12" y1="16" x2="12.01" y2="16" />
                            </svg>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    @endif --}}

                    <form action="{{ route('auth.login') }}" method="POST" class="space-y-5">
                        @csrf

                        <div class="space-y-2">
                            <label for="username"
                                class="ml-1 text-xs font-bold tracking-wider uppercase text-slate-400">Username</label>
                            <div class="relative group">
                                <span
                                    class="absolute transition-colors -translate-y-1/2 left-4 top-1/2 text-slate-400 group-focus-within:text-blue-600">
                                    <i class="text-sm fa-solid fa-user"></i>
                                </span>
                                <input type="text" id="username" name="username" value="{{ old('username') }}" required
                                    autofocus
                                    class="w-full py-4 pl-12 pr-4 text-sm font-medium transition-all border outline-none bg-slate-50 border-slate-200 rounded-2xl focus:bg-white focus:border-blue-600 focus:ring-4 focus:ring-blue-600/5">
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label for="password"
                                class="ml-1 text-xs font-bold tracking-wider uppercase text-slate-400">Password</label>
                            <div class="relative group">
                                <span
                                    class="absolute transition-colors -translate-y-1/2 left-4 top-1/2 text-slate-400 group-focus-within:text-blue-600">
                                    <i class="text-sm fa-solid fa-lock"></i>
                                </span>
                                <input type="password" id="password" name="password" required
                                    class="w-full py-4 pl-12 pr-12 text-sm font-medium transition-all border outline-none bg-slate-50 border-slate-200 rounded-2xl focus:bg-white focus:border-blue-600 focus:ring-4 focus:ring-blue-600/5">
                                <button type="button" onclick="togglePassword()"
                                    class="absolute transition-colors -translate-y-1/2 right-4 top-1/2 text-slate-300 hover:text-slate-600">
                                    <i id="eye-icon" class="text-sm fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit"
                            class="w-full mt-4 py-4 bg-slate-950 text-white text-xs font-black uppercase tracking-[0.2em] rounded-2xl transition-all hover:bg-blue-700 hover:shadow-xl active:scale-[0.98]">
                            Login
                        </button>
                    </form>

                    <p class="mt-10 text-center text-[10px] font-bold text-slate-300 uppercase tracking-widest">
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
