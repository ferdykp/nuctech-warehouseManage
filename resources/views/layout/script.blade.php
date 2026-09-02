<!-- Third-party scripts: Load jQuery standar tanpa defer agar selalu siap di DOM -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
    $(document).ready(function() {
        const pathSegments = window.location.pathname.split('/');
        const site = pathSegments[1] || '';
        let timer = null;
        let xhr = null;

        // AJAX Filter & Search Helper
        window.fetchFilteredData = function() {
            const $search = $('#search');
            if (!$search.length) return;

            const query = $search.val().trim();
            const condition = $('#filter-condition').val();
            const url = $search.data('route') || window.location.href;

            if (xhr) xhr.abort();

            xhr = $.ajax({
                url: url,
                method: 'GET',
                data: {
                    search: query,
                    condition: condition
                },
                beforeSend: function() {
                    $('#table-container').addClass('opacity-50 pointer-events-none');
                },
                success: function(res) {
                    $('#table-container').html(res.html).removeClass(
                        'opacity-50 pointer-events-none');
                },
                error: function(err) {
                    if (err.statusText !== 'abort') {
                        $('#table-container').removeClass('opacity-50 pointer-events-none');
                    }
                }
            });
        };

        // Event Listeners dengan Debounce
        $(document).on('input', '#search', function() {
            clearTimeout(timer);
            timer = setTimeout(fetchFilteredData, 350);
        });

        $(document).on('change', '#filter-condition', fetchFilteredData);

        // Checkbox Select All Toggle
        $(document).on('change', '#select_all_id', function() {
            $('.checkbox-id').prop('checked', this.checked);
        });

        // Bulk Delete Action
        $(document).on('click', '#btn-delete', function() {
            let ids = $('.checkbox-id:checked').map(function() {
                return $(this).val();
            }).get();

            if (ids.length === 0) {
                alert('Pilih minimal 1 data untuk dihapus.');
                return;
            }

            if (!confirm(`Apakah Anda yakin ingin menghapus ${ids.length} data terpilih?`)) return;

            $.ajax({
                url: `/${site}/sparepart/bulk-delete`,
                method: "POST",
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    ids: ids
                },
                success: function(res) {
                    if (res.success) {
                        fetchFilteredData();
                    } else {
                        alert(res.message || 'Gagal menghapus data.');
                    }
                },
                error: function() {
                    alert('Terjadi kesalahan pada sistem.');
                }
            });
        });
    });

    // Modal Control Global Helpers
    function openCreateModal() {
        const modal = document.getElementById('modal-create');
        if (modal) {
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }
    }

    function closeCreateModal() {
        const modal = document.getElementById('modal-create');
        if (modal) {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
    }
</script>
