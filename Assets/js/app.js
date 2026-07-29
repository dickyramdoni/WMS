document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-delete-confirm').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            if (!confirm(btn.dataset.confirmMsg || 'Yakin ingin menghapus data ini?')) {
                e.preventDefault();
            }
        });
    });
});
