<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole(['admin','supervisor']);
$pageTitle = 'Tambah Gudang';
$pdo = getDB();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $code = trim($_POST['code']);
    $name = trim($_POST['name']);
    $address = trim($_POST['address']);
    if ($code === '' || $name === '') {
        $error = 'Kode dan nama gudang wajib diisi.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO warehouses (code, name, address) VALUES (?, ?, ?)");
            $stmt->execute([$code, $name, $address]);
            setFlash('success', 'Gudang berhasil ditambahkan.');
            redirect(APP_URL . '/modules/warehouses/index.php');
        } catch (PDOException $ex) {
            $error = isDuplicateKeyError($ex->getMessage()) ? 'Kode gudang sudah digunakan.' : 'Gagal menyimpan data.';
        }
    }
}
require_once __DIR__ . '/../../includes/header.php';
?>
<h4 class="mb-3">Tambah Gudang</h4>
<div class="card" style="max-width:600px;">
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <form method="POST">
            <?= csrfField() ?>
            <div class="mb-3"><label class="form-label">Kode Gudang</label><input type="text" name="code" class="form-control" placeholder="cth: GD-A" value="<?= e($_POST['code'] ?? '') ?>" required></div>
            <div class="mb-3"><label class="form-label">Nama Gudang</label><input type="text" name="name" class="form-control" placeholder="cth: Gudang A" value="<?= e($_POST['name'] ?? '') ?>" required></div>
            <div class="mb-3"><label class="form-label">Alamat</label><textarea name="address" class="form-control" rows="2"><?= e($_POST['address'] ?? '') ?></textarea></div>
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="index.php" class="btn btn-outline-secondary">Batal</a>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
