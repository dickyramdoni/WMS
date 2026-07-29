<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
$pageTitle = 'Kartu Stok';
$pdo = getDB();
$productId = (int)($_GET['product_id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();
if (!$product) { setFlash('error', 'Produk tidak ditemukan.'); redirect(APP_URL . '/modules/stock/index.php'); }

$movements = $pdo->prepare("
    SELECT sm.*, l.code AS location_code, u.full_name AS user_name
    FROM stock_movements sm
    JOIN locations l ON l.id = sm.location_id
    LEFT JOIN users u ON u.id = sm.created_by
    WHERE sm.product_id = ?
    ORDER BY sm.id DESC
    LIMIT 100
");
$movements->execute([$productId]);
$movements = $movements->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<h4 class="mb-3">Kartu Stok: <?= e($product['sku']) ?> - <?= e($product['name']) ?></h4>
<div class="card">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead><tr><th>Waktu</th><th>Lokasi</th><th>Tipe</th><th class="text-end">Qty</th><th>Referensi</th><th>Catatan</th><th>Oleh</th></tr></thead>
            <tbody>
            <?php if (empty($movements)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada pergerakan stok untuk produk ini</td></tr>
            <?php else: foreach ($movements as $m): ?>
                <tr>
                    <td class="small"><?= formatDateTime($m['created_at']) ?></td>
                    <td><?= e($m['location_code']) ?></td>
                    <td><span class="badge bg-secondary"><?= e($m['movement_type']) ?></span></td>
                    <td class="text-end"><?= formatNumber($m['qty']) ?></td>
                    <td class="small"><?= e($m['reference_type']) ?> #<?= e($m['reference_id']) ?></td>
                    <td class="small"><?= e($m['notes']) ?></td>
                    <td class="small"><?= e($m['user_name'] ?? '-') ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<a href="index.php" class="btn btn-outline-secondary mt-3"><i class="bi bi-arrow-left"></i> Kembali</a>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
