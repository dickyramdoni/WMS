<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole(['admin']);
$pageTitle = 'Manajemen User';
require_once __DIR__ . '/../../includes/header.php';

$pdo = getDB();
$users = $pdo->query("SELECT * FROM users ORDER BY full_name")->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Manajemen User</h4>
    <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah User</a>
</div>
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Username</th><th>Nama Lengkap</th><th>Role</th><th class="text-center">Status</th><th class="text-center">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= e($u['username']) ?></td>
                    <td><?= e($u['full_name']) ?></td>
                    <td class="text-capitalize"><?= e($u['role']) ?></td>
                    <td class="text-center"><span class="badge bg-<?= $u['is_active'] ? 'success' : 'secondary' ?>"><?= $u['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                    <td class="text-center">
                        <a href="edit.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                        <a href="delete.php?id=<?= $u['id'] ?>&csrf_token=<?= generateCsrfToken() ?>" class="btn btn-sm btn-outline-danger btn-delete-confirm" data-confirm-msg="Hapus user ini?"><i class="bi bi-trash"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
