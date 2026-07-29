<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
$pageTitle = 'Detail PO';
$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT po.*, s.name AS supplier_name, s.phone, s.address FROM purchase_orders po JOIN suppliers s ON s.id = po.supplier_id WHERE po.id = ?");
$stmt->execute([$id]);
$po = $stmt->fetch();
if (!$po) { setFlash('error', 'PO tidak ditemukan.'); redirect(APP_URL . '/modules/purchase_orders/index.php'); }

$items = $pdo->prepare("SELECT poi.*, p.sku, p.name, p.unit FROM purchase_order_items poi JOIN products p ON p.id = poi.product_id WHERE poi.po_id = ?");
$items->execute([$id]);
$items = $items->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Detail PO: <?= e($po['po_number']) ?></h4>
    <?php if (in_array($po['status'], ['ordered','partial']) && in_array($_SESSION['role'], ['admin','supervisor','staff'])): ?>
        <a href="../goods_receipts/create.php?po_id=<?= $po['id'] ?>" class="btn btn-success"><i class="bi bi-box-arrow-in-down"></i> Terima Barang</a>
    <?php endif; ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card h-100"><div class="card-body">
            <p class="mb-1"><strong>Supplier:</strong> <?= e($po['supplier_name']) ?></p>
            <p class="mb-1"><strong>Telepon:</strong> <?= e($po['phone']) ?></p>
            <p class="mb-1"><strong>Alamat:</strong> <?= e($po['address']) ?></p>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card h-100"><div class="card-body">
            <p class="mb-1"><strong>Tanggal Order:</strong> <?= formatDate($po['order_date']) ?></p>
            <p class="mb-1"><strong>Status:</strong> <span class="badge badge-status-<?= e($po['status']) ?>"><?= e($po['status']) ?></span></p>
            <p class="mb-1"><strong>Catatan:</strong> <?= e($po['notes']) ?: '-' ?></p>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white fw-semibold">Item Produk</div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead><tr><th>SKU</th><th>Produk</th><th class="text-end">Qty Dipesan</th><th class="text-end">Qty Diterima</th><th class="text-end">Sisa</th><th class="text-end">Harga</th></tr></thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= e($it['sku']) ?></td>
                    <td><?= e($it['name']) ?></td>
                    <td class="text-end"><?= formatNumber($it['qty_ordered']) ?> <?= e($it['unit']) ?></td>
                    <td class="text-end"><?= formatNumber($it['qty_received']) ?></td>
                    <td class="text-end <?= ($it['qty_ordered'] - $it['qty_received']) > 0 ? 'text-warning fw-semibold' : '' ?>"><?= formatNumber($it['qty_ordered'] - $it['qty_received']) ?></td>
                    <td class="text-end"><?= formatMoney($it['unit_price']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<a href="index.php" class="btn btn-outline-secondary mt-3"><i class="bi bi-arrow-left"></i> Kembali</a>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
