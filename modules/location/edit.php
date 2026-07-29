<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole(['admin','supervisor']);
$pageTitle = 'Edit Lokasi';
$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM locations WHERE id = ?");
$stmt->execute([$id]);
$loc = $stmt->fetch();
if (!$loc) { setFlash('error', 'Lokasi tidak ditemukan.'); redirect(APP_URL . '/modules/locations/index.php'); }
$warehouses = $pdo->query("SELECT * FROM warehouses ORDER BY code")->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $warehouseId = (int)$_POST['warehouse_id'];
    $code = trim($_POST['code']);
    $name = trim($_POST['name']);
    $zone = trim($_POST['zone']);
    $rack = trim($_POST['rack']);
    $desc = trim($_POST['description']);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    if (!$warehouseId || $code === '' || $name === '') {
        $error = 'Gudang, kode, dan nama lokasi wajib diisi.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE locations SET warehouse_id=?, code=?, name=?, zone=?, rack=?, description=?, is_active=? WHERE id=?");
            $stmt->execute([$warehouseId, $code, $name, $zone, $rack, $desc, $isActive, $id]);
            setFlash('success', 'Lokasi berhasil diperbarui.');
            redirect(APP_URL . '/modules/locations/index.php');
        } catch (PDOException $ex) {
            $error = isDuplicateKeyError($ex->getMessage()) ? 'Kode lokasi sudah digunakan.' : 'Gagal menyimpan data.';
        }
    }
} else {
    $_POST = $loc;
}
require_once __DIR__ . '/../../includes/header.php';
?>
<h4 class="mb-3">Edit Lokasi / Rak Gudang</h4>
<div class="card" style="max-width:600px;">
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <form method="POST">
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">Gudang</label>
                <select name="warehouse_id" class="form-select" required>
                    <?php foreach ($warehouses as $w): ?>
                        <option value="<?= $w['id'] ?>" <?= $w['id'] == $_POST['warehouse_id'] ? 'selected' : '' ?>><?= e($w['code']) ?> - <?= e($w['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3"><label class="form-label">Kode Lokasi</label><input type="text" name="code" class="form-control" value="<?= e($_POST['code']) ?>" required></div>
            <div class="mb-3"><label class="form-label">Nama Lokasi</label><input type="text" name="name" class="form-control" value="<?= e($_POST['name']) ?>" required></div>
            <div class="row">
                <div class="col-6 mb-3"><label class="form-label">Zone</label><input type="text" name="zone" class="form-control" value="<?= e($_POST['zone']) ?>"></div>
                <div class="col-6 mb-3"><label class="form-label">Rak</label><input type="text" name="rack" class="form-control" value="<?= e($_POST['rack']) ?>"></div>
            </div>
            <div class="mb-3"><label class="form-label">Deskripsi</label><textarea name="description" class="form-control" rows="2"><?= e($_POST['description']) ?></textarea></div>
            <div class="mb-3 form-check">
                <input type="checkbox" name="is_active" class="form-check-input" id="isActive" <?= $_POST['is_active'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="isActive">Lokasi Aktif</label>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="index.php" class="btn btn-outline-secondary">Batal</a>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
