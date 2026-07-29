<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole(['admin','supervisor']);
$pageTitle = 'Edit Customer';
$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) { setFlash('error', 'Data tidak ditemukan.'); redirect(APP_URL . '/modules/customers/index.php'); }
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $name = trim($_POST['name']);
    $contact = trim($_POST['contact_person']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    if ($name === '') {
        $error = 'Nama wajib diisi.';
    } else {
        $stmt = $pdo->prepare("UPDATE customers SET name=?, contact_person=?, phone=?, address=? WHERE id=?");
        $stmt->execute([$name, $contact, $phone, $address, $id]);
        setFlash('success', 'Customer berhasil diperbarui.');
        redirect(APP_URL . '/modules/customers/index.php');
    }
} else {
    $_POST = $row;
}
require_once __DIR__ . '/../../includes/header.php';
?>
<h4 class="mb-3">Edit Customer</h4>
<div class="card" style="max-width:600px;">
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <form method="POST">
            <?= csrfField() ?>
            <div class="mb-3"><label class="form-label">Nama Customer</label><input type="text" name="name" class="form-control" value="<?= e($_POST['name']) ?>" required></div>
            <div class="mb-3"><label class="form-label">Nama Kontak</label><input type="text" name="contact_person" class="form-control" value="<?= e($_POST['contact_person']) ?>"></div>
            <div class="mb-3"><label class="form-label">Telepon</label><input type="text" name="phone" class="form-control" value="<?= e($_POST['phone']) ?>"></div>
            <div class="mb-3"><label class="form-label">Alamat</label><textarea name="address" class="form-control" rows="2"><?= e($_POST['address']) ?></textarea></div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="index.php" class="btn btn-outline-secondary">Batal</a>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
