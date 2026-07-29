<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole(['admin']);
verifyCsrfToken($_GET['csrf_token'] ?? '');
$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
try {
    $stmt = $pdo->prepare("DELETE FROM warehouses WHERE id = ?");
    $stmt->execute([$id]);
    setFlash('success', 'Gudang berhasil dihapus.');
} catch (PDOException $ex) {
    setFlash('error', 'Gudang tidak bisa dihapus karena masih memiliki lokasi/rak terkait.');
}
redirect(APP_URL . '/modules/warehouses/index.php');
