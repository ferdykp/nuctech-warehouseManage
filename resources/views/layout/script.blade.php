<script>
$(document).ready(function() {
    const site = window.location.pathname.split('/')[1];
    let timer = null;
    let xhr = null;

    function fetchFilteredData() {
        const query = $('#search').val().trim();
        const condition = $('#filter-condition').val();
        const url = $('#search').data('route');

        if (xhr) xhr.abort();

        xhr = $.ajax({
            url: url,
            method: 'GET',
            data: { 
                search: query, 
                condition: condition 
            },
            beforeSend: function() {
                $('#table-container').css('opacity', '0.5');
            },
            success: function(res) {
                $('#table-container').html(res.html);
                $('#table-container').css('opacity', '1');
            },
            error: function(err) {
                if (err.statusText !== 'abort') {
                    $('#table-container').css('opacity', '1');
                }
            }
        });
    }

    $('#search').on('input', function() {
        clearTimeout(timer);
        timer = setTimeout(fetchFilteredData, 400);
    });

    $('#filter-condition').on('change', fetchFilteredData);

    $(document).on('change', '#select_all_id', function() {
        $('.checkbox-id').prop('checked', this.checked);
    });

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
                    fetchFilteredData();
                    alert('Data berhasil dihapus');
                } else {
                    alert(res.message);
                }
            }
        });
    });
});

function openCreateModal() {
    document.getElementById('modal-create').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeCreateModal() {
    document.getElementById('modal-create').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

window.onclick = function(event) {
    const createModal = document.getElementById('modal-create');
    const importModal = document.getElementById('modal-import');
    if (event.target == createModal) closeCreateModal();
    if (event.target == importModal) importModal.classList.add('hidden');
}
</script>