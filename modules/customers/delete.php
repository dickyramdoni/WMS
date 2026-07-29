<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole(['admin']);
verifyCsrfToken($_GET['csrf_token'] ?? '');
$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
try {
    $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    setFlash('success', 'Customer berhasil dihapus.');
} catch (PDOException $ex) {
    setFlash('error', 'Data tidak bisa dihapus karena masih memiliki transaksi terkait.');
}
redirect(APP_URL . '/modules/customers/index.php');
