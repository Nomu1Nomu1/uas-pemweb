<?php
$currentFolder = basename(dirname($_SERVER['PHP_SELF']));
?>

<aside class="sidebar">

    <div class="sidebar-logo">
        <h3>UMKM Inventory</h3>
    </div>

    <ul class="sidebar-menu">

        <li>
            <a href="../dashboard/index.php" class="<?= ($currentFolder == 'dashboard') ? 'active' : '' ?>">
                <i class="bi bi-grid"></i>
                Dashboard
            </a>
        </li>

        <li>
            <a href="../produk/index.php" class="<?= ($currentFolder == 'produk') ? 'active' : '' ?>">
                <i class="bi bi-box"></i>
                Produk
            </a>
        </li>

        <li>
            <a href="../kategori/index.php" class="<?= ($currentFolder == 'kategori') ? 'active' : '' ?>">
                <i class="bi bi-tag"></i>
                Kategori
            </a>
        </li>

        <li>
            <a href="../distributor/index.php" class="<?= ($currentFolder == 'distributor') ? 'active' : '' ?>">
                <i class="bi bi-truck"></i>
                Distributor
            </a>
        </li>

        <li>
            <a href="../pengadaan/index.php" class="<?= ($currentFolder == 'pengadaan') ? 'active' : '' ?>">
                <i class="bi bi-cart"></i>
                Pengadaan
            </a>
        </li>

        <li>
            <a href="../transaksi/index.php" class="<?= ($currentFolder == 'transaksi') ? 'active' : '' ?>">
                <i class="bi bi-credit-card"></i>
                Transaksi
            </a>
        </li>

        <li>
            <a href="../laporan/index.php" class="<?= ($currentFolder == 'laporan') ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-text"></i>
                Laporan
            </a>
        </li>

    </ul>

    <div class="sidebar-user">

        <div class="avatar">
            <?= strtoupper(substr($_SESSION['users']['nama'] ?? 'A', 0, 1)) ?>
        </div>

        <div>
            <strong><?= $_SESSION['users']['nama'] ?? 'Admin' ?></strong>
            <br>
            <small><?= $_SESSION['users']['email'] ?? 'admin@umkm.com' ?></small>
        </div>

    </div>

    <a href="../auth/logout.php" class="logout-btn">
        <i class="bi bi-box-arrow-right"></i>
        Logout
    </a>

</aside>