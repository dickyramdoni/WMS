<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole(['admin','supervisor']);
$pageTitle = 'Tambah Lokasi';
$pdo = getDB();
$warehouses = $pdo->query("SELECT * FROM warehouses WHERE is_active = 1 ORDER BY code")->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $warehouseId = (int)$_POST['warehouse_id'];
    $code = trim($_POST['code']);
    $name = trim($_POST['name']);
    $zone = trim($_POST['zone']);
    $rack = trim($_POST['rack']);
    $desc = trim($_POST['description']);
    if (!$warehouseId || $code === '' || $name === '') {
        $error = 'Gudang, kode, dan nama lokasi wajib diisi.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO locations (warehouse_id, code, name, zone, rack, description) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$warehouseId, $code, $name, $zone, $rack, $desc]);
            setFlash('success', 'Lokasi berhasil ditambahkan.');
            redirect(APP_URL . '/modules/locations/index.php');
        } catch (PDOException $ex) {
            $error = isDuplicateKeyError($ex->getMessage()) ? 'Kode lokasi sudah digunakan.' : 'Gagal menyimpan data.';
        }
    }
}
require_once __DIR__ . '/../../includes/header.php';
?>
<h4 class="mb-3">Tambah Lokasi / Rak Gudang</h4>
<div class="card" style="max-width:600px;">
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <?php if (empty($warehouses)): ?>
            <div class="alert alert-warning">Belum ada data gudang aktif. <a href="../warehouses/create.php">Tambah gudang</a> terlebih dahulu.</div>
        <?php else: ?>
        <form method="POST">
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">Gudang</label>
                <select name="warehouse_id" class="form-select" required>
                    <option value="">- Pilih Gudang -</option>
                    <?php foreach ($warehouses as $w): ?>
                        <option value="<?= $w['id'] ?>"><?= e($w['code']) ?> - <?= e($w['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3"><label class="form-label">Kode Lokasi</label><input type="text" name="code" class="form-control" placeholder="cth: A-01-01" value="<?= e($_POST['code'] ?? '') ?>" required></div>
            <div class="mb-3"><label class="form-label">Nama Lokasi</label><input type="text" name="name" class="form-control" value="<?= e($_POST['name'] ?? '') ?>" required></div>
            <div class="row">
                <div class="col-6 mb-3"><label class="form-label">Zone</label><input type="text" name="zone" class="form-control" value="<?= e($_POST['zone'] ?? '') ?>"></div>
                <div class="col-6 mb-3"><label class="form-label">Rak</label><input type="text" name="rack" class="form-control" value="<?= e($_POST['rack'] ?? '') ?>"></div>
            </div>
            <div class="mb-3"><label class="form-label">Deskripsi</label><textarea name="description" class="form-control" rows="2"><?= e($_POST['description'] ?? '') ?></textarea></div>
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="index.php" class="btn btn-outline-secondary">Batal</a>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
