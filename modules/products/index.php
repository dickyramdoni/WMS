<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
$pageTitle = 'Produk';
require_once __DIR__ . '/../../includes/header.php';

$pdo = getDB();
$search = trim($_GET['q'] ?? '');

$sql = "SELECT p.*, c.name AS category_name, COALESCE(SUM(s.qty),0) AS total_stock
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN stock s ON s.product_id = p.id
        WHERE 1=1";
$params = [];
if ($search !== '') {
    $sql .= " AND (p.sku LIKE ? OR p.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " GROUP BY p.id ORDER BY p.name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Data Produk</h4>
    <?php if (in_array($_SESSION['role'], ['admin','supervisor'])): ?>
        <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Produk</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <form class="row g-2 mb-3" method="GET">
            <div class="col-auto flex-grow-1">
                <input type="text" name="q" class="form-control" placeholder="Cari SKU atau nama produk..." value="<?= e($search) ?>">
            </div>
            <div class="col-auto">
                <button class="btn btn-outline-secondary"><i class="bi bi-search"></i> Cari</button>
            </div>
        </form>

        <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>SKU</th><th>Nama Produk</th><th>Kategori</th><th>Satuan</th>
                    <th class="text-end">Stok Total</th><th class="text-end">Min. Stok</th><th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($products)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data produk</td></tr>
            <?php else: foreach ($products as $p): ?>
                <tr>
                    <td><?= e($p['sku']) ?></td>
                    <td><?= e($p['name']) ?></td>
                    <td><?= e($p['category_name'] ?? '-') ?></td>
                    <td><?= e($p['unit']) ?></td>
                    <td class="text-end <?= $p['total_stock'] <= $p['min_stock'] ? 'text-danger fw-semibold' : '' ?>"><?= formatNumber($p['total_stock']) ?></td>
                    <td class="text-end"><?= formatNumber($p['min_stock']) ?></td>
                    <td class="text-center">
                        <?php if (in_array($_SESSION['role'], ['admin','supervisor'])): ?>
                        <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="delete.php?id=<?= $p['id'] ?>&csrf_token=<?= generateCsrfToken() ?>" class="btn btn-sm btn-outline-danger btn-delete-confirm" data-confirm-msg="Hapus produk ini?"><i class="bi bi-trash"></i></a>
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
