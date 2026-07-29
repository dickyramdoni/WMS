<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole(['admin']);
verifyCsrfToken($_GET['csrf_token'] ?? '');
$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
try {
    $stmt = $pdo->prepare("DELETE FROM locations WHERE id = ?");
    $stmt->execute([$id]);
    setFlash('success', 'Lokasi berhasil dihapus.');
} catch (PDOException $ex) {
    setFlash('error', 'Lokasi tidak bisa dihapus karena masih memiliki data stok/transaksi terkait.');
}
redirect(APP_URL . '/modules/locations/index.php');
