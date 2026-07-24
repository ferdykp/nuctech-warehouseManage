@extends('layout.master')

@section('title', 'Master Shift Management')

@section('content')
    <div class="w-full space-y-6">
        {{-- ============ HEADER ============ --}}
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">Master Shift Management</h1>
                <p class="mt-0.5 text-xs sm:text-sm font-medium text-slate-500">
                    Configure work hours, shift templates, and off-day rules used across all sites.
                </p>
                @if (Auth::user()->role === 'admin_site')
                    <p class="mt-1 text-xs font-semibold text-amber-600 flex items-center gap-1.5">
                        <i class="fa-solid fa-lock"></i> Access Mode: Read-only (Site Admin)
                    </p>
                @endif
            </div>
            <a href="{{ route('schedule.index') }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold text-slate-700 transition-all bg-white border border-slate-200 rounded-xl hover:bg-slate-50 shadow-2xs active:scale-95 shrink-0">
                <i class="fa-solid fa-calendar-days text-slate-400"></i> Go to Schedules
            </a>
        </div>

        {{-- ============ ALERTS ============ --}}
        @if (session('success'))
            <div
                class="flex items-center gap-2.5 p-4 text-xs sm:text-sm font-semibold text-emerald-800 border border-emerald-200 bg-emerald-50 rounded-2xl">
                <i class="text-base fa-solid fa-circle-check text-emerald-600"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 space-y-1 text-xs border sm:text-sm text-rose-800 border-rose-200 bg-rose-50 rounded-2xl">
                <div class="font-bold flex items-center gap-1.5">
                    <i class="fa-solid fa-triangle-exclamation text-rose-600"></i> Failed to save shift data:
                </div>
                <ul class="list-disc pl-5 space-y-0.5 font-medium">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid items-start grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- ============ CREATE SHIFT FORM (SUPERADMIN ONLY) ============ --}}
            @if (Auth::user()->role === 'superadmin')
                <div
                    class="p-5 bg-white border shadow-sm sm:p-6 border-slate-200/80 rounded-2xl sm:rounded-3xl lg:col-span-1">
                    <h2 class="mb-4 text-xs font-extrabold tracking-wider uppercase text-slate-700">Add New Shift</h2>

                    <form action="{{ route('shift.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Shift Name <span
                                    class="text-rose-500">*</span></label>
                            <input type="text" name="shift_name" placeholder="e.g. Shift 1, Office Hours, OFF"
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 placeholder-slate-400"
                                required>
                        </div>

                        <div class="flex items-center gap-2 py-1">
                            <input type="checkbox" name="is_off" id="is_off" value="1"
                                class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500"
                                onchange="toggleTimeInputs(this, 'time-inputs', 'start_time', 'end_time')">
                            <label for="is_off" class="text-xs font-bold cursor-pointer select-none text-rose-600">
                                Mark as Off Day (OFF)
                            </label>
                        </div>

                        <div id="time-inputs" class="grid grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Start
                                    Time</label>
                                <input type="time" name="start_time" id="start_time"
                                    class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800"
                                    required>
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">End
                                    Time</label>
                                <input type="time" name="end_time" id="end_time"
                                    class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800"
                                    required>
                            </div>
                        </div>

                        <button type="submit"
                            class="w-full py-2.5 mt-2 text-xs font-bold text-white transition-all bg-blue-600 rounded-xl hover:bg-blue-700 shadow-md shadow-blue-600/20 active:scale-95">
                            <i class="mr-1 fa-solid fa-plus"></i> Save New Shift
                        </button>
                    </form>
                </div>
            @endif

            {{-- ============ SHIFT LIST TABLE ============ --}}
            <div
                class="overflow-hidden bg-white border border-slate-200/80 shadow-sm rounded-2xl sm:rounded-3xl {{ Auth::user()->role === 'superadmin' ? 'lg:col-span-2' : 'lg:col-span-3' }}">
                <div class="flex items-center justify-between p-5 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-xs font-extrabold tracking-wider uppercase text-slate-700">Registered Master Shifts</h2>
                    <span class="text-xs font-bold text-slate-400 bg-slate-100 px-2.5 py-1 rounded-lg">{{ count($shifts) }}
                        Shift(s)</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse min-w-[600px]">
                        <thead>
                            <tr
                                class="border-b bg-slate-100/70 border-slate-200/80 text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">
                                <th class="px-6 py-3.5 text-left">Shift Name</th>
                                <th class="px-6 py-3.5 text-center">Working Hours</th>
                                <th class="px-6 py-3.5 text-center">Status</th>
                                @if (Auth::user()->role === 'superadmin')
                                    <th class="px-6 py-3.5 text-center w-28">Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="text-xs font-medium divide-y divide-slate-100 sm:text-sm text-slate-700">
                            @forelse($shifts as $sf)
                                <tr class="transition-colors hover:bg-slate-50/80">
                                    <td class="px-6 py-4 font-bold text-slate-800">{{ $sf->shift_name }}</td>
                                    <td class="px-6 py-4 font-semibold text-center text-slate-600">
                                        @if ($sf->is_off)
                                            <span class="font-normal text-slate-400">&mdash;</span>
                                        @else
                                            {{ \Carbon\Carbon::parse($sf->start_time)->format('H:i') }} -
                                            {{ \Carbon\Carbon::parse($sf->end_time)->format('H:i') }}
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if ($sf->is_off)
                                            <span
                                                class="px-2.5 py-1 text-[10px] font-bold text-rose-700 bg-rose-50 border border-rose-200/60 rounded-full uppercase">
                                                Off Day (OFF)
                                            </span>
                                        @else
                                            <span
                                                class="px-2.5 py-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200/60 rounded-full uppercase">
                                                Working Shift
                                            </span>
                                        @endif
                                    </td>
                                    @if (Auth::user()->role === 'superadmin')
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <!-- Edit Button -->
                                                <button type="button"
                                                    onclick="openEditShiftModal({{ $sf->id }}, '{{ addslashes($sf->shift_name) }}', {{ $sf->is_off ? 'true' : 'false' }}, '{{ \Carbon\Carbon::parse($sf->start_time)->format('H:i') }}', '{{ \Carbon\Carbon::parse($sf->end_time)->format('H:i') }}')"
                                                    class="p-1.5 text-amber-600 transition-colors bg-amber-50 rounded-lg hover:bg-amber-600 hover:text-white"
                                                    title="Edit Shift">
                                                    <i class="text-xs fa-solid fa-pen-to-square"></i>
                                                </button>

                                                <!-- Delete Button -->
                                                <form action="{{ route('shift.destroy', $sf->id) }}" method="POST"
                                                    onsubmit="return confirm('Are you sure you want to delete shift \'{{ addslashes($sf->shift_name) }}\'?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="p-1.5 text-rose-600 transition-colors bg-rose-50 rounded-lg hover:bg-rose-600 hover:text-white"
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
                                    <td colspan="{{ Auth::user()->role === 'superadmin' ? '4' : '3' }}"
                                        class="px-6 py-12 text-center text-slate-400">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <i class="text-3xl opacity-50 text-slate-300 fa-solid fa-folder-open"></i>
                                            <p class="text-sm font-bold text-slate-700">No master shifts registered yet.</p>
                                        </div>
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
    @if (Auth::user()->role === 'superadmin')
        <div id="editShiftModal"
            class="fixed inset-0 z-50 items-center justify-center hidden p-4 transition-all duration-200 bg-slate-900/60 backdrop-blur-xs modal-overlay"
            onclick="if(event.target===this) closeModal('editShiftModal')">
            <div class="w-full max-w-md overflow-hidden bg-white shadow-2xl rounded-2xl">
                <div class="flex items-center justify-between p-5 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="text-xs font-extrabold tracking-wider uppercase text-slate-700">Edit Master Shift</h3>
                    <button type="button" onclick="closeModal('editShiftModal')"
                        class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg">
                        <i class="text-xs fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form id="formEditShift" action="" method="POST" class="p-6 space-y-4">
                    @csrf
                    @method('PUT')
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Shift Name <span
                                class="text-rose-500">*</span></label>
                        <input type="text" name="shift_name" id="edit_shift_name" required
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800">
                    </div>

                    <div class="flex items-center gap-2 py-1">
                        <input type="checkbox" name="is_off" id="edit_is_off" value="1"
                            class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500"
                            onchange="toggleTimeInputs(this, 'edit-time-inputs', 'edit_start_time', 'edit_end_time')">
                        <label for="edit_is_off" class="text-xs font-bold cursor-pointer select-none text-rose-600">
                            Mark as Off Day (OFF)
                        </label>
                    </div>

                    <div id="edit-time-inputs" class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Start
                                Time</label>
                            <input type="time" name="start_time" id="edit_start_time"
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800">
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">End Time</label>
                            <input type="time" name="end_time" id="edit_end_time"
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800">
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                        <button type="button" onclick="closeModal('editShiftModal')"
                            class="px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-5 py-2.5 text-xs font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-md shadow-blue-600/20 active:scale-95 transition-all">
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
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
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
