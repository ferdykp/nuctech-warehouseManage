<div class="modal fade" id="plotCalendarModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="w-full mx-auto my-8 modal-dialog modal-md modal-dialog-centered">
        <div class="overflow-hidden bg-white border border-gray-100 shadow-xl rounded-xl modal-content">
            <div class="flex items-center justify-between px-4 py-3 bg-gray-900 modal-header">
                <h5 class="flex items-center gap-2 text-sm font-bold text-white modal-title">
                    <i class="text-blue-400 bi bi-calendar3"></i> Plot Sesi Absen: <span id="modalEmployeeName"
                        class="text-blue-200"></span>
                </h5>
                <button type="button"
                    class="text-xl text-gray-400 transition-colors hover:text-white focus:outline-none btn-close"
                    data-bs-dismiss="modal" aria-label="Close">×</button>
            </div>
            <div class="p-4 bg-gray-50 modal-body">
                <div class="bg-white border border-gray-200 rounded-lg overflow-y-auto max-h-[380px] shadow-sm">
                    <table class="w-full text-sm text-center border-collapse">
                        <thead class="sticky top-0 font-bold text-gray-600 bg-gray-100 border-b border-gray-200">
                            <tr>
                                <th class="px-3 py-2 text-left">Tanggal</th>
                                <th class="px-3 py-2 text-blue-600">S1</th>
                                <th class="px-3 py-2 text-yellow-600">S2</th>
                                <th class="px-3 py-2 text-red-600">S3</th>
                            </tr>
                        </thead>
                        <tbody id="calendarGridBody" class="text-gray-700 divide-y divide-gray-100"></tbody>
                    </table>
                </div>
            </div>
            <div class="px-4 py-3 border-t border-gray-100 bg-gray-50 modal-footer">
                <button type="button"
                    class="w-full py-2 text-sm font-semibold text-white transition-colors bg-gray-900 rounded-lg shadow-sm hover:bg-gray-800"
                    data-bs-dismiss="modal">Selesai & Simpan
                </button>
                {{-- <button type="submit"
                    class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm flex inline-items-center gap-2 transition-colors ml-auto">
                    <i class="bi bi-cloud-arrow-up"></i> Simpan & Perbarui Absensi
                </button> --}}

            </div>
        </div>
    </div>
</div>

{{-- <div class="modal fade" id="editEmployeeModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="max-w-md mx-auto my-8 modal-dialog modal-dialog-centered">
        <div class="overflow-hidden bg-white border border-gray-100 shadow-xl rounded-xl modal-content">
            <div class="flex items-center justify-between px-4 py-3 bg-gray-900 modal-header">
                <h5 class="text-sm font-bold text-white modal-title"><i
                        class="mr-1 text-blue-400 bi bi-pencil-square"></i> Edit Data Karyawan</h5>
                <button type="button"
                    class="text-xl text-gray-400 transition-colors hover:text-white focus:outline-none btn-close"
                    data-bs-dismiss="modal" aria-label="Close">×</button>
            </div>
            <form id="formEditEmployee" method="POST">
                @csrf
                @method('PUT')
                <div class="p-4 space-y-4 modal-body">
                    <div>
                        <label class="block mb-1 text-xs font-semibold text-gray-500">Nama Lengkap Karyawan</label>
                        <input type="text" name="name" id="edit_employee_name"
                            class="w-full px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            required>
                    </div>
                    <div>
                        <label class="block mb-1 text-xs font-semibold text-gray-500">Pindah Cabang Branch</label>
                        <select name="site_id" id="edit_employee_site_id"
                            class="w-full px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            required>
                            @foreach ($sites as $site)
                                <option value="{{ $site->id }}">{{ $site->machine_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-2 px-4 py-3 border-t border-gray-100 bg-gray-50 modal-footer">
                    <button type="button"
                        class="px-4 py-2 text-sm font-semibold text-gray-700 transition-colors border border-gray-300 rounded-lg hover:bg-gray-100"
                        data-bs-dismiss="modal">Batal</button>
                    <button type="submit"
                        class="px-4 py-2 text-sm font-semibold text-white transition-colors bg-blue-600 rounded-lg shadow-sm hover:bg-blue-700">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div> --}}

{{-- <div class="modal fade" id="detailPlotModal" tabindex="-1" aria-hidden="true">
    <div class="max-w-md mx-auto my-8 modal-dialog modal-dialog-centered">
        <div class="overflow-hidden bg-white border border-gray-100 shadow-xl rounded-xl modal-content">
            <div class="flex items-center justify-between px-4 py-3 bg-cyan-700 modal-header">
                <h5 class="flex items-center gap-2 text-sm font-bold text-white modal-title">
                    <i class="bi bi-eye"></i> Tanggal Tercentang: <span id="detailEmployeeName"
                        class="text-cyan-100"></span>
                </h5>
                <button type="button"
                    class="text-xl transition-colors text-cyan-200 hover:text-white focus:outline-none btn-close"
                    data-bs-dismiss="modal" aria-label="Close">×</button>
            </div>
            <div class="p-4 bg-gray-50 modal-body">
                <div class="bg-white border border-gray-200 rounded-lg overflow-y-auto max-h-[350px] shadow-sm">
                    <ul class="divide-y divide-gray-100" id="detailActiveDatesList">
                    </ul>
                </div>
            </div>
            <div class="px-4 py-3 border-t border-gray-100 bg-gray-50 modal-footer">
                <button type="button"
                    class="w-full py-2 text-sm font-semibold text-center text-gray-700 transition-colors bg-gray-200 rounded-lg hover:bg-gray-300"
                    data-bs-dismiss="modal">Tutup Tinjauan</button>
            </div>
        </div>
    </div>
</div> --}}

<form id="formDeleteEmployee" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>
