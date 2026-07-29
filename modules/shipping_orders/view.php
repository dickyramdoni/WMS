<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
$pageTitle = 'Detail SO';
$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT so.*, c.name AS customer_name, c.phone, c.address FROM shipping_orders so JOIN customers c ON c.id = so.customer_id WHERE so.id = ?");
$stmt->execute([$id]);
$so = $stmt->fetch();
if (!$so) { setFlash('error', 'SO tidak ditemukan.'); redirect(APP_URL . '/modules/shipping_orders/index.php'); }

$items = $pdo->prepare("SELECT soi.*, p.sku, p.name, p.unit, l.code AS location_code FROM shipping_order_items soi JOIN products p ON p.id = soi.product_id LEFT JOIN locations l ON l.id = soi.location_id WHERE soi.so_id = ?");
$items->execute([$id]);
$items = $items->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Detail SO: <?= e($so['so_number']) ?></h4>
    <?php if (in_array($so['status'], ['draft','picking']) && in_array($_SESSION['role'], ['admin','supervisor','staff'])): ?>
        <a href="process.php?id=<?= $so['id'] ?>" class="btn btn-success"><i class="bi bi-box-arrow-up"></i> Proses Pengiriman</a>
    <?php endif; ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card h-100"><div class="card-body">
            <p class="mb-1"><strong>Customer:</strong> <?= e($so['customer_name']) ?></p>
            <p class="mb-1"><strong>Telepon:</strong> <?= e($so['phone']) ?></p>
            <p class="mb-1"><strong>Alamat:</strong> <?= e($so['address']) ?></p>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card h-100"><div class="card-body">
            <p class="mb-1"><strong>Tanggal Order:</strong> <?= formatDate($so['order_date']) ?></p>
            <p class="mb-1"><strong>Status:</strong> <span class="badge badge-status-<?= e($so['status']) ?>"><?= e($so['status']) ?></span></p>
            <p class="mb-1"><strong>Catatan:</strong> <?= e($so['notes']) ?: '-' ?></p>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white fw-semibold">Item Produk</div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead><tr><th>SKU</th><th>Produk</th><th class="text-end">Qty Diminta</th><th class="text-end">Qty Dikirim</th><th>Lokasi Ambil</th></tr></thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= e($it['sku']) ?></td>
                    <td><?= e($it['name']) ?></td>
                    <td class="text-end"><?= formatNumber($it['qty_requested']) ?> <?= e($it['unit']) ?></td>
                    <td class="text-end"><?= formatNumber($it['qty_shipped']) ?></td>
                    <td><?= e($it['location_code'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<a href="index.php" class="btn btn-outline-secondary mt-3"><i class="bi bi-arrow-left"></i> Kembali</a>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
