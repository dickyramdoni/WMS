<?php
require_once __DIR__ . '/config/database.php';
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();

$totalProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();
$totalStockQty = $pdo->query("SELECT COALESCE(SUM(qty),0) FROM stock")->fetchColumn();
$openPO = $pdo->query("SELECT COUNT(*) FROM purchase_orders WHERE status IN ('ordered','partial')")->fetchColumn();
$openSO = $pdo->query("SELECT COUNT(*) FROM shipping_orders WHERE status IN ('draft','picking')")->fetchColumn();

$lowStock = $pdo->query("
    SELECT p.sku, p.name, p.unit, p.min_stock, COALESCE(SUM(s.qty),0) AS total_stock
    FROM products p
    LEFT JOIN stock s ON s.product_id = p.id
    WHERE p.is_active = 1
    GROUP BY p.id
    HAVING total_stock <= p.min_stock
    ORDER BY total_stock ASC
    LIMIT 8
")->fetchAll();

$recentMovements = $pdo->query("
    SELECT sm.*, p.name AS product_name, l.code AS location_code, u.full_name AS user_name
    FROM stock_movements sm
    JOIN products p ON p.id = sm.product_id
    JOIN locations l ON l.id = sm.location_id
    LEFT JOIN users u ON u.id = sm.created_by
    ORDER BY sm.id DESC
    LIMIT 8
")->fetchAll();
?>

<h4 class="mb-4">Dashboard</h4>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card card-stat bg-stat-1">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="small">Total Produk</div>
                    <div class="fs-4 fw-bold"><?= formatNumber($totalProducts) ?></div>
                </div>
                <i class="bi bi-box icon"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-stat bg-stat-2">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="small">Total Stok (unit)</div>
                    <div class="fs-4 fw-bold"><?= formatNumber($totalStockQty) ?></div>
                </div>
                <i class="bi bi-clipboard-data icon"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-stat bg-stat-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="small">PO Berjalan</div>
                    <div class="fs-4 fw-bold"><?= formatNumber($openPO) ?></div>
                </div>
                <i class="bi bi-file-earmark-text icon"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-stat bg-stat-4">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="small">SO Berjalan</div>
                    <div class="fs-4 fw-bold"><?= formatNumber($openSO) ?></div>
                </div>
                <i class="bi bi-box-arrow-up icon"></i>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-exclamation-triangle text-warning me-1"></i> Stok Menipis</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>SKU</th><th>Produk</th><th class="text-end">Stok</th><th class="text-end">Min</th></tr></thead>
                    <tbody>
                    <?php if (empty($lowStock)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">Tidak ada stok menipis</td></tr>
                    <?php else: foreach ($lowStock as $row): ?>
                        <tr>
                            <td><?= e($row['sku']) ?></td>
                            <td><?= e($row['name']) ?></td>
                            <td class="text-end text-danger fw-semibold"><?= formatNumber($row['total_stock']) ?> <?= e($row['unit']) ?></td>
                            <td class="text-end"><?= formatNumber($row['min_stock']) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-clock-history me-1"></i> Pergerakan Stok Terbaru</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Waktu</th><th>Produk</th><th>Lokasi</th><th>Tipe</th><th class="text-end">Qty</th></tr></thead>
                    <tbody>
                    <?php if (empty($recentMovements)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Belum ada pergerakan stok</td></tr>
                    <?php else: foreach ($recentMovements as $row): ?>
                        <tr>
                            <td class="small"><?= formatDateTime($row['created_at']) ?></td>
                            <td><?= e($row['product_name']) ?></td>
                            <td><?= e($row['location_code']) ?></td>
                            <td><span class="badge bg-secondary"><?= e($row['movement_type']) ?></span></td>
                            <td class="text-end"><?= formatNumber($row['qty']) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
