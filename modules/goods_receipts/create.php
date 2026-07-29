<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole(['admin','supervisor','staff']);
$pageTitle = 'Terima Barang';
$pdo = getDB();

$poId = (int)($_GET['po_id'] ?? $_POST['po_id'] ?? 0);
$stmt = $pdo->prepare("SELECT po.*, s.name AS supplier_name FROM purchase_orders po JOIN suppliers s ON s.id = po.supplier_id WHERE po.id = ?");
$stmt->execute([$poId]);
$po = $stmt->fetch();
if (!$po || !in_array($po['status'], ['ordered','partial'])) {
    setFlash('error', 'PO tidak valid atau sudah selesai diterima.');
    redirect(APP_URL . '/modules/goods_receipts/index.php');
}

$itemsStmt = $pdo->prepare("SELECT poi.*, p.sku, p.name, p.unit FROM purchase_order_items poi JOIN products p ON p.id = poi.product_id WHERE poi.po_id = ? AND poi.qty_received < poi.qty_ordered");
$itemsStmt->execute([$poId]);
$poItems = $itemsStmt->fetchAll();

$locations = $pdo->query("
    SELECT l.*, w.code AS warehouse_code
    FROM locations l JOIN warehouses w ON w.id = l.warehouse_id
    WHERE l.is_active = 1 ORDER BY w.code, l.code
")->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $receivedDate = $_POST['received_date'];
    $notes = trim($_POST['notes'] ?? '');
    $poItemIds = $_POST['po_item_id'] ?? [];
    $qtys = $_POST['qty_receive'] ?? [];
    $locIds = $_POST['location_id'] ?? [];

    try {
        $pdo->beginTransaction();
        $grNumber = generateDocNumber($pdo, 'goods_receipts', 'gr_number', 'GR');
        $ins = $pdo->prepare("INSERT INTO goods_receipts (gr_number, po_id, received_date, received_by, notes) VALUES (?, ?, ?, ?, ?)");
        $ins->execute([$grNumber, $poId, $receivedDate, $_SESSION['user_id'], $notes]);
        $grId = $pdo->lastInsertId();

        $anyReceived = false;
        $itemInsert = $pdo->prepare("INSERT INTO goods_receipt_items (gr_id, po_item_id, product_id, location_id, qty_received) VALUES (?, ?, ?, ?, ?)");
        $updatePoItem = $pdo->prepare("UPDATE purchase_order_items SET qty_received = qty_received + ? WHERE id = ?");
        $getPoItem = $pdo->prepare("SELECT product_id, qty_ordered, qty_received FROM purchase_order_items WHERE id = ?");

        foreach ($poItemIds as $i => $poItemId) {
            $qty = (int)($qtys[$i] ?? 0);
            $locId = (int)($locIds[$i] ?? 0);
            if ($qty > 0 && $locId) {
                $getPoItem->execute([$poItemId]);
                $poItemRow = $getPoItem->fetch();
                if (!$poItemRow) continue;
                $remaining = $poItemRow['qty_ordered'] - $poItemRow['qty_received'];
                $qty = min($qty, $remaining);
                if ($qty <= 0) continue;

                $itemInsert->execute([$grId, $poItemId, $poItemRow['product_id'], $locId, $qty]);
                $updatePoItem->execute([$qty, $poItemId]);
                updateStock($pdo, $poItemRow['product_id'], $locId, $qty, 'in', 'goods_receipt', $grId, $_SESSION['user_id'], "Penerimaan $grNumber");
                $anyReceived = true;
            }
        }

        if (!$anyReceived) throw new Exception('Tidak ada item yang diterima. Isi qty dan lokasi minimal 1 item.');

        // Update status PO
        $checkStmt = $pdo->prepare("SELECT SUM(qty_ordered) AS total_ordered, SUM(qty_received) AS total_received FROM purchase_order_items WHERE po_id = ?");
        $checkStmt->execute([$poId]);
        $check = $checkStmt->fetch();
        $newStatus = $check['total_received'] >= $check['total_ordered'] ? 'received' : 'partial';
        $pdo->prepare("UPDATE purchase_orders SET status = ? WHERE id = ?")->execute([$newStatus, $poId]);

        $pdo->commit();
        setFlash('success', "Penerimaan barang $grNumber berhasil disimpan dan stok telah diperbarui.");
        redirect(APP_URL . '/modules/goods_receipts/view.php?id=' . $grId);
    } catch (Exception $ex) {
        $pdo->rollBack();
        $error = 'Gagal menyimpan penerimaan: ' . $ex->getMessage();
    }
}
require_once __DIR__ . '/../../includes/header.php';
?>
<h4 class="mb-3">Terima Barang - PO <?= e($po['po_number']) ?> (<?= e($po['supplier_name']) ?>)</h4>
<div class="card">
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <?php if (empty($poItems)): ?>
            <p class="text-muted">Semua item pada PO ini sudah diterima sepenuhnya.</p>
            <a href="../purchase_orders/view.php?id=<?= $poId ?>" class="btn btn-outline-secondary">Kembali ke PO</a>
        <?php else: ?>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="po_id" value="<?= $poId ?>">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Tanggal Terima</label>
                    <input type="date" name="received_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-8 mb-3">
                    <label class="form-label">Catatan</label>
                    <input type="text" name="notes" class="form-control">
                </div>
            </div>
            <table class="table table-sm">
                <thead><tr><th>SKU</th><th>Produk</th><th class="text-end">Sisa Order</th><th style="width:150px;">Qty Diterima</th><th style="width:220px;">Lokasi Simpan</th></tr></thead>
                <tbody>
                <?php foreach ($poItems as $it): $remaining = $it['qty_ordered'] - $it['qty_received']; ?>
                    <tr>
                        <td><?= e($it['sku']) ?><input type="hidden" name="po_item_id[]" value="<?= $it['id'] ?>"></td>
                        <td><?= e($it['name']) ?></td>
                        <td class="text-end"><?= formatNumber($remaining) ?> <?= e($it['unit']) ?></td>
                        <td><input type="number" name="qty_receive[]" class="form-control form-control-sm" min="0" max="<?= $remaining ?>" value="<?= $remaining ?>"></td>
                        <td>
                            <select name="location_id[]" class="form-select form-select-sm" required>
                                <?php foreach ($locations as $l): ?>
                                    <option value="<?= $l['id'] ?>">[<?= e($l['warehouse_code']) ?>] <?= e($l['code']) ?> - <?= e($l['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Simpan Penerimaan</button>
            <a href="../purchase_orders/view.php?id=<?= $poId ?>" class="btn btn-outline-secondary">Batal</a>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
