<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
$pageTitle = 'Laporan';
$pdo = getDB();

$type = $_GET['type'] ?? 'stock';
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

require_once __DIR__ . '/../../includes/header.php';
?>
<h4 class="mb-3">Laporan</h4>

<ul class="nav nav-pills mb-3">
    <li class="nav-item"><a class="nav-link <?= $type==='stock'?'active':'' ?>" href="?type=stock">Laporan Stok</a></li>
    <li class="nav-item"><a class="nav-link <?= $type==='inbound'?'active':'' ?>" href="?type=inbound&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>">Laporan Inbound</a></li>
    <li class="nav-item"><a class="nav-link <?= $type==='outbound'?'active':'' ?>" href="?type=outbound&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>">Laporan Outbound</a></li>
</ul>

<?php if ($type === 'stock'):
    $rows = $pdo->query("
        SELECT p.sku, p.name, p.unit, p.min_stock, COALESCE(SUM(s.qty),0) AS total_stock
        FROM products p LEFT JOIN stock s ON s.product_id = p.id
        WHERE p.is_active = 1 GROUP BY p.id ORDER BY p.name
    ")->fetchAll();
?>
<div class="card"><div class="card-body p-0">
    <table class="table mb-0">
        <thead><tr><th>SKU</th><th>Produk</th><th class="text-end">Total Stok</th><th class="text-end">Min. Stok</th><th class="text-center">Status</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): $low = $r['total_stock'] <= $r['min_stock']; ?>
            <tr>
                <td><?= e($r['sku']) ?></td>
                <td><?= e($r['name']) ?></td>
                <td class="text-end"><?= formatNumber($r['total_stock']) ?> <?= e($r['unit']) ?></td>
                <td class="text-end"><?= formatNumber($r['min_stock']) ?></td>
                <td class="text-center"><span class="badge bg-<?= $low ? 'danger' : 'success' ?>"><?= $low ? 'Menipis' : 'Aman' ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div></div>

<?php elseif ($type === 'inbound'): ?>
<form class="row g-2 mb-3" method="GET">
    <input type="hidden" name="type" value="inbound">
    <div class="col-auto"><input type="date" name="start_date" class="form-control" value="<?= e($startDate) ?>"></div>
    <div class="col-auto"><input type="date" name="end_date" class="form-control" value="<?= e($endDate) ?>"></div>
    <div class="col-auto"><button class="btn btn-outline-secondary">Filter</button></div>
</form>
<?php
    $stmt = $pdo->prepare("
        SELECT gr.gr_number, gr.received_date, po.po_number, s.name AS supplier_name,
               SUM(gri.qty_received) AS total_qty
        FROM goods_receipts gr
        JOIN purchase_orders po ON po.id = gr.po_id
        JOIN suppliers s ON s.id = po.supplier_id
        JOIN goods_receipt_items gri ON gri.gr_id = gr.id
        WHERE gr.received_date BETWEEN ? AND ?
        GROUP BY gr.id ORDER BY gr.received_date DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    $rows = $stmt->fetchAll();
?>
<div class="card"><div class="card-body p-0">
    <table class="table mb-0">
        <thead><tr><th>No. GR</th><th>Tanggal</th><th>No. PO</th><th>Supplier</th><th class="text-end">Total Qty Diterima</th></tr></thead>
        <tbody>
        <?php if (empty($rows)): ?><tr><td colspan="5" class="text-center text-muted py-4">Tidak ada data pada periode ini</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= e($r['gr_number']) ?></td>
                <td><?= formatDate($r['received_date']) ?></td>
                <td><?= e($r['po_number']) ?></td>
                <td><?= e($r['supplier_name']) ?></td>
                <td class="text-end"><?= formatNumber($r['total_qty']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div></div>

<?php elseif ($type === 'outbound'): ?>
<form class="row g-2 mb-3" method="GET">
    <input type="hidden" name="type" value="outbound">
    <div class="col-auto"><input type="date" name="start_date" class="form-control" value="<?= e($startDate) ?>"></div>
    <div class="col-auto"><input type="date" name="end_date" class="form-control" value="<?= e($endDate) ?>"></div>
    <div class="col-auto"><button class="btn btn-outline-secondary">Filter</button></div>
</form>
<?php
    $stmt = $pdo->prepare("
        SELECT so.so_number, so.order_date, c.name AS customer_name, so.status,
               SUM(soi.qty_shipped) AS total_qty
        FROM shipping_orders so
        JOIN customers c ON c.id = so.customer_id
        JOIN shipping_order_items soi ON soi.so_id = so.id
        WHERE so.order_date BETWEEN ? AND ?
        GROUP BY so.id ORDER BY so.order_date DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    $rows = $stmt->fetchAll();
?>
<div class="card"><div class="card-body p-0">
    <table class="table mb-0">
        <thead><tr><th>No. SO</th><th>Tanggal</th><th>Customer</th><th class="text-center">Status</th><th class="text-end">Total Qty Dikirim</th></tr></thead>
        <tbody>
        <?php if (empty($rows)): ?><tr><td colspan="5" class="text-center text-muted py-4">Tidak ada data pada periode ini</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= e($r['so_number']) ?></td>
                <td><?= formatDate($r['order_date']) ?></td>
                <td><?= e($r['customer_name']) ?></td>
                <td class="text-center"><span class="badge badge-status-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
                <td class="text-end"><?= formatNumber($r['total_qty']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div></div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
