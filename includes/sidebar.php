<?php
$role = $_SESSION['role'] ?? '';
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
function navActive($dir, $matchDir) {
    return $dir === $matchDir ? 'active' : '';
}
?>
<aside class="wms-sidebar" id="wmsSidebar">
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>

        <li class="nav-header">Master Data</li>
        <li class="nav-item">
            <a class="nav-link <?= navActive($currentDir,'products') ?>" href="<?= APP_URL ?>/modules/products/index.php">
                <i class="bi bi-box"></i> Produk
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= navActive($currentDir,'warehouses') ?>" href="<?= APP_URL ?>/modules/warehouses/index.php">
                <i class="bi bi-building"></i> Gudang
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= navActive($currentDir,'locations') ?>" href="<?= APP_URL ?>/modules/locations/index.php">
                <i class="bi bi-geo-alt"></i> Lokasi / Rak
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= navActive($currentDir,'suppliers') ?>" href="<?= APP_URL ?>/modules/suppliers/index.php">
                <i class="bi bi-truck"></i> Supplier
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= navActive($currentDir,'customers') ?>" href="<?= APP_URL ?>/modules/customers/index.php">
                <i class="bi bi-people"></i> Customer
            </a>
        </li>

        <li class="nav-header">Inbound</li>
        <li class="nav-item">
            <a class="nav-link <?= navActive($currentDir,'purchase_orders') ?>" href="<?= APP_URL ?>/modules/purchase_orders/index.php">
                <i class="bi bi-file-earmark-text"></i> Purchase Order
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= navActive($currentDir,'goods_receipts') ?>" href="<?= APP_URL ?>/modules/goods_receipts/index.php">
                <i class="bi bi-box-arrow-in-down"></i> Penerimaan Barang
            </a>
        </li>

        <li class="nav-header">Outbound</li>
        <li class="nav-item">
            <a class="nav-link <?= navActive($currentDir,'shipping_orders') ?>" href="<?= APP_URL ?>/modules/shipping_orders/index.php">
                <i class="bi bi-box-arrow-up"></i> Pengiriman Barang
            </a>
        </li>

        <li class="nav-header">Stok & Laporan</li>
        <li class="nav-item">
            <a class="nav-link <?= navActive($currentDir,'stock') ?>" href="<?= APP_URL ?>/modules/stock/index.php">
                <i class="bi bi-clipboard-data"></i> Stok Barang
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= navActive($currentDir,'stock') ?>" href="<?= APP_URL ?>/modules/stock/by_warehouse.php">
                <i class="bi bi-building-gear"></i> Stok per Gudang
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= navActive($currentDir,'reports') ?>" href="<?= APP_URL ?>/modules/reports/index.php">
                <i class="bi bi-bar-chart"></i> Laporan
            </a>
        </li>

        <?php if ($role === 'admin'): ?>
        <li class="nav-header">Administrasi</li>
        <li class="nav-item">
            <a class="nav-link <?= navActive($currentDir,'users') ?>" href="<?= APP_URL ?>/modules/users/index.php">
                <i class="bi bi-person-gear"></i> Manajemen User
            </a>
        </li>
        <?php endif; ?>
    </ul>
</aside>
<script>
document.getElementById('sidebarToggle')?.addEventListener('click', function() {
    document.getElementById('wmsSidebar').classList.toggle('show');
});
</script>
