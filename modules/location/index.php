<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
$pageTitle = 'Lokasi / Rak Gudang';
require_once __DIR__ . '/../../includes/header.php';

$pdo = getDB();
$warehouseFilter = (int)($_GET['warehouse_id'] ?? 0);
$warehouses = $pdo->query("SELECT * FROM warehouses ORDER BY code")->fetchAll();

$sql = "SELECT l.*, w.code AS warehouse_code, w.name AS warehouse_name FROM locations l JOIN warehouses w ON w.id = l.warehouse_id";
$params = [];
if ($warehouseFilter) {
    $sql .= " WHERE l.warehouse_id = ?";
    $params[] = $warehouseFilter;
}
$sql .= " ORDER BY w.code, l.code";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$locations = $stmt->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Lokasi / Rak Gudang</h4>
    <?php if (in_array($_SESSION['role'], ['admin','supervisor'])): ?>
        <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Lokasi</a>
    <?php endif; ?>
</div>
<div class="card">
    <div class="card-body">
        <form class="row g-2 mb-3" method="GET">
            <div class="col-auto">
                <select name="warehouse_id" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Gudang</option>
                    <?php foreach ($warehouses as $w): ?>
                        <option value="<?= $w['id'] ?>" <?= $warehouseFilter == $w['id'] ? 'selected' : '' ?>><?= e($w['code']) ?> - <?= e($w['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
        <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Gudang</th><th>Kode Lokasi</th><th>Nama</th><th>Zone</th><th>Rak</th><th class="text-center">Status</th><th class="text-center">Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($locations)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data lokasi</td></tr>
            <?php else: foreach ($locations as $l): ?>
                <tr>
                    <td><span class="badge bg-secondary"><?= e($l['warehouse_code']) ?></span> <?= e($l['warehouse_name']) ?></td>
                    <td><?= e($l['code']) ?></td>
                    <td><?= e($l['name']) ?></td>
                    <td><?= e($l['zone']) ?></td>
                    <td><?= e($l['rack']) ?></td>
                    <td class="text-center"><span class="badge bg-<?= $l['is_active'] ? 'success' : 'secondary' ?>"><?= $l['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                    <td class="text-center">
                        <?php if (in_array($_SESSION['role'], ['admin','supervisor'])): ?>
                        <a href="edit.php?id=<?= $l['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="delete.php?id=<?= $l['id'] ?>&csrf_token=<?= generateCsrfToken() ?>" class="btn btn-sm btn-outline-danger btn-delete-confirm" data-confirm-msg="Hapus lokasi ini?"><i class="bi bi-trash"></i></a>
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
