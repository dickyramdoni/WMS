<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole(['admin','supervisor']);
$pageTitle = 'Buat Purchase Order';
$pdo = getDB();

$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name")->fetchAll();
$products = $pdo->query("SELECT * FROM products WHERE is_active = 1 ORDER BY name")->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $supplierId = (int)$_POST['supplier_id'];
    $orderDate = $_POST['order_date'];
    $notes = trim($_POST['notes'] ?? '');
    $productIds = $_POST['product_id'] ?? [];
    $qtys = $_POST['qty_ordered'] ?? [];
    $prices = $_POST['unit_price'] ?? [];

    if (!$supplierId || empty($productIds)) {
        $error = 'Supplier dan minimal 1 item produk wajib diisi.';
    } else {
        try {
            $pdo->beginTransaction();
            $poNumber = generateDocNumber($pdo, 'purchase_orders', 'po_number', 'PO');
            $stmt = $pdo->prepare("INSERT INTO purchase_orders (po_number, supplier_id, order_date, status, notes, created_by) VALUES (?, ?, ?, 'ordered', ?, ?)");
            $stmt->execute([$poNumber, $supplierId, $orderDate, $notes, $_SESSION['user_id']]);
            $poId = $pdo->lastInsertId();

            $itemStmt = $pdo->prepare("INSERT INTO purchase_order_items (po_id, product_id, qty_ordered, unit_price) VALUES (?, ?, ?, ?)");
            $count = 0;
            foreach ($productIds as $i => $pid) {
                $qty = (int)($qtys[$i] ?? 0);
                $price = (float)($prices[$i] ?? 0);
                if ($pid && $qty > 0) {
                    $itemStmt->execute([$poId, $pid, $qty, $price]);
                    $count++;
                }
            }
            if ($count === 0) throw new Exception('Minimal 1 item dengan qty valid harus diisi.');

            $pdo->commit();
            setFlash('success', "PO $poNumber berhasil dibuat.");
            redirect(APP_URL . '/modules/purchase_orders/view.php?id=' . $poId);
        } catch (Exception $ex) {
            $pdo->rollBack();
            $error = 'Gagal menyimpan PO: ' . $ex->getMessage();
        }
    }
}
require_once __DIR__ . '/../../includes/header.php';
?>
<h4 class="mb-3">Buat Purchase Order</h4>
<div class="card">
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <form method="POST" id="poForm">
            <?= csrfField() ?>
            <div class="row">
                <div class="col-md-5 mb-3">
                    <label class="form-label">Supplier</label>
                    <select name="supplier_id" class="form-select" required>
                        <option value="">- Pilih Supplier -</option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Tanggal Order</label>
                    <input type="date" name="order_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Catatan</label>
                    <input type="text" name="notes" class="form-control">
                </div>
            </div>

            <hr>
            <h6>Item Produk</h6>
            <table class="table table-sm" id="itemTable">
                <thead><tr><th style="width:40%">Produk</th><th>Qty</th><th>Harga Satuan</th><th></th></tr></thead>
                <tbody>
                    <tr>
                        <td>
                            <select name="product_id[]" class="form-select form-select-sm" required>
                                <option value="">- Pilih Produk -</option>
                                <?php foreach ($products as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= e($p['sku']) ?> - <?= e($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="number" name="qty_ordered[]" class="form-control form-control-sm" min="1" required></td>
                        <td><input type="number" name="unit_price[]" class="form-control form-control-sm" min="0" step="0.01" value="0"></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger removeRow"><i class="bi bi-x"></i></button></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="btn btn-sm btn-outline-primary mb-3" id="addRow"><i class="bi bi-plus"></i> Tambah Item</button>
            <br>
            <button type="submit" class="btn btn-primary">Simpan PO</button>
            <a href="index.php" class="btn btn-outline-secondary">Batal</a>
        </form>
    </div>
</div>

<script>
document.getElementById('addRow').addEventListener('click', function () {
    const tbody = document.querySelector('#itemTable tbody');
    const newRow = tbody.rows[0].cloneNode(true);
    newRow.querySelectorAll('input').forEach(i => i.value = i.type === 'number' && i.name.includes('unit_price') ? '0' : '');
    newRow.querySelector('select').selectedIndex = 0;
    tbody.appendChild(newRow);
});
document.querySelector('#itemTable').addEventListener('click', function (e) {
    if (e.target.closest('.removeRow')) {
        const tbody = document.querySelector('#itemTable tbody');
        if (tbody.rows.length > 1) e.target.closest('tr').remove();
    }
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
