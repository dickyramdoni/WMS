<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole(['admin','supervisor']);
$pageTitle = 'Edit Gudang';
$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM warehouses WHERE id = ?");
$stmt->execute([$id]);
$wh = $stmt->fetch();
if (!$wh) { setFlash('error', 'Gudang tidak ditemukan.'); redirect(APP_URL . '/modules/warehouses/index.php'); }
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $code = trim($_POST['code']);
    $name = trim($_POST['name']);
    $address = trim($_POST['address']);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    if ($code === '' || $name === '') {
        $error = 'Kode dan nama gudang wajib diisi.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE warehouses SET code=?, name=?, address=?, is_active=? WHERE id=?");
            $stmt->execute([$code, $name, $address, $isActive, $id]);
            setFlash('success', 'Gudang berhasil diperbarui.');
            redirect(APP_URL . '/modules/warehouses/index.php');
        } catch (PDOException $ex) {
            $error = isDuplicateKeyError($ex->getMessage()) ? 'Kode gudang sudah digunakan.' : 'Gagal menyimpan data.';
        }
    }
} else {
    $_POST = $wh;
}
require_once __DIR__ . '/../../includes/header.php';
?>
<h4 class="mb-3">Edit Gudang</h4>
<div class="card" style="max-width:600px;">
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <form method="POST">
            <?= csrfField() ?>
            <div class="mb-3"><label class="form-label">Kode Gudang</label><input type="text" name="code" class="form-control" value="<?= e($_POST['code']) ?>" required></div>
            <div class="mb-3"><label class="form-label">Nama Gudang</label><input type="text" name="name" class="form-control" value="<?= e($_POST['name']) ?>" required></div>
            <div class="mb-3"><label class="form-label">Alamat</label><textarea name="address" class="form-control" rows="2"><?= e($_POST['address']) ?></textarea></div>
            <div class="mb-3 form-check">
                <input type="checkbox" name="is_active" class="form-check-input" id="isActive" <?= $_POST['is_active'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="isActive">Gudang Aktif</label>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="index.php" class="btn btn-outline-secondary">Batal</a>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
