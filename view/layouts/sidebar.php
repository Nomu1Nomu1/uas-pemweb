<aside class="sidebar">

    <div class="sidebar-logo">
        <h3>UMKM Inventory</h3>
    </div>

    <ul class="sidebar-menu">

        <li>
            <a href="<?= BASE_URL ?>/dashboard/index">
                <i class="bi bi-grid"></i>
                Dashboard
            </a>
        </li>

        <li>
            <a href="<?= BASE_URL ?>/product/index">
                <i class="bi bi-box"></i>
                Produk
            </a>
        </li>

        <li>
            <a href="<?= BASE_URL ?>/kategori/index" class="active">
                <i class="bi bi-tag"></i>
                Kategori
            </a>
        </li>

        <li>
            <a href="<?= BASE_URL ?>/distributor/index">
                <i class="bi bi-truck"></i>
                Distributor
            </a>
        </li>

        <li>
            <a href="<?= BASE_URL ?>/pengadaan/index">
                <i class="bi bi-cart"></i>
                Pengadaan
            </a>
        </li>

        <li>
            <a href="<?= BASE_URL ?>/transaksi/index">
                <i class="bi bi-credit-card"></i>
                Transaksi
            </a>
        </li>

        <li>
            <a href="<?= BASE_URL ?>/laporan/index">
                <i class="bi bi-file-earmark-text"></i>
                Laporan
            </a>
        </li>

    </ul>

    <div class="sidebar-user">

        <div class="avatar">
            <?= strtoupper(substr($_SESSION['users']['nama'] ?? 'A',0,1)) ?>
        </div>

        <div>
            <strong><?= $_SESSION['users']['nama'] ?? 'Admin' ?></strong>
            <br>
            <small><?= $_SESSION['users']['email'] ?? 'admin@umkm.com' ?></small>
        </div>

    </div>

    <a href="<?= BASE_URL ?>/auth/logout" class="logout-btn">
        <i class="bi bi-box-arrow-right"></i>
        Logout
    </a>

</aside>