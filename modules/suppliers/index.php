<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
$pageTitle = 'Supplier';
require_once __DIR__ . '/../../includes/header.php';

$pdo = getDB();
$rows = $pdo->query("SELECT * FROM suppliers ORDER BY name")->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Data Supplier</h4>
    <?php if (in_array($_SESSION['role'], ['admin','supervisor'])): ?>
        <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Supplier</a>
    <?php endif; ?>
</div>
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Nama</th><th>Kontak</th><th>Telepon</th><th>Alamat</th><th class="text-center">Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data</td></tr>
            <?php else: foreach ($rows as $r): ?>
                <tr>
                    <td><?= e($r['name']) ?></td>
                    <td><?= e($r['contact_person']) ?></td>
                    <td><?= e($r['phone']) ?></td>
                    <td><?= e($r['address']) ?></td>
                    <td class="text-center">
                        <?php if (in_array($_SESSION['role'], ['admin','supervisor'])): ?>
                        <a href="edit.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="delete.php?id=<?= $r['id'] ?>&csrf_token=<?= generateCsrfToken() ?>" class="btn btn-sm btn-outline-danger btn-delete-confirm" data-confirm-msg="Hapus data ini?"><i class="bi bi-trash"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
