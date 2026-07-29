<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
$pageTitle = 'Stok Barang';
require_once __DIR__ . '/../../includes/header.php';

$pdo = getDB();
$search = trim($_GET['q'] ?? '');

$sql = "SELECT s.*, p.sku, p.name AS product_name, p.unit, l.code AS location_code, l.name AS location_name,
               w.code AS warehouse_code, w.name AS warehouse_name
        FROM stock s
        JOIN products p ON p.id = s.product_id
        JOIN locations l ON l.id = s.location_id
        JOIN warehouses w ON w.id = l.warehouse_id
        WHERE s.qty > 0";
$params = [];
if ($search !== '') {
    $sql .= " AND (p.sku LIKE ? OR p.name LIKE ? OR l.code LIKE ? OR w.code LIKE ? OR w.name LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%", "%$search%", "%$search%"];
}
$sql .= " ORDER BY w.code, p.name, l.code";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$stocks = $stmt->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Stok Barang per Lokasi</h4>
    <?php if (in_array($_SESSION['role'], ['admin','supervisor'])): ?>
        <a href="transfer.php" class="btn btn-outline-primary"><i class="bi bi-arrow-left-right"></i> Transfer / Penyesuaian Stok</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <form class="row g-2 mb-3" method="GET">
            <div class="col-auto flex-grow-1">
                <input type="text" name="q" class="form-control" placeholder="Cari SKU, produk, kode lokasi, atau gudang..." value="<?= e($search) ?>">
            </div>
            <div class="col-auto"><button class="btn btn-outline-secondary"><i class="bi bi-search"></i> Cari</button></div>
        </form>
        <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Gudang</th><th>SKU</th><th>Produk</th><th>Lokasi</th><th class="text-end">Qty</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($stocks)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data stok</td></tr>
            <?php else: foreach ($stocks as $s): ?>
                <tr>
                    <td><span class="badge bg-secondary"><?= e($s['warehouse_code']) ?></span></td>
                    <td><?= e($s['sku']) ?></td>
                    <td><?= e($s['product_name']) ?></td>
                    <td><?= e($s['location_code']) ?> - <?= e($s['location_name']) ?></td>
                    <td class="text-end fw-semibold"><?= formatNumber($s['qty']) ?> <?= e($s['unit']) ?></td>
                    <td class="text-center"><a href="card.php?product_id=<?= $s['product_id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-clock-history"></i> Kartu Stok</a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
