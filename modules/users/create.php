<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole(['admin']);
$pageTitle = 'Tambah User';
$pdo = getDB();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $username = trim($_POST['username']);
    $fullName = trim($_POST['full_name']);
    $role = $_POST['role'];
    $password = $_POST['password'];

    if ($username === '' || $fullName === '' || strlen($password) < 6) {
        $error = 'Username, nama lengkap wajib diisi dan password minimal 6 karakter.';
    } else {
        try {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $hash, $fullName, $role]);
            setFlash('success', 'User berhasil ditambahkan.');
            redirect(APP_URL . '/modules/users/index.php');
        } catch (PDOException $ex) {
            $error = isDuplicateKeyError($ex->getMessage()) ? 'Username sudah digunakan.' : 'Gagal menyimpan data.';
        }
    }
}
require_once __DIR__ . '/../../includes/header.php';
?>
<h4 class="mb-3">Tambah User</h4>
<div class="card" style="max-width:500px;">
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <form method="POST">
            <?= csrfField() ?>
            <div class="mb-3"><label class="form-label">Username</label><input type="text" name="username" class="form-control" value="<?= e($_POST['username'] ?? '') ?>" required></div>
            <div class="mb-3"><label class="form-label">Nama Lengkap</label><input type="text" name="full_name" class="form-control" value="<?= e($_POST['full_name'] ?? '') ?>" required></div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select" required>
                    <option value="staff">Staff Gudang</option>
                    <option value="supervisor">Supervisor</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required minlength="6"></div>
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="index.php" class="btn btn-outline-secondary">Batal</a>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
