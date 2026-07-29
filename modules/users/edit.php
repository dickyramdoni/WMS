<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole(['admin']);
$pageTitle = 'Edit User';
$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$u = $stmt->fetch();
if (!$u) { setFlash('error', 'User tidak ditemukan.'); redirect(APP_URL . '/modules/users/index.php'); }
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $fullName = trim($_POST['full_name']);
    $role = $_POST['role'];
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $newPassword = $_POST['password'] ?? '';

    if ($fullName === '') {
        $error = 'Nama lengkap wajib diisi.';
    } else {
        try {
            if ($newPassword !== '') {
                if (strlen($newPassword) < 6) throw new Exception('Password baru minimal 6 karakter.');
                $hash = password_hash($newPassword, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET full_name=?, role=?, is_active=?, password=? WHERE id=?");
                $stmt->execute([$fullName, $role, $isActive, $hash, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name=?, role=?, is_active=? WHERE id=?");
                $stmt->execute([$fullName, $role, $isActive, $id]);
            }
            setFlash('success', 'User berhasil diperbarui.');
            redirect(APP_URL . '/modules/users/index.php');
        } catch (Exception $ex) {
            $error = $ex->getMessage();
        }
    }
} else {
    $_POST = $u;
}
require_once __DIR__ . '/../../includes/header.php';
?>
<h4 class="mb-3">Edit User</h4>
<div class="card" style="max-width:500px;">
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <form method="POST">
            <?= csrfField() ?>
            <div class="mb-3"><label class="form-label">Username</label><input type="text" class="form-control" value="<?= e($u['username']) ?>" disabled></div>
            <div class="mb-3"><label class="form-label">Nama Lengkap</label><input type="text" name="full_name" class="form-control" value="<?= e($_POST['full_name']) ?>" required></div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select" required>
                    <option value="staff" <?= $_POST['role']==='staff'?'selected':'' ?>>Staff Gudang</option>
                    <option value="supervisor" <?= $_POST['role']==='supervisor'?'selected':'' ?>>Supervisor</option>
                    <option value="admin" <?= $_POST['role']==='admin'?'selected':'' ?>>Admin</option>
                </select>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" name="is_active" class="form-check-input" id="isActive" <?= $_POST['is_active'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="isActive">User Aktif</label>
            </div>
            <div class="mb-3"><label class="form-label">Password Baru (kosongkan jika tidak diubah)</label><input type="password" name="password" class="form-control" minlength="6"></div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="index.php" class="btn btn-outline-secondary">Batal</a>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
