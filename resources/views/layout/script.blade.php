    <script>
        $(document).ready(function() {

            const site = window.location.pathname.split('/')[1];

            // SELECT ALL
            $('#select_all_id').on('change', function() {
                $('.checkbox-id').prop('checked', this.checked);
            });

            // BULK DELETE
            $('#btn-delete').on('click', function() {

                let ids = [];

                $('.checkbox-id:checked').each(function() {
                    ids.push($(this).val());
                });

                if (ids.length === 0) {
                    alert('Pilih minimal 1 data!');
                    return;
                }

                if (!confirm('Yakin ingin menghapus data terpilih?')) return;

                $.ajax({
                    url: `/${site}/sparepart/bulk-delete`,
                    method: "POST",
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        ids: ids
                    },
                    success: function(res) {
                        if (res.success) {
                            location.reload();
                        } else {
                            alert(res.message);
                        }
                    },
                    error: function(err) {
                        console.error(err);
                        alert('Terjadi kesalahan saat menghapus data.');
                    }
                });
            });

        });
    </script>


    <script>
        $(function() {
            let timer = null;
            let xhr = null;

            $('#search').on('input', function() {
                const query = this.value.trim();
                const url = $(this).data('route');

                clearTimeout(timer);

                timer = setTimeout(() => {

                    if (xhr) xhr.abort();

                    // search mulai dari 1 karakter
                    if (query.length > 0 && query.length < 1) return;

                    xhr = $.ajax({
                        url: url,
                        method: 'GET',
                        data: {
                            search: query
                        },

                        beforeSend() {
                            $('#table-container').html(`
                        <div class="py-6 text-center text-gray-400">
                            Searching...
                        </div>
                    `);
                        },

                        success(res) {
                            $('#table-container').html(res.html);
                        },

                        error(err) {
                            if (err.statusText !== 'abort') {
                                console.error(err);
                            }
                        }
                    });

                }, 400); // debounce 400ms
            });
        });
    </script>
