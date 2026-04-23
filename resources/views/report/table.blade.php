{{-- TABLE BODY --}}
@forelse ($data as $index => $item)
    <tr class="border-b hover:bg-gray-50">

        @if (Auth::user()->role === 'superadmin')
            <td class="px-4 py-3 text-center">
                <input type="checkbox" class="w-4 h-4 checkbox_id" value="{{ $item->id }}">
            </td>
        @endif

        {{-- NO --}}
        <td class="px-4 py-3 text-center">
            {{ $index + 1 + ($data->currentPage() - 1) * $data->perPage() }}
        </td>

        @php
            $siteLabels = [
                'fsjkt' => 'FS6000 Jakarta',
                'fssmg' => 'FS6000 Semarang',
                'fssby' => 'FS6000 Surabaya',
                'ebeam' => 'E-Beam',
            ];
        @endphp

        <td class="px-4 py-3 text-center">
            {{ $siteLabels[$item->site_machine] ?? $item->site_machine }}
        </td>

        {{-- TYPE --}}
        <td class="px-4 py-3 text-center">
            {{ $item->attendant }}
        </td>

        {{-- STOCK --}}
        <td class="px-4 py-3 text-center">
            {{ $item->failure_date }}
        </td>

        {{-- ACTION --}}
        <td class="px-4 py-3">
            <div class="flex justify-center gap-2">

                {{-- DETAIL --}}
                <button onclick='openDetailModal(@json($item))'
                    class="px-3 py-2 font-semibold text-white bg-gray-600 rounded-lg text-md hover:bg-gray-700">
                    Detail
                </button>

                @if (Auth::user()->role === 'superadmin')
                    <a href="{{ route('report.edit', $item->id) }}"
                        class="px-3 py-2 font-semibold text-white bg-blue-600 rounded-lg text-md hover:bg-blue-700">
                        Edit
                    </a>

                    <div x-data="{ open: false }">
                        <button @click="open = true"
                            class="px-3 py-2 font-semibold text-white bg-red-600 rounded-lg text-md hover:bg-red-700">
                            Delete
                        </button>

                        {{-- MODAL --}}
                        <div x-show="open" x-cloak x-transition.opacity
                            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                            <div @click.outside="open = false" x-transition
                                class="w-full max-w-md p-6 bg-white shadow-2xl rounded-2xl">
                                <h3 class="mb-2 text-lg font-semibold text-gray-800">
                                    Konfirmasi Hapus
                                </h3>

                                <p class="mb-6 text-sm text-gray-600">
                                    Apakah kamu yakin ingin menghapus data ini?
                                    <span class="font-semibold text-red-600">Tindakan ini tidak bisa dibatalkan.</span>
                                </p>

                                <div class="flex justify-end gap-3">
                                    <button @click="open = false"
                                        class="px-4 py-2 text-sm font-semibold text-gray-600 bg-gray-200 rounded-lg hover:bg-gray-300">
                                        Batal
                                    </button>

                                    <form action="{{ route('report.destroy', $item->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                            class="px-4 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700">
                                            Ya, Hapus
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="10" class="py-6 text-center text-gray-500">
            Belum ada report
        </td>
    </tr>
@endforelse


{{-- ================= MODAL DETAIL (REPORT STYLE) ================= --}}
<div id="detailModal" class="fixed inset-0 z-50 items-center justify-center hidden bg-black/60 backdrop-blur-sm">

    <div id="modalWrapper"
        class="w-full max-w-4xl overflow-hidden transition-all duration-300 transform scale-95 bg-white shadow-2xl opacity-0 rounded-2xl">

        {{-- HEADER --}}
        <div class="flex items-center justify-between px-6 py-4 border-b bg-gradient-to-r from-gray-800 to-gray-700">
            <div>
                <h2 class="text-lg font-semibold text-white">
                    Report Detail
                </h2>
                <p class="text-xs text-gray-300">
                    Failure & Troubleshooting Report
                </p>
            </div>
            <button onclick="closeDetailModal()" class="text-2xl font-bold text-gray-300 hover:text-red-400">
                &times;
            </button>
        </div>

        {{-- BODY --}}
        <div class="grid grid-cols-1 gap-6 p-6 lg:grid-cols-3">

            {{-- LEFT: IMAGE / EVIDENCE --}}
            <div class="lg:col-span-1">
                <p class="mb-2 text-xs font-semibold text-gray-500 uppercase">
                    Evidence Image
                </p>
                <div
                    class="flex items-center justify-center w-full overflow-hidden border border-dashed h-72 rounded-xl bg-gray-50">
                    <img id="d_image" class="hidden object-contain w-full h-full">
                    <span id="no_image" class="text-sm text-gray-400">
                        No Image Available
                    </span>
                </div>
            </div>

            {{-- RIGHT: REPORT CONTENT --}}
            <div class="space-y-6 lg:col-span-2">

                {{-- META INFO --}}
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="p-4 border rounded-xl bg-gray-50">
                        <p class="text-xs text-gray-500">Attendant</p>
                        <p class="font-semibold text-gray-800" id="d_attendant"></p>
                    </div>

                    <div class="p-4 border rounded-xl bg-gray-50">
                        <p class="text-xs text-gray-500">Site Machine</p>
                        <p class="font-semibold text-gray-800" id="d_site_machine"></p>
                    </div>

                    <div class="p-4 border rounded-xl bg-gray-50">
                        <p class="text-xs text-gray-500">Series Machine</p>
                        <p class="font-semibold text-gray-800" id="d_series_machine"></p>
                    </div>

                    <div class="p-4 border rounded-xl bg-gray-50">
                        <p class="text-xs text-gray-500">Failure Date</p>
                        <p class="font-semibold text-gray-800" id="d_failure_date"></p>
                    </div>
                </div>

                {{-- FAILURE SECTION --}}
                <div class="p-5 border-l-4 border-red-500 rounded-xl bg-red-50">
                    <h3 class="mb-3 text-sm font-bold text-red-700 uppercase">
                        Failure Information
                    </h3>

                    <div class="mb-4">
                        <p class="text-xs text-gray-500">Failed Sub-System</p>
                        <p class="font-semibold text-gray-800 whitespace-pre-line" id="d_failed_subsystem"></p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500">Failure Phenomenon</p>
                        <p class="font-semibold text-gray-800 whitespace-pre-line" id="d_failure_phenomenon"></p>
                    </div>
                </div>

                {{-- TROUBLESHOOT --}}
                <div class="p-5 border-l-4 border-blue-500 rounded-xl bg-blue-50">
                    <h3 class="mb-3 text-sm font-bold text-blue-700 uppercase">
                        Troubleshooting Procedure
                    </h3>
                    <p class="font-semibold text-gray-800 whitespace-pre-line" id="d_ts_procedure"></p>
                </div>

                {{-- TIMESTAMP --}}
                <div class="flex flex-wrap gap-4 pt-2 text-xs text-gray-500">
                    <div>
                        Created:
                        <span class="font-semibold text-gray-700" id="d_created_at"></span>
                    </div>
                    <div>
                        Last Update:
                        <span class="font-semibold text-gray-700" id="d_updated_at"></span>
                    </div>
                </div>

            </div>
        </div>

        {{-- FOOTER --}}
        <div class="flex justify-end px-6 py-4 border-t bg-gray-50">
            <button onclick="closeDetailModal()"
                class="px-6 py-2 text-sm font-semibold text-white bg-gray-700 rounded-lg hover:bg-gray-800">
                Close
            </button>
        </div>
    </div>
</div>



<script>
    function openDetailModal(item) {

        const siteLabels = {
            fsjkt: 'FS6000 Jakarta',
            fssmg: 'FS6000 Semarang',
            fssby: 'FS6000 Surabaya',
            ebeam: 'E-Beam'
        };

        const formatDate = d =>
            d ? new Date(d).toLocaleDateString('en-GB') : '-';

        const formatDateTime = d =>
            d ? new Date(d).toLocaleString('en-GB') : '-';

        // ===== PARSE FAILURE NOTE =====
        let failedSubsystem = '-';
        let failurePhenomenon = '-';

        if (item.failure_note) {
            const parts = item.failure_note.split('\n\nFailure Phenomenon:\n');
            failedSubsystem = parts[0]?.replace('Failed Sub-System:\n', '') ?? '-';
            failurePhenomenon = parts[1] ?? '-';
        }

        // ===== FILL TEXT =====
        d_attendant.innerText = item.attendant;
        d_site_machine.innerText = siteLabels[item.site_machine] ?? item.site_machine;
        d_series_machine.innerText = item.series_machine;
        d_failure_date.innerText = formatDate(item.failure_date);

        d_failed_subsystem.innerText = failedSubsystem;
        d_failure_phenomenon.innerText = failurePhenomenon;
        d_ts_procedure.innerText = item.ts_procedure ?? '-';

        d_created_at.innerText = formatDateTime(item.created_at);
        d_updated_at.innerText = formatDateTime(item.updated_at);

        // ===== IMAGE =====
        if (item.image) {
            d_image.src = `/storage/${item.image}`;
            d_image.classList.remove('hidden');
            no_image.classList.add('hidden');
        } else {
            d_image.classList.add('hidden');
            no_image.classList.remove('hidden');
        }

        // ===== SHOW =====
        detailModal.classList.remove('hidden');
        detailModal.classList.add('flex');

        setTimeout(() => {
            modalWrapper.classList.remove('scale-95', 'opacity-0');
            modalWrapper.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeDetailModal() {
        modalWrapper.classList.add('scale-95', 'opacity-0');
        modalWrapper.classList.remove('scale-100', 'opacity-100');

        setTimeout(() => {
            detailModal.classList.add('hidden');
            detailModal.classList.remove('flex');
        }, 200);
    }
</script>
