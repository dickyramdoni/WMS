<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole(['admin','supervisor']);
$pageTitle = 'Buat Shipping Order';
$pdo = getDB();

$customers = $pdo->query("SELECT * FROM customers ORDER BY name")->fetchAll();
$products = $pdo->query("SELECT * FROM products WHERE is_active = 1 ORDER BY name")->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $customerId = (int)$_POST['customer_id'];
    $orderDate = $_POST['order_date'];
    $notes = trim($_POST['notes'] ?? '');
    $productIds = $_POST['product_id'] ?? [];
    $qtys = $_POST['qty_requested'] ?? [];

    if (!$customerId || empty($productIds)) {
        $error = 'Customer dan minimal 1 item produk wajib diisi.';
    } else {
        try {
            $pdo->beginTransaction();
            $soNumber = generateDocNumber($pdo, 'shipping_orders', 'so_number', 'SO');
            $stmt = $pdo->prepare("INSERT INTO shipping_orders (so_number, customer_id, order_date, status, notes, created_by) VALUES (?, ?, ?, 'draft', ?, ?)");
            $stmt->execute([$soNumber, $customerId, $orderDate, $notes, $_SESSION['user_id']]);
            $soId = $pdo->lastInsertId();

            // Item tanpa lokasi dulu (lokasi ditentukan saat proses pengiriman); location_id default ke 0-holder via first active location, actual dipilih saat proses
            $firstLoc = $pdo->query("SELECT id FROM locations WHERE is_active = 1 ORDER BY id LIMIT 1")->fetchColumn();
            $itemStmt = $pdo->prepare("INSERT INTO shipping_order_items (so_id, product_id, location_id, qty_requested) VALUES (?, ?, ?, ?)");
            $count = 0;
            foreach ($productIds as $i => $pid) {
                $qty = (int)($qtys[$i] ?? 0);
                if ($pid && $qty > 0) {
                    $itemStmt->execute([$soId, $pid, $firstLoc, $qty]);
                    $count++;
                }
            }
            if ($count === 0) throw new Exception('Minimal 1 item dengan qty valid harus diisi.');

            $pdo->commit();
            setFlash('success', "SO $soNumber berhasil dibuat.");
            redirect(APP_URL . '/modules/shipping_orders/view.php?id=' . $soId);
        } catch (Exception $ex) {
            $pdo->rollBack();
            $error = 'Gagal menyimpan SO: ' . $ex->getMessage();
        }
    }
}
require_once __DIR__ . '/../../includes/header.php';
?>
<h4 class="mb-3">Buat Shipping Order</h4>
<div class="card">
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <form method="POST">
            <?= csrfField() ?>
            <div class="row">
                <div class="col-md-5 mb-3">
                    <label class="form-label">Customer</label>
                    <select name="customer_id" class="form-select" required>
                        <option value="">- Pilih Customer -</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
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
            <h6>Item Produk yang Diminta</h6>
            <table class="table table-sm" id="itemTable">
                <thead><tr><th style="width:50%">Produk</th><th>Qty Diminta</th><th></th></tr></thead>
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
                        <td><input type="number" name="qty_requested[]" class="form-control form-control-sm" min="1" required></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger removeRow"><i class="bi bi-x"></i></button></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="btn btn-sm btn-outline-primary mb-3" id="addRow"><i class="bi bi-plus"></i> Tambah Item</button>
            <br>
            <button type="submit" class="btn btn-primary">Simpan SO</button>
            <a href="index.php" class="btn btn-outline-secondary">Batal</a>
        </form>
    </div>
</div>
<script>
document.getElementById('addRow').addEventListener('click', function () {
    const tbody = document.querySelector('#itemTable tbody');
    const newRow = tbody.rows[0].cloneNode(true);
    newRow.querySelectorAll('input').forEach(i => i.value = '');
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
