@extends('layout.master')

@section('title', 'Kelola Master Shift')

@section('content')
    <div class="w-full px-6 py-8">
        {{-- ============ HEADER ============ --}}
        <div class="flex flex-col justify-between gap-3 mb-6 md:flex-row md:items-center">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tighter text-black">Master Shift Management</h1>
                <p class="text-sm text-gray-500">
                    Atur master jam kerja dan shift opsional yang dipakai di seluruh site.
                </p>
                @if (Auth::user()->role === 'admin_site')
                    <p class="mt-1 text-xs font-semibold text-amber-600">
                        <i class="mr-1 fa-solid fa-lock"></i> Mode Akses: Read-only (Site Admin)
                    </p>
                @endif
            </div>
            <a href="{{ route('schedule.index') }}"
                class="flex items-center gap-1 px-4 py-2 text-xs font-bold text-gray-700 transition-colors bg-gray-100 hover:bg-gray-200 rounded-xl w-fit">
                <i class="mr-1 fa-solid fa-calendar-days"></i> Ke Halaman Jadwal
            </a>
        </div>

        {{-- ============ ALERTS ============ --}}
        @if (session('success'))
            <div
                class="flex items-center gap-2 p-4 mb-6 text-sm text-green-800 border border-green-200 bg-green-50 rounded-xl">
                <i class="text-base fa-solid fa-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 mb-6 text-sm text-red-800 border border-red-200 bg-red-50 rounded-xl">
                <div class="mb-1 font-bold">Gagal menyimpan data:</div>
                <ul class="list-disc pl-4 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- ============ FORM TAMBAH SHIFT BARU (HANYA UNTUK SUPERADMIN) ============ --}}
            @if (Auth::user()->role === 'superadmin')
                <div class="p-6 bg-white shadow-sm rounded-2xl ring-1 ring-gray-200 lg:col-span-1 h-fit">
                    <h5 class="mb-4 text-xs font-bold tracking-wider text-gray-400 uppercase">Tambah Shift Baru</h5>

                    <form action="{{ route('shift.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block mb-1 text-xs font-medium text-gray-600">Nama Shift <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="shift_name" placeholder="Contoh: Shift 1, Office Hour, OFF"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                required>
                        </div>

                        <div class="flex items-center gap-2 py-2">
                            <input type="checkbox" name="is_off" id="is_off" value="1"
                                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                onchange="toggleTimeInputs(this, 'time-inputs', 'start_time', 'end_time')">
                            <label for="is_off" class="text-xs font-semibold text-red-600 cursor-pointer">
                                Tandai sebagai Hari Libur (OFF)
                            </label>
                        </div>

                        <div id="time-inputs" class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block mb-1 text-xs font-medium text-gray-600">Jam Mulai</label>
                                <input type="time" name="start_time" id="start_time"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                    required>
                            </div>
                            <div>
                                <label class="block mb-1 text-xs font-medium text-gray-600">Jam Selesai</label>
                                <input type="time" name="end_time" id="end_time"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                    required>
                            </div>
                        </div>

                        <button type="submit"
                            class="w-full py-2.5 mt-2 text-sm font-bold text-white transition-all bg-blue-600 shadow-md hover:bg-blue-700 rounded-xl active:scale-95">
                            <i class="mr-1 fa-solid fa-plus"></i> Simpan Shift Baru
                        </button>
                    </form>
                </div>
            @endif

            {{-- ============ DAFTAR SHIFT ============ --}}
            <div
                class="overflow-hidden bg-white shadow-sm rounded-2xl ring-1 ring-gray-200 {{ Auth::user()->role === 'superadmin' ? 'lg:col-span-2' : 'lg:col-span-3' }}">
                <div class="flex items-center justify-between p-4 border-b border-gray-100 bg-gray-50/50">
                    <h5 class="text-xs font-bold tracking-wider text-gray-400 uppercase">Daftar Shift Saat Ini</h5>
                    <span class="text-xs font-semibold text-gray-400">{{ count($shifts) }} Shift Terdaftar</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead class="text-xs font-semibold text-gray-700 bg-gray-100 border-b">
                            <tr>
                                <th class="px-6 py-3 text-left">Nama Shift</th>
                                <th class="px-6 py-3 text-center">Jam Kerja</th>
                                <th class="px-6 py-3 text-center">Status</th>
                                @if (Auth::user()->role === 'superadmin')
                                    <th class="px-6 py-3 text-center">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-gray-100">
                            @forelse($shifts as $sf)
                                <tr class="transition-colors hover:bg-gray-50/50">
                                    <td class="px-6 py-4 font-bold text-gray-900">{{ $sf->shift_name }}</td>
                                    <td class="px-6 py-4 font-medium text-center text-gray-600">
                                        @if ($sf->is_off)
                                            <span class="font-normal text-gray-400">&mdash;</span>
                                        @else
                                            {{ \Carbon\Carbon::parse($sf->start_time)->format('H:i') }} -
                                            {{ \Carbon\Carbon::parse($sf->end_time)->format('H:i') }}
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if ($sf->is_off)
                                            <span
                                                class="px-2.5 py-1 text-xs font-bold text-red-700 bg-red-50 border border-red-200 rounded-full">
                                                Libur (OFF)
                                            </span>
                                        @else
                                            <span
                                                class="px-2.5 py-1 text-xs font-bold text-green-700 bg-green-50 border border-green-200 rounded-full">
                                                Masuk Kerja
                                            </span>
                                        @endif
                                    </td>
                                    @if (Auth::user()->role === 'superadmin')
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <!-- Edit Button -->
                                                <button type="button"
                                                    onclick="openEditShiftModal({{ $sf->id }}, '{{ addslashes($sf->shift_name) }}', {{ $sf->is_off ? 'true' : 'false' }}, '{{ \Carbon\Carbon::parse($sf->start_time)->format('H:i') }}', '{{ \Carbon\Carbon::parse($sf->end_time)->format('H:i') }}')"
                                                    class="p-1.5 text-xs text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                                    title="Edit Shift">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>

                                                <!-- Delete Button -->
                                                <form action="{{ route('shift.destroy', $sf->id) }}" method="POST"
                                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus shift \'{{ $sf->shift_name }}\'?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="p-1.5 text-xs text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                        title="Hapus Shift">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ Auth::user()->role === 'superadmin' ? '4' : '3' }}"
                                        class="px-6 py-12 text-xs text-center text-gray-400">
                                        Belum ada master shift terdaftar.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ MODAL EDIT SHIFT (HANYA SUPERADMIN) ============ --}}
    @if (Auth::user()->role === 'superadmin')
        <div id="editShiftModal" class="fixed inset-0 z-50 items-center justify-center hidden p-4 bg-black/50 modal-overlay"
            onclick="if(event.target===this) closeModal('editShiftModal')">
            <div class="w-full max-w-md p-6 bg-white shadow-xl rounded-2xl">
                <div class="flex items-start justify-between pb-3 mb-4 border-b">
                    <h3 class="text-base font-bold text-gray-900">Edit Master Shift</h3>
                    <button type="button" onclick="closeModal('editShiftModal')" class="text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form id="formEditShift" action="" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block mb-1 text-xs font-medium text-gray-600">Nama Shift <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="shift_name" id="edit_shift_name" required
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>

                    <div class="flex items-center gap-2 py-2">
                        <input type="checkbox" name="is_off" id="edit_is_off" value="1"
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                            onchange="toggleTimeInputs(this, 'edit-time-inputs', 'edit_start_time', 'edit_end_time')">
                        <label for="edit_is_off" class="text-xs font-semibold text-red-600 cursor-pointer">
                            Tandai sebagai Hari Libur (OFF)
                        </label>
                    </div>

                    <div id="edit-time-inputs" class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block mb-1 text-xs font-medium text-gray-600">Jam Mulai</label>
                            <input type="time" name="start_time" id="edit_start_time"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block mb-1 text-xs font-medium text-gray-600">Jam Selesai</label>
                            <input type="time" name="end_time" id="edit_end_time"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t">
                        <button type="button" onclick="closeModal('editShiftModal')"
                            class="px-4 py-2 text-xs font-semibold text-gray-600 rounded-lg hover:bg-gray-100">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-4 py-2 text-xs font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                            Simpan Perubahan
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
