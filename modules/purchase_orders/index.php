<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
$pageTitle = 'Purchase Order';
require_once __DIR__ . '/../../includes/header.php';

$pdo = getDB();
$pos = $pdo->query("
    SELECT po.*, s.name AS supplier_name,
        (SELECT COALESCE(SUM(qty_ordered),0) FROM purchase_order_items WHERE po_id = po.id) AS total_ordered,
        (SELECT COALESCE(SUM(qty_received),0) FROM purchase_order_items WHERE po_id = po.id) AS total_received
    FROM purchase_orders po
    JOIN suppliers s ON s.id = po.supplier_id
    ORDER BY po.id DESC
")->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Purchase Order</h4>
    <?php if (in_array($_SESSION['role'], ['admin','supervisor'])): ?>
        <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Buat PO</a>
    <?php endif; ?>
</div>
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>No. PO</th><th>Supplier</th><th>Tanggal</th><th class="text-end">Dipesan</th><th class="text-end">Diterima</th><th class="text-center">Status</th><th class="text-center">Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($pos)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada Purchase Order</td></tr>
            <?php else: foreach ($pos as $po): ?>
                <tr>
                    <td><?= e($po['po_number']) ?></td>
                    <td><?= e($po['supplier_name']) ?></td>
                    <td><?= formatDate($po['order_date']) ?></td>
                    <td class="text-end"><?= formatNumber($po['total_ordered']) ?></td>
                    <td class="text-end"><?= formatNumber($po['total_received']) ?></td>
                    <td class="text-center"><span class="badge badge-status-<?= e($po['status']) ?>"><?= e($po['status']) ?></span></td>
                    <td class="text-center">
                        <a href="view.php?id=<?= $po['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
