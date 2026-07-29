<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole(['admin','supervisor','staff']);
$pageTitle = 'Proses Pengiriman';
$pdo = getDB();
$soId = (int)($_GET['id'] ?? $_POST['so_id'] ?? 0);

$stmt = $pdo->prepare("SELECT so.*, c.name AS customer_name FROM shipping_orders so JOIN customers c ON c.id = so.customer_id WHERE so.id = ?");
$stmt->execute([$soId]);
$so = $stmt->fetch();
if (!$so || !in_array($so['status'], ['draft','picking'])) {
    setFlash('error', 'SO tidak valid atau sudah selesai dikirim.');
    redirect(APP_URL . '/modules/shipping_orders/index.php');
}

$items = $pdo->prepare("SELECT soi.*, p.sku, p.name, p.unit FROM shipping_order_items soi JOIN products p ON p.id = soi.product_id WHERE soi.so_id = ? AND soi.qty_shipped < soi.qty_requested");
$items->execute([$soId]);
$items = $items->fetchAll();

// Ambil stok tiap produk per lokasi untuk ditampilkan sebagai pilihan
$stockByProduct = [];
foreach ($items as $it) {
    $s = $pdo->prepare("SELECT s.location_id, l.code, w.code AS warehouse_code, s.qty FROM stock s JOIN locations l ON l.id = s.location_id JOIN warehouses w ON w.id = l.warehouse_id WHERE s.product_id = ? AND s.qty > 0 ORDER BY s.qty DESC");
    $s->execute([$it['product_id']]);
    $stockByProduct[$it['id']] = $s->fetchAll();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $soItemIds = $_POST['so_item_id'] ?? [];
    $qtys = $_POST['qty_ship'] ?? [];
    $locIds = $_POST['location_id'] ?? [];

    try {
        $pdo->beginTransaction();
        $anyShipped = false;
        $getItem = $pdo->prepare("SELECT product_id, qty_requested, qty_shipped FROM shipping_order_items WHERE id = ?");
        $updItem = $pdo->prepare("UPDATE shipping_order_items SET qty_shipped = qty_shipped + ?, location_id = ? WHERE id = ?");

        foreach ($soItemIds as $i => $soItemId) {
            $qty = (int)($qtys[$i] ?? 0);
            $locId = (int)($locIds[$i] ?? 0);
            if ($qty > 0 && $locId) {
                $getItem->execute([$soItemId]);
                $row = $getItem->fetch();
                if (!$row) continue;
                $remaining = $row['qty_requested'] - $row['qty_shipped'];
                $qty = min($qty, $remaining);
                $available = getStockQty($pdo, $row['product_id'], $locId);
                if ($qty > $available) {
                    throw new Exception('Stok tidak mencukupi di lokasi yang dipilih untuk salah satu produk.');
                }
                if ($qty <= 0) continue;

                $updItem->execute([$qty, $locId, $soItemId]);
                updateStock($pdo, $row['product_id'], $locId, -$qty, 'out', 'shipping_order', $soId, $_SESSION['user_id'], "Pengiriman SO");
                $anyShipped = true;
            }
        }

        if (!$anyShipped) throw new Exception('Tidak ada item yang diproses. Isi qty dan lokasi minimal 1 item.');

        $checkStmt = $pdo->prepare("SELECT SUM(qty_requested) AS total_req, SUM(qty_shipped) AS total_shipped FROM shipping_order_items WHERE so_id = ?");
        $checkStmt->execute([$soId]);
        $check = $checkStmt->fetch();
        $newStatus = $check['total_shipped'] >= $check['total_req'] ? 'shipped' : 'picking';
        $pdo->prepare("UPDATE shipping_orders SET status = ? WHERE id = ?")->execute([$newStatus, $soId]);

        $pdo->commit();
        setFlash('success', 'Pengiriman berhasil diproses dan stok telah diperbarui.');
        redirect(APP_URL . '/modules/shipping_orders/view.php?id=' . $soId);
    } catch (Exception $ex) {
        $pdo->rollBack();
        $error = 'Gagal memproses pengiriman: ' . $ex->getMessage();
    }
}
require_once __DIR__ . '/../../includes/header.php';
?>
<h4 class="mb-3">Proses Pengiriman - SO <?= e($so['so_number']) ?> (<?= e($so['customer_name']) ?>)</h4>
<div class="card">
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <?php if (empty($items)): ?>
            <p class="text-muted">Semua item pada SO ini sudah selesai dikirim.</p>
            <a href="view.php?id=<?= $soId ?>" class="btn btn-outline-secondary">Kembali ke SO</a>
        <?php else: ?>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="so_id" value="<?= $soId ?>">
            <table class="table table-sm">
                <thead><tr><th>SKU</th><th>Produk</th><th class="text-end">Sisa Diminta</th><th style="width:150px;">Qty Kirim</th><th style="width:260px;">Lokasi Ambil (stok tersedia)</th></tr></thead>
                <tbody>
                <?php foreach ($items as $it): $remaining = $it['qty_requested'] - $it['qty_shipped']; $stocks = $stockByProduct[$it['id']]; ?>
                    <tr>
                        <td><?= e($it['sku']) ?><input type="hidden" name="so_item_id[]" value="<?= $it['id'] ?>"></td>
                        <td><?= e($it['name']) ?></td>
                        <td class="text-end"><?= formatNumber($remaining) ?> <?= e($it['unit']) ?></td>
                        <td><input type="number" name="qty_ship[]" class="form-control form-control-sm" min="0" max="<?= $remaining ?>" value="0"></td>
                        <td>
                            <?php if (empty($stocks)): ?>
                                <span class="text-danger small">Stok kosong di semua lokasi</span>
                                <select name="location_id[]" class="form-select form-select-sm" disabled></select>
                            <?php else: ?>
                                <select name="location_id[]" class="form-select form-select-sm">
                                    <?php foreach ($stocks as $st): ?>
                                        <option value="<?= $st['location_id'] ?>">[<?= e($st['warehouse_code']) ?>] <?= e($st['code']) ?> (tersedia: <?= formatNumber($st['qty']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Simpan Pengiriman</button>
            <a href="view.php?id=<?= $soId ?>" class="btn btn-outline-secondary">Batal</a>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
