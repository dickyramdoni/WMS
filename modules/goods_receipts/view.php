<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
$pageTitle = 'Detail Penerimaan';
$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT gr.*, po.po_number, s.name AS supplier_name, u.full_name AS received_by_name
    FROM goods_receipts gr
    JOIN purchase_orders po ON po.id = gr.po_id
    JOIN suppliers s ON s.id = po.supplier_id
    LEFT JOIN users u ON u.id = gr.received_by
    WHERE gr.id = ?
");
$stmt->execute([$id]);
$gr = $stmt->fetch();
if (!$gr) { setFlash('error', 'Data tidak ditemukan.'); redirect(APP_URL . '/modules/goods_receipts/index.php'); }

$items = $pdo->prepare("
    SELECT gri.*, p.sku, p.name, p.unit, l.code AS location_code
    FROM goods_receipt_items gri
    JOIN products p ON p.id = gri.product_id
    JOIN locations l ON l.id = gri.location_id
    WHERE gri.gr_id = ?
");
$items->execute([$id]);
$items = $items->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<h4 class="mb-3">Penerimaan Barang: <?= e($gr['gr_number']) ?></h4>
<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card h-100"><div class="card-body">
            <p class="mb-1"><strong>No. PO:</strong> <?= e($gr['po_number']) ?></p>
            <p class="mb-1"><strong>Supplier:</strong> <?= e($gr['supplier_name']) ?></p>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card h-100"><div class="card-body">
            <p class="mb-1"><strong>Tanggal Terima:</strong> <?= formatDate($gr['received_date']) ?></p>
            <p class="mb-1"><strong>Diterima Oleh:</strong> <?= e($gr['received_by_name'] ?? '-') ?></p>
            <p class="mb-1"><strong>Catatan:</strong> <?= e($gr['notes']) ?: '-' ?></p>
        </div></div>
    </div>
</div>
<div class="card">
    <div class="card-header bg-white fw-semibold">Item Diterima</div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead><tr><th>SKU</th><th>Produk</th><th>Lokasi Simpan</th><th class="text-end">Qty Diterima</th></tr></thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= e($it['sku']) ?></td>
                    <td><?= e($it['name']) ?></td>
                    <td><?= e($it['location_code']) ?></td>
                    <td class="text-end"><?= formatNumber($it['qty_received']) ?> <?= e($it['unit']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<a href="../purchase_orders/view.php?id=<?= $gr['po_id'] ?>" class="btn btn-outline-secondary mt-3"><i class="bi bi-arrow-left"></i> Kembali ke PO</a>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
