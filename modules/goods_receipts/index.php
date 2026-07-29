<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
$pageTitle = 'Penerimaan Barang';
require_once __DIR__ . '/../../includes/header.php';

$pdo = getDB();
$receipts = $pdo->query("
    SELECT gr.*, po.po_number, s.name AS supplier_name, u.full_name AS received_by_name
    FROM goods_receipts gr
    JOIN purchase_orders po ON po.id = gr.po_id
    JOIN suppliers s ON s.id = po.supplier_id
    LEFT JOIN users u ON u.id = gr.received_by
    ORDER BY gr.id DESC
")->fetchAll();

$pendingPOs = $pdo->query("
    SELECT po.id, po.po_number, s.name AS supplier_name
    FROM purchase_orders po JOIN suppliers s ON s.id = po.supplier_id
    WHERE po.status IN ('ordered','partial') ORDER BY po.id DESC
")->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Penerimaan Barang (Inbound)</h4>
</div>

<?php if (!empty($pendingPOs) && in_array($_SESSION['role'], ['admin','supervisor','staff'])): ?>
<div class="card mb-3">
    <div class="card-header bg-white fw-semibold">PO Menunggu Penerimaan</div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
        <?php foreach ($pendingPOs as $p): ?>
            <a href="create.php?po_id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-success">
                <i class="bi bi-box-arrow-in-down"></i> <?= e($p['po_number']) ?> - <?= e($p['supplier_name']) ?>
            </a>
        <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>No. GR</th><th>No. PO</th><th>Supplier</th><th>Tanggal Terima</th><th>Diterima Oleh</th><th class="text-center">Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($receipts)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada penerimaan barang</td></tr>
            <?php else: foreach ($receipts as $r): ?>
                <tr>
                    <td><?= e($r['gr_number']) ?></td>
                    <td><?= e($r['po_number']) ?></td>
                    <td><?= e($r['supplier_name']) ?></td>
                    <td><?= formatDate($r['received_date']) ?></td>
                    <td><?= e($r['received_by_name'] ?? '-') ?></td>
                    <td class="text-center"><a href="view.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
