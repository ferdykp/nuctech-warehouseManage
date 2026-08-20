@extends('layout.master')

@section('title', 'Master Shift Management')

@section('content')
    <div class="w-full space-y-6">

        {{-- 1. HEADER CARD (TERPISAH) --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <nav class="flex items-center gap-2 mb-1.5 text-xs font-bold tracking-wider text-slate-400 uppercase">
                        <span class="transition-colors cursor-pointer hover:text-blue-600">Attendance & Roster</span>
                        <i class="fa-solid fa-chevron-right text-[9px]"></i>
                        <span class="font-extrabold text-blue-600">Master Shifts</span>
                    </nav>
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                        Master Shift Management
                    </h1>
                    <p class="mt-1 text-xs font-semibold sm:text-sm text-slate-500">
                        Configure work hours, shift templates, and off-day rules used across all operational sites.
                    </p>
                    @if (Auth::user()?->role === 'admin_site')
                        <p
                            class="mt-2 text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200/80 px-3 py-1 rounded-full inline-flex items-center gap-1.5">
                            <i class="fa-solid fa-lock"></i> Access Mode: Read-only (Site Admin)
                        </p>
                    @endif
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <a href="{{ route('schedule.index') }}"
                        class="flex items-center justify-center gap-2 px-5 py-3 text-xs font-bold transition-all bg-white border sm:text-sm text-slate-700 border-slate-200 rounded-xl hover:bg-slate-50 active:scale-95 shadow-2xs">
                        <i class="text-xs fa-solid fa-calendar-days text-slate-400"></i>
                        <span>Go to Schedules</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- 2. ALERTS SECTION --}}
        @if (session('success'))
            <div
                class="flex items-center gap-3 p-4 text-xs font-bold border sm:text-sm text-emerald-800 border-emerald-200/80 bg-emerald-50 rounded-2xl shadow-2xs">
                <i class="text-base fa-solid fa-circle-check text-emerald-600 shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div
                class="p-4 space-y-1.5 text-xs sm:text-sm border text-rose-800 border-rose-200/80 bg-rose-50 rounded-2xl shadow-2xs">
                <div class="flex items-center gap-2 font-extrabold">
                    <i class="fa-solid fa-triangle-exclamation text-rose-600 shrink-0"></i> Failed to save shift data:
                </div>
                <ul class="list-disc pl-5 space-y-0.5 font-semibold">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- 3. MAIN WORKSPACE GRID --}}
        <div class="grid items-start grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- ============ CREATE SHIFT FORM CARD (SUPERADMIN ONLY) ============ --}}
            @if (Auth::user()?->role === 'superadmin')
                <div class="overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl lg:col-span-1">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h2 class="text-xs font-extrabold tracking-wider uppercase text-slate-700">Add New Shift</h2>
                    </div>

                    <form action="{{ route('shift.store') }}" method="POST" class="p-6 space-y-5">
                        @csrf
                        {{-- SHIFT NAME --}}
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                                Shift Name <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="shift_name" placeholder="e.g. Shift 1, Office Hours, OFF"
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 placeholder-slate-400"
                                required>
                        </div>

                        {{-- OFF DAY CHECKBOX --}}
                        <div class="flex items-center gap-2.5 p-3 rounded-2xl bg-rose-50/50 border border-rose-100">
                            <input type="checkbox" name="is_off" id="is_off" value="1"
                                class="w-4 h-4 rounded cursor-pointer text-rose-600 border-slate-300 focus:ring-rose-500"
                                onchange="toggleTimeInputs(this, 'time-inputs', 'start_time', 'end_time')">
                            <label for="is_off" class="text-xs font-bold cursor-pointer select-none text-rose-700">
                                Mark as Off Day (OFF)
                            </label>
                        </div>

                        {{-- WORKING HOURS INPUTS --}}
                        <div id="time-inputs" class="grid grid-cols-2 gap-3 transition-all duration-200">
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                                    Start Time
                                </label>
                                <input type="time" name="start_time" id="start_time"
                                    class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-semibold border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800"
                                    required>
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                                    End Time
                                </label>
                                <input type="time" name="end_time" id="end_time"
                                    class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-semibold border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800"
                                    required>
                            </div>
                        </div>

                        {{-- SUBMIT BUTTON --}}
                        <button type="submit"
                            class="w-full py-3 text-xs font-bold text-white transition-all bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-600/20 active:scale-[0.98]">
                            <i class="mr-1.5 fa-solid fa-plus"></i> Save New Shift
                        </button>
                    </form>
                </div>
            @endif

            {{-- ============ SHIFT LIST TABLE CARD ============ --}}
            <div
                class="overflow-hidden bg-white border border-slate-200/80 shadow-xs rounded-3xl {{ Auth::user()?->role === 'superadmin' ? 'lg:col-span-2' : 'lg:col-span-3' }}">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-xs font-extrabold tracking-wider uppercase text-slate-700">Registered Master Shifts</h2>
                    <span
                        class="text-[10px] font-extrabold text-slate-600 bg-slate-100 border border-slate-200/80 px-3 py-1 rounded-full uppercase">
                        {{ count($shifts) }} Shift(s) Registered
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[550px]">
                        <thead>
                            <tr
                                class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 bg-slate-50 border-b border-slate-100">
                                <th class="px-6 py-4">Shift Name</th>
                                <th class="px-6 py-4 text-center">Working Hours</th>
                                <th class="px-6 py-4 text-center">Status</th>
                                @if (Auth::user()?->role === 'superadmin')
                                    <th class="w-32 px-6 py-4 text-center">Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="text-xs font-medium divide-y divide-slate-100 text-slate-700">
                            @forelse($shifts as $sf)
                                <tr class="transition-colors hover:bg-slate-50/60">
                                    <td class="px-6 py-4 text-sm font-extrabold text-slate-900">
                                        {{ $sf->shift_name }}
                                    </td>

                                    <td class="px-6 py-4 font-bold text-center text-slate-600">
                                        @if ($sf->is_off)
                                            <span class="font-bold text-slate-300">&mdash;</span>
                                        @else
                                            <span
                                                class="px-2.5 py-1 bg-slate-100 border border-slate-200 rounded-lg text-slate-700 font-mono text-[11px]">
                                                {{ \Carbon\Carbon::parse($sf->start_time)->format('H:i') }} -
                                                {{ \Carbon\Carbon::parse($sf->end_time)->format('H:i') }}
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 text-center">
                                        @if ($sf->is_off)
                                            <span
                                                class="px-3 py-1 text-[10px] font-extrabold text-rose-800 bg-rose-50 border border-rose-200 rounded-full uppercase">
                                                Off Day (OFF)
                                            </span>
                                        @else
                                            <span
                                                class="px-3 py-1 text-[10px] font-extrabold text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-full uppercase">
                                                Working Shift
                                            </span>
                                        @endif
                                    </td>

                                    @if (Auth::user()?->role === 'superadmin')
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                {{-- EDIT BUTTON --}}
                                                <button type="button"
                                                    onclick="openEditShiftModal({{ $sf->id }}, '{{ addslashes($sf->shift_name) }}', {{ $sf->is_off ? 'true' : 'false' }}, '{{ \Carbon\Carbon::parse($sf->start_time)->format('H:i') }}', '{{ \Carbon\Carbon::parse($sf->end_time)->format('H:i') }}')"
                                                    class="flex items-center justify-center w-8 h-8 transition-all border rounded-xl text-amber-600 bg-amber-50 border-amber-100 hover:bg-amber-600 hover:text-white active:scale-95"
                                                    title="Edit Shift">
                                                    <i class="text-xs fa-solid fa-pen-to-square"></i>
                                                </button>

                                                {{-- DELETE BUTTON --}}
                                                <form action="{{ route('shift.destroy', $sf->id) }}" method="POST"
                                                    class="inline"
                                                    onsubmit="return confirm('Are you sure you want to delete shift \'{{ addslashes($sf->shift_name) }}\'?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="flex items-center justify-center w-8 h-8 transition-all border rounded-xl text-rose-600 bg-rose-50 border-rose-100 hover:bg-rose-600 hover:text-white active:scale-95"
                                                        title="Delete Shift">
                                                        <i class="text-xs fa-solid fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ Auth::user()?->role === 'superadmin' ? '4' : '3' }}"
                                        class="p-12 text-center text-slate-400">
                                        <div
                                            class="flex items-center justify-center w-12 h-12 mx-auto mb-3 text-xl rounded-2xl bg-slate-100 text-slate-400">
                                            <i class="fa-solid fa-clock"></i>
                                        </div>
                                        <p class="text-sm font-bold text-slate-800">No Master Shifts Registered Yet</p>
                                        <p class="mt-1 text-xs text-slate-400">Start by creating shift templates using the
                                            panel form.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ EDIT SHIFT MODAL (SUPERADMIN ONLY) ============ --}}
    @if (Auth::user()?->role === 'superadmin')
        <div id="editShiftModal"
            class="fixed inset-0 z-50 items-center justify-center hidden p-4 transition-all duration-200 bg-slate-900/60 backdrop-blur-xs modal-overlay"
            onclick="if(event.target===this) closeModal('editShiftModal')">
            <div class="w-full max-w-md overflow-hidden bg-white border shadow-2xl border-slate-100 rounded-3xl">
                <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="text-xs font-extrabold tracking-wider uppercase text-slate-700">Edit Master Shift</h3>
                    <button type="button" onclick="closeModal('editShiftModal')"
                        class="flex items-center justify-center w-8 h-8 transition-colors rounded-lg text-slate-400 hover:text-slate-600 bg-slate-100 hover:bg-slate-200">&times;</button>
                </div>

                <form id="formEditShift" action="" method="POST" class="p-6 space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Shift Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="shift_name" id="edit_shift_name" required
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800">
                    </div>

                    <div class="flex items-center gap-2.5 p-3 rounded-2xl bg-rose-50/50 border border-rose-100">
                        <input type="checkbox" name="is_off" id="edit_is_off" value="1"
                            class="w-4 h-4 rounded cursor-pointer text-rose-600 border-slate-300 focus:ring-rose-500"
                            onchange="toggleTimeInputs(this, 'edit-time-inputs', 'edit_start_time', 'edit_end_time')">
                        <label for="edit_is_off" class="text-xs font-bold cursor-pointer select-none text-rose-700">
                            Mark as Off Day (OFF)
                        </label>
                    </div>

                    <div id="edit-time-inputs" class="grid grid-cols-2 gap-3 transition-all duration-200">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                                Start Time
                            </label>
                            <input type="time" name="start_time" id="edit_start_time"
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-semibold border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800">
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                                End Time
                            </label>
                            <input type="time" name="end_time" id="edit_end_time"
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-semibold border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800">
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <button type="button" onclick="closeModal('editShiftModal')"
                            class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-6 py-2.5 text-xs font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-md shadow-blue-600/20 active:scale-[0.98] transition-all">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <script>
        function toggleTimeInputs(checkbox, containerId, startId, endId) {
            const timeInputs = document.getElementById(containerId);
            const startTime = document.getElementById(startId);
            const endTime = document.getElementById(endId);

            if (!timeInputs || !startTime || !endTime) return;

            if (checkbox.checked) {
                timeInputs.classList.add('opacity-40', 'pointer-events-none');
                startTime.removeAttribute('required');
                endTime.removeAttribute('required');
                startTime.value = '';
                endTime.value = '';
            } else {
                timeInputs.classList.remove('opacity-40', 'pointer-events-none');
                startTime.setAttribute('required', '');
                endTime.setAttribute('required', '');
            }
        }

        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        function openEditShiftModal(id, name, isOff, startTime, endTime) {
            const form = document.getElementById('formEditShift');
            form.action = '/shift/' + id;

            document.getElementById('edit_shift_name').value = name;
            const isOffCheckbox = document.getElementById('edit_is_off');
            isOffCheckbox.checked = isOff;

            document.getElementById('edit_start_time').value = startTime || '';
            document.getElementById('edit_end_time').value = endTime || '';

            toggleTimeInputs(isOffCheckbox, 'edit-time-inputs', 'edit_start_time', 'edit_end_time');

            openModal('editShiftModal');
        }
    </script>
@endsection
