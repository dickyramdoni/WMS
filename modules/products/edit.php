<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole(['admin','supervisor']);
$pageTitle = 'Edit Produk';

$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) { setFlash('error', 'Produk tidak ditemukan.'); redirect(APP_URL . '/modules/products/index.php'); }

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $sku = trim($_POST['sku']);
    $name = trim($_POST['name']);
    $categoryId = $_POST['category_id'] ?: null;
    $unit = trim($_POST['unit']);
    $minStock = (int)$_POST['min_stock'];
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($sku === '' || $name === '') {
        $error = 'SKU dan nama produk wajib diisi.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE products SET sku=?, name=?, category_id=?, unit=?, min_stock=?, is_active=? WHERE id=?");
            $stmt->execute([$sku, $name, $categoryId, $unit, $minStock, $isActive, $id]);
            setFlash('success', 'Produk berhasil diperbarui.');
            redirect(APP_URL . '/modules/products/index.php');
        } catch (PDOException $ex) {
            $error = isDuplicateKeyError($ex->getMessage()) ? 'SKU sudah digunakan produk lain.' : 'Gagal menyimpan data.';
        }
    }
} else {
    $_POST = $product;
}
require_once __DIR__ . '/../../includes/header.php';
?>
<h4 class="mb-3">Edit Produk</h4>
<div class="card" style="max-width:600px;">
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <form method="POST">
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">SKU</label>
                <input type="text" name="sku" class="form-control" value="<?= e($_POST['sku']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Nama Produk</label>
                <input type="text" name="name" class="form-control" value="<?= e($_POST['name']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Kategori</label>
                <select name="category_id" class="form-select">
                    <option value="">- Pilih Kategori -</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $c['id'] == $_POST['category_id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="form-label">Satuan</label>
                    <input type="text" name="unit" class="form-control" value="<?= e($_POST['unit']) ?>" required>
                </div>
                <div class="col-6 mb-3">
                    <label class="form-label">Stok Minimum</label>
                    <input type="number" name="min_stock" class="form-control" value="<?= (int)$_POST['min_stock'] ?>" min="0" required>
                </div>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" name="is_active" class="form-check-input" id="isActive" <?= $_POST['is_active'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="isActive">Produk Aktif</label>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="index.php" class="btn btn-outline-secondary">Batal</a>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
