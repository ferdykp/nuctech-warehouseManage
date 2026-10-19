<!-- MOBILE OVERLAY -->
<div x-show="sidebarOpen" @click="sidebarOpen = false" x-cloak
    x-transition:enter="transition-opacity ease-linear duration-200" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-200"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm lg:hidden">
</div>

<!-- SIDEBAR NAVIGATION -->
<aside id="main-sidebar" :inert="!desktop && !sidebarOpen" :aria-hidden="!desktop && !sidebarOpen"
    :class="{ 'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen }"
    class="fixed inset-y-0 left-0 z-50 -translate-x-full flex flex-col justify-between w-64 transition-transform duration-300 ease-in-out border-r shadow-xl bg-slate-900 text-slate-300 lg:translate-x-0 lg:static lg:inset-0 shrink-0 border-slate-800">

    <!-- LOGO / BRANDING -->
    <div>
        <div class="flex items-center justify-between h-16 px-6 border-b bg-slate-950/50 border-slate-800/80">
            <div class="flex items-center gap-3">
                <div
                    class="flex items-center justify-center w-8 h-8 text-base font-black text-white bg-blue-600 rounded-lg shadow-md shadow-blue-500/20">
                    N
                </div>
                <span class="text-base font-bold tracking-tight text-white">NUCINDO</span>
            </div>
            <button @click="sidebarOpen = false" aria-label="Tutup menu utama" type="button"
                class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 lg:hidden cursor-pointer">
                <i class="text-lg fa-solid fa-xmark"></i>
            </button>
        </div>

        <nav id="sidebar-menu" data-scroll-key="sidebar-scroll-{{ auth()->id() }}" aria-label="Menu utama" class="p-4 space-y-5 overflow-y-auto max-h-[calc(100dvh-8rem)]">
            @php
                $baseItemClass =
                    'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-150';
                $defaultClass = 'text-slate-300 hover:text-white hover:bg-slate-800/60';
                $activeClass = 'bg-blue-600 text-white shadow-md shadow-blue-600/30';
            @endphp
            <div>
                <p class="px-3 mb-2 text-xs font-bold tracking-wide text-slate-400">Beranda</p>
                <div class="space-y-1">
                    <a href="{{ route('dashboard') }}"
                        class="{{ $baseItemClass }} {{ request()->routeIs('dashboard') ? $activeClass : $defaultClass }}">
                        <i class="w-5 text-sm text-center fa-solid fa-chart-pie" aria-hidden="true">
                        </i>
                        <span>Dashboard</span>
                    </a>
                </div>
            </div>
            @if (in_array(Auth::user()?->role, ['superadmin', 'team_leader']))
                <div>
                    <p class="px-3 mb-2 text-xs font-bold tracking-wide text-slate-400">Inventaris</p>
                    <div class="space-y-1">
                        <a href="{{ route('sparepart.all') }}"
                            class="{{ $baseItemClass }} {{ request()->routeIs('sparepart.all') ? $activeClass : $defaultClass }}">
                            <i class="w-5 text-sm text-center fa-solid fa-boxes-stacked" aria-hidden="true">
                            </i>
                            <span>Semua Stok</span>
                        </a>
                        <div x-data="{ open: {{ request()->routeIs('sites.*') || request()->routeIs('sparepart.index') ? 'true' : 'false' }} }">
                            <button @click="open = !open" :aria-expanded="open" aria-controls="sidebar-machine-list"
                                type="button"
                                class="w-full {{ $baseItemClass }} {{ $defaultClass }} justify-between cursor-pointer">
                                <div class="flex items-center gap-3">
                                    <i class="w-5 text-sm text-center fa-solid fa-gears">
                                    </i>
                                    <span>Stok per Lokasi</span>
                                </div>
                                <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200"
                                    :class="{ 'rotate-180': open }">
                                </i>
                            </button>

                            <div x-show="open" x-cloak x-collapse
                                class="pl-3 mt-1 ml-4 space-y-1 border-l border-slate-800" id="sidebar-machine-list">
                                @foreach ($sidebarSites as $site)
                                    <a href="{{ route('sparepart.index', $site->slug) }}"
                                        class="block px-3 py-1.5 text-xs font-medium rounded-lg transition-colors truncate
                                {{ request()->segment(2) == $site->slug ? 'text-blue-400 font-bold bg-blue-500/10' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }}">
                                        {{ $site->machine_name }}
                                    </a>
                                @endforeach

                                @if (Auth::user()?->role === 'superadmin')
                                    <button @click="$dispatch('open-add-machine'); sidebarOpen = false" type="button"
                                        class="flex items-center gap-2 w-full px-3 py-1.5 text-xs font-bold text-emerald-400 hover:bg-emerald-500/10 rounded-lg transition-all mt-1 cursor-pointer">
                                        <i class="fa-solid fa-circle-plus">
                                        </i> Mesin Baru
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            <div>
                <p class="px-3 mb-2 text-xs font-bold tracking-wide text-slate-400">Laporan</p>
                <div class="space-y-1">
                    @if (in_array(Auth::user()?->role, ['superadmin', 'team_leader']))
                        <a href="{{ route('report.index') }}"
                            class="{{ $baseItemClass }} {{ request()->routeIs('report.*') ? $activeClass : $defaultClass }}">
                            <i class="w-5 text-sm text-center fa-solid fa-triangle-exclamation" aria-hidden="true">
                            </i>
                            <span>Laporan Kerusakan</span>
                        </a>
                    @endif
                    <a href="{{ route('daily_reports.index') }}"
                        class="{{ $baseItemClass }} {{ request()->routeIs('daily_reports.*') ? $activeClass : $defaultClass }}">
                        <i class="w-5 text-sm text-center fa-solid fa-calendar" aria-hidden="true">
                        </i>
                        <span>Laporan
                            Harian</span>
                    </a>
                </div>
            </div>
            <div>
                <p class="px-3 mb-2 text-xs font-bold tracking-wide text-slate-400">Kepegawaian</p>
                <div class="space-y-1">
                    @if (Auth::user()?->role === 'superadmin')
                        <a href="{{ route('employee.index') }}"
                            class="{{ $baseItemClass }} {{ request()->routeIs('employee.*') ? $activeClass : $defaultClass }}">
                            <i class="w-5 text-sm text-center fa-solid fa-users" aria-hidden="true">
                            </i>
                            <span>Karyawan</span>
                        </a>
                    @endif
                    @if(in_array(Auth::user()?->role, ['superadmin', 'administration']))
                    <a href="{{ route('contracts.index') }}" class="{{ $baseItemClass }} {{ request()->routeIs('contracts.*') ? $activeClass : $defaultClass }}"><i class="w-5 text-sm text-center fa-solid fa-file-contract" aria-hidden="true"></i><span>Kontrak Karyawan</span></a>
                    @endif
                    <a href="{{ route('schedule.index') }}"
                        class="{{ $baseItemClass }} {{ request()->routeIs('schedule.*') ? $activeClass : $defaultClass }}">
                        <i class="w-5 text-sm text-center fa-solid fa-calendar-days" aria-hidden="true">
                        </i>
                        <span>Jadwal Kerja</span>
                    </a>
                    <a href="{{ route('attendance.index') }}"
                        class="{{ $baseItemClass }} {{ request()->routeIs('attendance.*') ? $activeClass : $defaultClass }}">
                        <i class="w-5 text-sm text-center fa-solid fa-user-check" aria-hidden="true">
                        </i>
                        <span>Absensi</span>
                    </a>
                    <a href="{{ route('leave.index') }}"
                        class="{{ $baseItemClass }} {{ request()->routeIs('leave.*') ? $activeClass : $defaultClass }}">
                        <i class="w-5 text-sm text-center fa-solid fa-person-walking-arrow-right" aria-hidden="true">
                        </i>
                        <span>Cuti dan Izin</span>
                    </a>
                </div>
            </div>
            <div>
                <p class="px-3 mb-2 text-xs font-bold tracking-wide text-slate-400">Keuangan</p>
                <div class="space-y-1">
                    <a href="{{ route('reimbursements.index') }}"
                        class="{{ $baseItemClass }} {{ request()->routeIs('reimbursements.*') ? $activeClass : $defaultClass }}">
                        <i class="w-5 text-sm text-center fa-solid fa-receipt" aria-hidden="true">
                        </i>
                        <span>Reimbursement</span>
                    </a>
                    @if (Auth::user()?->role === 'superadmin')
                        <a href="{{ route('salary.index') }}"
                            class="{{ $baseItemClass }} {{ request()->routeIs('salary.*') ? $activeClass : $defaultClass }}">
                            <i class="w-5 text-sm text-center fa-solid fa-wallet" aria-hidden="true">
                            </i>
                            <span>Penggajian</span>
                        </a>
                    @endif
                </div>
            </div>
            @if (in_array(Auth::user()?->role, ['superadmin', 'team_leader']))
                <div>
                    <p class="px-3 mb-2 text-xs font-bold tracking-wide text-slate-400">Data Master</p>
                    <div class="space-y-1">
                        <a href="{{ route('site.index') }}"
                            class="{{ $baseItemClass }} {{ request()->routeIs('site.*') ? $activeClass : $defaultClass }}">
                            <i class="w-5 text-sm text-center fa-solid fa-map-pin" aria-hidden="true">
                            </i>
                            <span>Daftar
                                Lokasi Mesin</span>
                        </a>
                        <a href="{{ route('categories.index') }}"
                            class="{{ $baseItemClass }} {{ request()->routeIs('categories.*') ? $activeClass : $defaultClass }}">
                            <i class="w-5 text-sm text-center fa-solid fa-layer-group" aria-hidden="true">
                            </i>
                            <span>Kategori Sparepart</span>
                        </a>
                        @if (Auth::user()?->role === 'superadmin')
                            <a href="{{ route('branches.index') }}"
                                class="{{ $baseItemClass }} {{ request()->routeIs('branches.*') ? $activeClass : $defaultClass }}">
                                <i class="w-5 text-sm text-center fa-solid fa-building" aria-hidden="true">
                                </i>
                                <span>Cabang</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </nav>
        <script>
            // Restore before the deferred application bundle to avoid a jump from the top.
            (() => {
                const menu = document.getElementById('sidebar-menu');
                const key = menu.dataset.scrollKey;
                const restore = () => {
                    try {
                        const saved = sessionStorage.getItem(key);
                        const top = Number(saved);
                        if (saved !== null && Number.isFinite(top) && top >= 0) menu.scrollTop = top;
                    } catch { /* Sidebar navigation also works when storage is unavailable. */ }
                };
                const save = () => {
                    try { sessionStorage.setItem(key, String(menu.scrollTop)); }
                    catch { /* Storage may be disabled by the browser. */ }
                };
                restore();
                // Fonts and stylesheet loading can change the scrollable height.
                window.addEventListener('load', restore, { once: true });
                menu.addEventListener('scroll', save, { passive: true });
                menu.addEventListener('click', save, { capture: true });
                window.addEventListener('pagehide', save);
            })();
        </script>
    </div>

    <!-- USER FOOTER CARD -->
    <div class="p-4 border-t border-slate-800 bg-slate-950/30">
        <div class="flex items-center gap-3">
            <div
                class="flex items-center justify-center font-bold border rounded-full w-9 h-9 bg-slate-800 text-slate-300 border-slate-700 shrink-0">
                {{ strtoupper(substr(auth()->user()?->username ?? 'U', 0, 2)) }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-bold truncate text-slate-200">{{ auth()->user()?->username ?? 'Tamu' }}</p>
                <p class="text-xs font-semibold text-slate-400 capitalize truncate">
                    {{ \App\Support\UiText::label(auth()->user()?->role) }}</p>
            </div>
        </div>
    </div>
</aside>

<!-- MODAL ADD MACHINE (DENGAN HANDLER AJAX AUTO-REFRESH) -->
<div x-data="{
    show: false,
    loading: false,
    error: '',
    machine_name: '',
    branch_id: '',
    location: '',
    submitMachine() {
        if (!this.machine_name || !this.branch_id || !this.location) return;
        if (this.loading) return;
        this.loading = true;
        this.error = '';

        fetch('{{ route('sites.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    machine_name: this.machine_name,
                    branch_id: this.branch_id,
                    location: this.location
                })
            })
            .then(async res => {
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    const messages = Object.values(data.errors || {}).flat();
                    throw new Error(messages.join(' ') || data.message || 'Lokasi gagal disimpan. Periksa isian dan coba lagi.');
                }
                window.location.reload();
            })
            .catch(err => {
                this.error = err.message || 'Koneksi terputus. Coba lagi; isian Anda tetap tersimpan di formulir ini.';
            })
            .finally(() => { this.loading = false; });
    }
}" x-on:open-add-machine.window="show = true" x-show="show" x-cloak
    class="fixed inset-0 z-[70] flex items-center justify-center p-4">
    <div x-show="show" x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>

    <div x-show="show" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        @click.away="show = false"
        class="relative z-10 w-full max-w-lg overflow-hidden bg-white border shadow-2xl rounded-2xl border-slate-100">

        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-bold text-slate-800">Lokasi Mesin Baru</h3>
                <p class="text-xs text-slate-500">Daftarkan unit mesin baru.</p>
            </div>
            <button @click="show = false" type="button"
                class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 cursor-pointer">
                <i class="text-lg fa-solid fa-xmark"></i>
            </button>
        </div>

        <form @submit.prevent="submitMachine()" class="p-6 space-y-4">
            <p x-show="error" x-text="error" role="alert"
                class="p-3 text-sm border rounded-xl border-rose-200 bg-rose-50 text-rose-800"></p>
            <div>
                <label class="block mb-1 text-xs font-bold text-slate-700">Nama Mesin</label>
                <input type="text" x-model="machine_name" required
                    class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white focus:outline-none transition-all"
                    placeholder="Contoh: FS6000 Jakarta Main">
            </div>
            <div>
                <label class="block mb-1 text-xs font-bold text-slate-700">Lokasi Cabang</label>
                <select x-model="branch_id" required
                    class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white focus:outline-none transition-all cursor-pointer">
                    <option value="" disabled selected>Pilih cabang...</option>
                    {{-- Ambil data branch langsung secara fresh dari DB --}}
                    @foreach (\App\Models\Branch::orderBy('branch_name', 'asc')->get() as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block mb-1 text-xs font-bold text-slate-700">Alamat Lengkap</label>
                <textarea x-model="location" rows="3" maxlength="255" required
                    class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white focus:outline-none transition-all"
                    placeholder="Nama jalan, lantai, atau koordinat lokasi..."></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" @click="show = false"
                    class="px-4 py-2 text-xs font-semibold cursor-pointer text-slate-600 hover:text-slate-800">Batal</button>
                <button type="submit" :disabled="loading"
                    class="flex items-center gap-2 px-5 py-2 text-xs font-bold text-white transition-all bg-blue-600 shadow-md cursor-pointer hover:bg-blue-700 shadow-blue-500/20 rounded-xl">
                    <span x-show="loading" class="animate-spin"><i class="fa-solid fa-circle-notch"></i></span>
                    <span x-text="loading ? 'Menyimpan...' : 'Simpan Mesin'"></span>
                </button>
            </div>
        </form>
    </div>
</div>
