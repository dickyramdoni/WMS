<?php
// ============================================
// Script instalasi awal - jalankan sekali saja lalu HAPUS file ini
// ============================================
require_once __DIR__ . '/config/database.php';

$message = '';
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? 'admin');
    $password = $_POST['password'] ?? '';
    $fullName = trim($_POST['full_name'] ?? 'Administrator');

    if (strlen($password) < 6) {
        $message = 'Password minimal 6 karakter.';
    } else {
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $message = 'Username sudah terdaftar. Silakan login, atau gunakan username lain.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $ins = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, 'admin')");
                $ins->execute([$username, $hash, $fullName]);
                $done = true;
            }
        } catch (Exception $e) {
            $message = 'Gagal terhubung ke database: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Instalasi WMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:500px;">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h4 class="mb-3">Instalasi WMS - Buat Akun Admin</h4>
            <?php if ($done): ?>
                <div class="alert alert-success">
                    Akun admin berhasil dibuat! <strong>Hapus file install.php sekarang</strong>, lalu
                    <a href="login.php">klik di sini untuk login</a>.
                </div>
            <?php else: ?>
                <?php if ($message): ?><div class="alert alert-danger"><?= htmlspecialchars($message) ?></div><?php endif; ?>
                <p class="text-muted small">Database SQLite akan dibuat otomatis saat halaman ini pertama kali diakses (tidak perlu import manual). Pastikan folder <code>database/</code> memiliki izin tulis.</p>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" value="admin" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="full_name" class="form-control" value="Administrator" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Buat Akun Admin</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
