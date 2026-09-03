<!-- MOBILE OVERLAY -->
<div x-show="sidebarOpen" @click="sidebarOpen = false" x-cloak
    x-transition:enter="transition-opacity ease-linear duration-200" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-200"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm lg:hidden">
</div>

<!-- SIDEBAR NAVIGATION -->
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-50 flex flex-col justify-between w-64 transition-transform duration-300 ease-in-out border-r shadow-xl bg-slate-900 text-slate-300 lg:translate-x-0 lg:static lg:inset-0 shrink-0 border-slate-800">

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
            <button @click="sidebarOpen = false" type="button"
                class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 lg:hidden cursor-pointer">
                <i class="text-lg fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- NAVIGATION MENU -->
        <nav class="p-4 space-y-6 overflow-y-auto max-h-[calc(100vh-8rem)]">

            @php
                $baseItemClass =
                    'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all duration-150';
                $defaultClass = 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60';
                $activeClass = 'bg-blue-600 text-white shadow-md shadow-blue-600/30';
            @endphp

            <!-- MENU GROUP: GENERAL -->
            <div>
                <p class="px-3 mb-2 text-[10px] font-bold tracking-wider uppercase text-slate-500">General</p>
                <div class="space-y-1">
                    <a href="{{ route('dashboard') }}"
                        class="{{ $baseItemClass }} {{ request()->routeIs('dashboard') ? $activeClass : $defaultClass }}">
                        <i class="w-5 text-sm text-center fa-solid fa-chart-pie"></i>
                        <span>Dashboard</span>
                    </a>
                    @if (Auth::user()?->role === 'superadmin')
                        <a href="{{ route('branches.index') }}"
                            class="{{ $baseItemClass }} {{ request()->routeIs('branches.*') ? $activeClass : $defaultClass }}">
                            <i class="w-5 text-sm text-center fa-solid fa-building"></i>
                            <span>Branches</span>
                        </a>
                    @endif
                </div>
            </div>

            <!-- MENU GROUP: MACHINE MANAGEMENT -->
            @if (in_array(Auth::user()?->role, ['superadmin', 'team_leader']))

                <div>
                    <p class="px-3 mb-2 text-[10px] font-bold tracking-wider uppercase text-slate-500">Assets &
                        Operations
                    </p>
                    <div x-data="{ open: {{ request()->routeIs('sites.*') || request()->routeIs('sparepart.index') ? 'true' : 'false' }} }">
                        <button @click="open = !open" type="button"
                            class="w-full {{ $baseItemClass }} {{ $defaultClass }} justify-between cursor-pointer">
                            <div class="flex items-center gap-3">
                                <i class="w-5 text-sm text-center fa-solid fa-gears"></i>
                                <span>Machine Sites</span>
                            </div>
                            <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200"
                                :class="{ 'rotate-180': open }"></i>
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
                                    <i class="fa-solid fa-circle-plus"></i> New Machine
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
            <!-- MENU GROUP: INVENTORY -->
            <div>
                <p class="px-3 mb-2 text-[10px] font-bold tracking-wider uppercase text-slate-500">Logistics &
                    Support
                </p>
                <div class="space-y-1">
                    @if (in_array(Auth::user()?->role, ['superadmin', 'team_leader']))
                        <a href="{{ route('categories.index') }}"
                            class="{{ $baseItemClass }} {{ request()->routeIs('categories.*') ? $activeClass : $defaultClass }}">
                            <i class="w-5 text-sm text-center fa-solid fa-layer-group"></i>
                            <span>Categories</span>
                        </a>
                        <a href="{{ route('site.index') }}"
                            class="{{ $baseItemClass }} {{ request()->routeIs('site.index') ? $activeClass : $defaultClass }}">
                            <i class="w-5 text-sm text-center fa-solid fa-map-pin"></i>
                            <span>All Sites</span>
                        </a>
                        <a href="{{ route('sparepart.all') }}"
                            class="{{ $baseItemClass }} {{ request()->routeIs('sparepart.all') ? $activeClass : $defaultClass }}">
                            <i class="w-5 text-sm text-center fa-solid fa-boxes-stacked"></i>
                            <span>Spareparts</span>
                        </a>
                        <a href="{{ route('report.index') }}"
                            class="{{ $baseItemClass }} {{ request()->routeIs('report.*') ? $activeClass : $defaultClass }}">
                            <i class="w-5 text-sm text-center fa-solid fa-triangle-exclamation"></i>
                            <span>Failure Reports</span>
                        </a>
                    @endif

                    <a href="{{ route('daily_reports.index') }}"
                        class="{{ $baseItemClass }} {{ request()->routeIs('daily_reports.*') ? $activeClass : $defaultClass }}">
                        <i class="w-5 text-sm text-center fa-solid fa-calendar"></i>
                        <span>Daily Reports</span>
                    </a>
                </div>
            </div>
            <!-- MENU GROUP: HR & FINANCE -->
            <div>
                <p class="px-3 mb-2 text-[10px] font-bold tracking-wider uppercase text-slate-500">HR & Finance</p>
                <div class="space-y-1">
                    <a href="{{ route('reimbursements.index') }}"
                        class="{{ $baseItemClass }} justify-between {{ request()->routeIs('reimbursements.*') ? $activeClass : $defaultClass }}">
                        <div class="flex items-center gap-3">
                            <i class="w-5 text-sm text-center fa-solid fa-receipt"></i>
                            <span>Reimbursements</span>
                        </div>
                    </a>
                    @if (in_array(Auth::user()?->role, ['superadmin', 'administration']))
                        <a href="{{ route('employee.index') }}"
                            class="{{ $baseItemClass }} {{ request()->routeIs('employee.*') ? $activeClass : $defaultClass }}">
                            <i class="w-5 text-sm text-center fa-solid fa-users"></i>
                            <span>Employees</span>
                        </a>
                    @endif
                    <a href="{{ route('schedule.index') }}"
                        class="{{ $baseItemClass }} {{ request()->routeIs('schedule.*') ? $activeClass : $defaultClass }}">
                        <i class="w-5 text-sm text-center fa-solid fa-calendar-days"></i>
                        <span>Schedules</span>
                    </a>
                    <a href="{{ route('attendance.index') }}"
                        class="{{ $baseItemClass }} {{ request()->routeIs('attendance.*') ? $activeClass : $defaultClass }}">
                        <i class="w-5 text-sm text-center fa-solid fa-user-check"></i>
                        <span>Attendance</span>
                    </a>
                    @if (in_array(Auth::user()?->role, ['superadmin', 'adminstration']))
                        <a href="{{ route('salary.index') }}"
                            class="{{ $baseItemClass }} {{ request()->routeIs('salary.*') ? $activeClass : $defaultClass }}">
                            <i class="w-5 text-sm text-center fa-solid fa-wallet"></i>
                            <span>Payroll / Salary</span>
                        </a>
                    @endif
                    <a href="{{ route('leave.index') }}"
                        class="{{ $baseItemClass }} {{ request()->routeIs('leave.*') ? $activeClass : $defaultClass }}">
                        <i class="w-5 text-sm text-center fa-solid fa-person-walking-arrow-right"></i>
                        <span>Leave</span>
                    </a>
                </div>
            </div>

        </nav>
    </div>

    <!-- USER FOOTER CARD -->
    <div class="p-4 border-t border-slate-800 bg-slate-950/30">
        <div class="flex items-center gap-3">
            <div
                class="flex items-center justify-center font-bold border rounded-full w-9 h-9 bg-slate-800 text-slate-300 border-slate-700 shrink-0">
                {{ strtoupper(substr(auth()->user()?->username ?? 'U', 0, 2)) }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-bold truncate text-slate-200">{{ auth()->user()?->username ?? 'Guest' }}</p>
                <p class="text-[10px] font-semibold text-slate-400 capitalize truncate">
                    {{ auth()->user()?->role ?? 'User' }}</p>
            </div>
        </div>
    </div>
</aside>

<!-- MODAL ADD MACHINE (DENGAN HANDLER AJAX AUTO-REFRESH) -->
<div x-data="{
    show: false,
    loading: false,
    machine_name: '',
    branch_id: '',
    location: '',
    submitMachine() {
        if (!this.machine_name || !this.branch_id || !this.location) return;
        this.loading = true;

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
            .then(res => res.json())
            .then(data => {
                this.loading = false;
                if (data.status === 'success' || data.site) {
                    // Skenario 1: Reload halaman jika sukses agar semua state Blade segar
                    window.location.reload();
                } else {
                    // Fallback reload untuk memastikan data sync
                    window.location.reload();
                }
            })
            .catch(err => {
                this.loading = false;
                // Jika backend membalas redirect HTML standar
                window.location.reload();
            });
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
                <h3 class="text-base font-bold text-slate-800">New Machine Site</h3>
                <p class="text-xs text-slate-500">Register a new unit to the tracking system.</p>
            </div>
            <button @click="show = false" type="button"
                class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 cursor-pointer">
                <i class="text-lg fa-solid fa-xmark"></i>
            </button>
        </div>

        <form @submit.prevent="submitMachine()" class="p-6 space-y-4">
            <div>
                <label class="block mb-1 text-xs font-bold text-slate-700">Machine Name</label>
                <input type="text" x-model="machine_name" required
                    class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white focus:outline-none transition-all"
                    placeholder="e.g., FS6000 Jakarta Main">
            </div>
            <div>
                <label class="block mb-1 text-xs font-bold text-slate-700">Branch Location</label>
                <select x-model="branch_id" required
                    class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white focus:outline-none transition-all cursor-pointer">
                    <option value="" disabled selected>Choose a branch...</option>
                    @foreach ($globalBranches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block mb-1 text-xs font-bold text-slate-700">Detailed Address</label>
                <textarea x-model="location" rows="3" required
                    class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white focus:outline-none transition-all"
                    placeholder="Street name, Floor, or specific coordinates..."></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" @click="show = false"
                    class="px-4 py-2 text-xs font-semibold cursor-pointer text-slate-600 hover:text-slate-800">Cancel</button>
                <button type="submit" :disabled="loading"
                    class="flex items-center gap-2 px-5 py-2 text-xs font-bold text-white transition-all bg-blue-600 shadow-md cursor-pointer hover:bg-blue-700 shadow-blue-500/20 rounded-xl">
                    <span x-show="loading" class="animate-spin"><i class="fa-solid fa-circle-notch"></i></span>
                    <span x-text="loading ? 'Registering...' : 'Register Machine'"></span>
                </button>
            </div>
        </form>
    </div>
</div>
