<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole(['admin','supervisor']);
$pageTitle = 'Transfer / Penyesuaian Stok';
$pdo = getDB();

$products = $pdo->query("SELECT * FROM products WHERE is_active = 1 ORDER BY name")->fetchAll();
$locations = $pdo->query("
    SELECT l.*, w.code AS warehouse_code
    FROM locations l JOIN warehouses w ON w.id = l.warehouse_id
    WHERE l.is_active = 1 ORDER BY w.code, l.code
")->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $mode = $_POST['mode'];
    $productId = (int)$_POST['product_id'];
    $qty = (int)$_POST['qty'];
    $notes = trim($_POST['notes'] ?? '');

    try {
        if ($mode === 'transfer') {
            $fromLoc = (int)$_POST['from_location_id'];
            $toLoc = (int)$_POST['to_location_id'];
            if ($fromLoc === $toLoc) throw new Exception('Lokasi asal dan tujuan tidak boleh sama.');
            $available = getStockQty($pdo, $productId, $fromLoc);
            if ($qty <= 0 || $qty > $available) throw new Exception('Qty transfer melebihi stok yang tersedia di lokasi asal (' . $available . ').');

            $pdo->beginTransaction();
            updateStock($pdo, $productId, $fromLoc, -$qty, 'transfer_out', 'stock_transfer', null, $_SESSION['user_id'], $notes);
            updateStock($pdo, $productId, $toLoc, $qty, 'transfer_in', 'stock_transfer', null, $_SESSION['user_id'], $notes);
            $pdo->commit();
            setFlash('success', 'Transfer stok berhasil diproses.');
        } else {
            // Penyesuaian manual (bisa admin saja idealnya, tapi supervisor diizinkan dengan catatan wajib)
            $locId = (int)$_POST['location_id'];
            $direction = $_POST['direction']; // add / subtract
            if ($notes === '') throw new Exception('Catatan/alasan penyesuaian wajib diisi.');
            $change = $direction === 'add' ? $qty : -$qty;
            if ($direction === 'subtract') {
                $available = getStockQty($pdo, $productId, $locId);
                if ($qty > $available) throw new Exception('Qty pengurangan melebihi stok tersedia (' . $available . ').');
            }
            updateStock($pdo, $productId, $locId, $change, 'adjustment', 'manual_adjustment', null, $_SESSION['user_id'], $notes);
            setFlash('success', 'Penyesuaian stok berhasil disimpan.');
        }
        redirect(APP_URL . '/modules/stock/index.php');
    } catch (Exception $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = $ex->getMessage();
    }
}
require_once __DIR__ . '/../../includes/header.php';
?>
<h4 class="mb-3">Transfer / Penyesuaian Stok</h4>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabTransfer">Transfer Antar Lokasi</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabAdjust">Penyesuaian Manual</button></li>
</ul>

<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<div class="tab-content">
<div class="tab-pane fade show active" id="tabTransfer">
    <div class="card"><div class="card-body">
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="mode" value="transfer">
            <div class="mb-3">
                <label class="form-label">Produk</label>
                <select name="product_id" class="form-select" required>
                    <option value="">- Pilih Produk -</option>
                    <?php foreach ($products as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['sku']) ?> - <?= e($p['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="row">
                <div class="col-md-5 mb-3">
                    <label class="form-label">Dari Lokasi</label>
                    <select name="from_location_id" class="form-select" required>
                        <?php foreach ($locations as $l): ?><option value="<?= $l['id'] ?>">[<?= e($l['warehouse_code']) ?>] <?= e($l['code']) ?> - <?= e($l['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5 mb-3">
                    <label class="form-label">Ke Lokasi</label>
                    <select name="to_location_id" class="form-select" required>
                        <?php foreach ($locations as $l): ?><option value="<?= $l['id'] ?>">[<?= e($l['warehouse_code']) ?>] <?= e($l['code']) ?> - <?= e($l['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Qty</label>
                    <input type="number" name="qty" class="form-control" min="1" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Catatan</label>
                <input type="text" name="notes" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Proses Transfer</button>
        </form>
    </div></div>
</div>

<div class="tab-pane fade" id="tabAdjust">
    <div class="card"><div class="card-body">
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="mode" value="adjust">
            <div class="mb-3">
                <label class="form-label">Produk</label>
                <select name="product_id" class="form-select" required>
                    <option value="">- Pilih Produk -</option>
                    <?php foreach ($products as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['sku']) ?> - <?= e($p['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="row">
                <div class="col-md-5 mb-3">
                    <label class="form-label">Lokasi</label>
                    <select name="location_id" class="form-select" required>
                        <?php foreach ($locations as $l): ?><option value="<?= $l['id'] ?>">[<?= e($l['warehouse_code']) ?>] <?= e($l['code']) ?> - <?= e($l['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Jenis</label>
                    <select name="direction" class="form-select" required>
                        <option value="add">Tambah (+)</option>
                        <option value="subtract">Kurangi (-)</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Qty</label>
                    <input type="number" name="qty" class="form-control" min="1" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Alasan Penyesuaian <span class="text-danger">*</span></label>
                <input type="text" name="notes" class="form-control" placeholder="cth: hasil stock opname, barang rusak, dll" required>
            </div>
            <button type="submit" class="btn btn-warning">Simpan Penyesuaian</button>
        </form>
    </div></div>
</div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
