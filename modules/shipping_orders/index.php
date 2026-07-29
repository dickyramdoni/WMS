<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
$pageTitle = 'Pengiriman Barang';
require_once __DIR__ . '/../../includes/header.php';

$pdo = getDB();
$sos = $pdo->query("
    SELECT so.*, c.name AS customer_name,
        (SELECT COALESCE(SUM(qty_requested),0) FROM shipping_order_items WHERE so_id = so.id) AS total_requested,
        (SELECT COALESCE(SUM(qty_shipped),0) FROM shipping_order_items WHERE so_id = so.id) AS total_shipped
    FROM shipping_orders so
    JOIN customers c ON c.id = so.customer_id
    ORDER BY so.id DESC
")->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Pengiriman Barang (Outbound)</h4>
    <?php if (in_array($_SESSION['role'], ['admin','supervisor'])): ?>
        <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Buat Sales/Shipping Order</a>
    <?php endif; ?>
</div>
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>No. SO</th><th>Customer</th><th>Tanggal</th><th class="text-end">Diminta</th><th class="text-end">Dikirim</th><th class="text-center">Status</th><th class="text-center">Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($sos)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada Shipping Order</td></tr>
            <?php else: foreach ($sos as $so): ?>
                <tr>
                    <td><?= e($so['so_number']) ?></td>
                    <td><?= e($so['customer_name']) ?></td>
                    <td><?= formatDate($so['order_date']) ?></td>
                    <td class="text-end"><?= formatNumber($so['total_requested']) ?></td>
                    <td class="text-end"><?= formatNumber($so['total_shipped']) ?></td>
                    <td class="text-center"><span class="badge badge-status-<?= e($so['status']) ?>"><?= e($so['status']) ?></span></td>
                    <td class="text-center">
                        <a href="view.php?id=<?= $so['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
