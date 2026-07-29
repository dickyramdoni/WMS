<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
$pageTitle = 'Stok Produk per Gudang';
require_once __DIR__ . '/../../includes/header.php';

$pdo = getDB();
$search = trim($_GET['q'] ?? '');
$warehouseFilter = (int)($_GET['warehouse_id'] ?? 0);

$warehouses = $pdo->query("SELECT * FROM warehouses WHERE is_active = 1 ORDER BY code")->fetchAll();

// Ambil semua produk aktif (filter pencarian jika ada)
$prodSql = "SELECT * FROM products WHERE is_active = 1";
$prodParams = [];
if ($search !== '') {
    $prodSql .= " AND (sku LIKE ? OR name LIKE ?)";
    $prodParams = ["%$search%", "%$search%"];
}
$prodSql .= " ORDER BY name";
$stmt = $pdo->prepare($prodSql);
$stmt->execute($prodParams);
$products = $stmt->fetchAll();

// Ambil total stok per produk per gudang (agregat semua lokasi/rak dalam gudang tsb)
$stockMap = []; // [product_id][warehouse_id] = qty
$stmt2 = $pdo->query("
    SELECT s.product_id, l.warehouse_id, SUM(s.qty) AS total_qty
    FROM stock s
    JOIN locations l ON l.id = s.location_id
    GROUP BY s.product_id, l.warehouse_id
");
foreach ($stmt2->fetchAll() as $row) {
    $stockMap[$row['product_id']][$row['warehouse_id']] = (int)$row['total_qty'];
}

$displayedWarehouses = $warehouseFilter
    ? array_values(array_filter($warehouses, fn($w) => $w['id'] == $warehouseFilter))
    : $warehouses;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Stok Produk per Gudang</h4>
    <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Stok per Lokasi</a>
</div>

<div class="card">
    <div class="card-body">
        <form class="row g-2 mb-3" method="GET">
            <div class="col-auto flex-grow-1">
                <input type="text" name="q" class="form-control" placeholder="Cari SKU atau nama produk..." value="<?= e($search) ?>">
            </div>
            <div class="col-auto">
                <select name="warehouse_id" class="form-select">
                    <option value="">Semua Gudang</option>
                    <?php foreach ($warehouses as $w): ?>
                        <option value="<?= $w['id'] ?>" <?= $warehouseFilter == $w['id'] ? 'selected' : '' ?>><?= e($w['code']) ?> - <?= e($w['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto"><button class="btn btn-outline-secondary"><i class="bi bi-search"></i> Filter</button></div>
        </form>

        <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Nama Produk</th>
                    <th>Satuan</th>
                    <?php foreach ($displayedWarehouses as $w): ?>
                        <th class="text-end"><?= e($w['code']) ?><br><small class="text-muted fw-normal"><?= e($w['name']) ?></small></th>
                    <?php endforeach; ?>
                    <th class="text-end bg-light">Total Semua Gudang</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($products) || empty($displayedWarehouses)): ?>
                <tr><td colspan="<?= 4 + count($displayedWarehouses) ?>" class="text-center text-muted py-4">Tidak ada data untuk ditampilkan</td></tr>
            <?php else: foreach ($products as $p):
                $total = 0;
                foreach ($displayedWarehouses as $w) { $total += $stockMap[$p['id']][$w['id']] ?? 0; }
            ?>
                <tr>
                    <td><?= e($p['sku']) ?></td>
                    <td><?= e($p['name']) ?></td>
                    <td><?= e($p['unit']) ?></td>
                    <?php foreach ($displayedWarehouses as $w):
                        $qty = $stockMap[$p['id']][$w['id']] ?? 0;
                    ?>
                        <td class="text-end <?= $qty == 0 ? 'text-muted' : '' ?>">
                            <a href="card_by_warehouse.php?product_id=<?= $p['id'] ?>&warehouse_id=<?= $w['id'] ?>" class="text-decoration-none <?= $qty == 0 ? 'text-muted' : '' ?>">
                                <?= formatNumber($qty) ?>
                            </a>
                        </td>
                    <?php endforeach; ?>
                    <td class="text-end fw-bold bg-light <?= $total <= $p['min_stock'] ? 'text-danger' : '' ?>"><?= formatNumber($total) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
        <p class="text-muted small mb-0">Klik angka pada tiap gudang untuk melihat detail lokasi/rak dan histori pergerakan stok produk tersebut di gudang itu.</p>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
