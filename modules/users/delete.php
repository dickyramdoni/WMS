<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole(['admin']);
verifyCsrfToken($_GET['csrf_token'] ?? '');
$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);

if ($id == $_SESSION['user_id']) {
    setFlash('error', 'Tidak bisa menghapus akun sendiri.');
} else {
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'User berhasil dihapus.');
    } catch (PDOException $ex) {
        setFlash('error', 'User tidak bisa dihapus karena masih memiliki data transaksi terkait. Nonaktifkan saja user tersebut.');
    }
}
redirect(APP_URL . '/modules/users/index.php');
