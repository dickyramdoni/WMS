<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
$pageTitle = 'Gudang';
require_once __DIR__ . '/../../includes/header.php';

$pdo = getDB();
$warehouses = $pdo->query("
    SELECT w.*,
        (SELECT COUNT(*) FROM locations l WHERE l.warehouse_id = w.id) AS total_locations,
        (SELECT COALESCE(SUM(s.qty),0) FROM stock s JOIN locations l ON l.id = s.location_id WHERE l.warehouse_id = w.id) AS total_stock
    FROM warehouses w ORDER BY w.code
")->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Data Gudang</h4>
    <?php if (in_array($_SESSION['role'], ['admin','supervisor'])): ?>
        <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Gudang</a>
    <?php endif; ?>
</div>
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Kode</th><th>Nama Gudang</th><th>Alamat</th><th class="text-end">Jumlah Lokasi/Rak</th><th class="text-end">Total Stok</th><th class="text-center">Status</th><th class="text-center">Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($warehouses)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data gudang</td></tr>
            <?php else: foreach ($warehouses as $w): ?>
                <tr>
                    <td><?= e($w['code']) ?></td>
                    <td><strong><?= e($w['name']) ?></strong></td>
                    <td><?= e($w['address']) ?></td>
                    <td class="text-end"><?= formatNumber($w['total_locations']) ?></td>
                    <td class="text-end fw-semibold"><?= formatNumber($w['total_stock']) ?></td>
                    <td class="text-center"><span class="badge bg-<?= $w['is_active'] ? 'success' : 'secondary' ?>"><?= $w['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                    <td class="text-center">
                        <a href="../stock/by_warehouse.php?warehouse_id=<?= $w['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Lihat Stok"><i class="bi bi-clipboard-data"></i></a>
                        <?php if (in_array($_SESSION['role'], ['admin','supervisor'])): ?>
                        <a href="edit.php?id=<?= $w['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="delete.php?id=<?= $w['id'] ?>&csrf_token=<?= generateCsrfToken() ?>" class="btn btn-sm btn-outline-danger btn-delete-confirm" data-confirm-msg="Hapus gudang ini?"><i class="bi bi-trash"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
