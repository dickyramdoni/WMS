<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
$pageTitle = 'Detail Stok Gudang';
$pdo = getDB();
$productId = (int)($_GET['product_id'] ?? 0);
$warehouseId = (int)($_GET['warehouse_id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM warehouses WHERE id = ?");
$stmt->execute([$warehouseId]);
$warehouse = $stmt->fetch();

if (!$product || !$warehouse) {
    setFlash('error', 'Data tidak ditemukan.');
    redirect(APP_URL . '/modules/stock/by_warehouse.php');
}

$stockPerLocation = $pdo->prepare("
    SELECT l.code, l.name, l.zone, l.rack, s.qty
    FROM stock s JOIN locations l ON l.id = s.location_id
    WHERE s.product_id = ? AND l.warehouse_id = ?
    ORDER BY l.code
");
$stockPerLocation->execute([$productId, $warehouseId]);
$stockPerLocation = $stockPerLocation->fetchAll();

$movements = $pdo->prepare("
    SELECT sm.*, l.code AS location_code, u.full_name AS user_name
    FROM stock_movements sm
    JOIN locations l ON l.id = sm.location_id
    LEFT JOIN users u ON u.id = sm.created_by
    WHERE sm.product_id = ? AND l.warehouse_id = ?
    ORDER BY sm.id DESC
    LIMIT 50
");
$movements->execute([$productId, $warehouseId]);
$movements = $movements->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<h4 class="mb-1"><?= e($product['sku']) ?> - <?= e($product['name']) ?></h4>
<p class="text-muted mb-3">di <strong><?= e($warehouse['code']) ?> - <?= e($warehouse['name']) ?></strong></p>

<div class="card mb-3">
    <div class="card-header bg-white fw-semibold">Rincian per Lokasi/Rak</div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead><tr><th>Kode Lokasi</th><th>Nama</th><th>Zone</th><th>Rak</th><th class="text-end">Qty</th></tr></thead>
            <tbody>
            <?php if (empty($stockPerLocation)): ?>
                <tr><td colspan="5" class="text-center text-muted py-3">Tidak ada stok produk ini di gudang tersebut</td></tr>
            <?php else: foreach ($stockPerLocation as $s): ?>
                <tr>
                    <td><?= e($s['code']) ?></td>
                    <td><?= e($s['name']) ?></td>
                    <td><?= e($s['zone']) ?></td>
                    <td><?= e($s['rack']) ?></td>
                    <td class="text-end fw-semibold"><?= formatNumber($s['qty']) ?> <?= e($product['unit']) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white fw-semibold">Histori Pergerakan di Gudang Ini</div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead><tr><th>Waktu</th><th>Lokasi</th><th>Tipe</th><th class="text-end">Qty</th><th>Catatan</th><th>Oleh</th></tr></thead>
            <tbody>
            <?php if (empty($movements)): ?>
                <tr><td colspan="6" class="text-center text-muted py-3">Belum ada pergerakan stok</td></tr>
            <?php else: foreach ($movements as $m): ?>
                <tr>
                    <td class="small"><?= formatDateTime($m['created_at']) ?></td>
                    <td><?= e($m['location_code']) ?></td>
                    <td><span class="badge bg-secondary"><?= e($m['movement_type']) ?></span></td>
                    <td class="text-end"><?= formatNumber($m['qty']) ?></td>
                    <td class="small"><?= e($m['notes']) ?></td>
                    <td class="small"><?= e($m['user_name'] ?? '-') ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<a href="by_warehouse.php" class="btn btn-outline-secondary mt-3"><i class="bi bi-arrow-left"></i> Kembali</a>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
