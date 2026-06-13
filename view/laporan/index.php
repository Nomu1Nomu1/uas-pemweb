<?php
ob_start();
?>

<div class="container-fluid">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">Laporan Penjualan</h3>
            <p class="text-muted mb-0">
                Ringkasan penjualan dan produk terlaris
            </p>
        </div>

        <button onclick="window.print()" class="btn btn-success">
            <i class="bi bi-printer"></i> Cetak
        </button>
    </div>

    <!-- Filter -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">

                <input type="hidden" name="url" value="laporan/penjualan">

                <div class="col-md-4">
                    <label class="form-label fw-semibold">
                        Dari Tanggal
                    </label>
                    <input type="date" name="dari" class="form-control" value="<?= htmlspecialchars($dari) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">
                        Sampai Tanggal
                    </label>
                    <input type="date" name="sampai" class="form-control" value="<?= htmlspecialchars($sampai) ?>">
                </div>

                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i>
                        Tampilkan
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- Summary -->
    <div class="row mb-4">

        <div class="col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted mb-1">
                        Total Transaksi
                    </div>

                    <h2 class="fw-bold text-primary mb-0">
                        <?= number_format($summary['total_trx'] ?? 0) ?>
                    </h2>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted mb-1">
                        Total Penjualan
                    </div>

                    <h2 class="fw-bold text-success mb-0">
                        Rp <?= number_format($summary['grand_total'] ?? 0, 0, ',', '.') ?>
                    </h2>
                </div>
            </div>
        </div>

    </div>

    <!-- Penjualan Harian -->
    <div class="card border-0 shadow-sm mb-4">

        <div class="card-header bg-white">
            <h5 class="mb-0 fw-bold">
                Penjualan Harian
            </h5>
        </div>

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">
                        <tr>
                            <th width="60">No</th>
                            <th>Tanggal</th>
                            <th>Jumlah Transaksi</th>
                            <th>Total Penjualan</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (!empty($penjualanHarian)): ?>

                            <?php foreach ($penjualanHarian as $i => $row): ?>

                                <tr>
                                    <td><?= $i + 1 ?></td>

                                    <td>
                                        <?= date('d M Y', strtotime($row['tgl'])) ?>
                                    </td>

                                    <td>
                                        <?= number_format($row['jumlah_trx']) ?>
                                    </td>

                                    <td>
                                        Rp <?= number_format($row['total_penjualan'], 0, ',', '.') ?>
                                    </td>
                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    Tidak ada data penjualan
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <?php if (empty($produkStokHabis)): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill"></i>
            Produk sedang kehabisan stok.
        </div>
    <?php else: ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i>
            Semua produk masih tersedia.
        </div>
    <?php endif; ?>

    <!-- Produk Terlaris -->
    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white">
            <h5 class="mb-0 fw-bold">
                10 Produk Terlaris
            </h5>
        </div>

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">
                        <tr>
                            <th width="60">No</th>
                            <th>Kode Barang</th>
                            <th>Nama Barang</th>
                            <th>Kategori</th>
                            <th>Total Terjual</th>
                            <th>Total Pendapatan</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (!empty($produkTerlaris)): ?>

                            <?php foreach ($produkTerlaris as $i => $produk): ?>

                                <tr>

                                    <td><?= $i + 1 ?></td>

                                    <td>
                                        <?= htmlspecialchars($produk['kode_barang']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($produk['nama_barang']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($produk['nama_kategori']) ?>
                                    </td>

                                    <td>
                                        <?= number_format($produk['total_terjual']) ?>
                                    </td>

                                    <td>
                                        Rp <?= number_format($produk['total_pendapatan'], 0, ',', '.') ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    Belum ada data penjualan
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<div class="card border-0 shadow-sm mt-4">

    <div class="card-header bg-danger text-white">

        <h5 class="mb-0">
            <i class="bi bi-exclamation-triangle-fill"></i>
            Produk Stok Habis
        </h5>

    </div>

    <div class="card-body p-0">

        <div class="table-responsive">

            <table class="table table-hover mb-0">

                <thead class="table-light">

                    <tr>
                        <th>No</th>
                        <th>Kode Barang</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Stok</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if (!empty($stokHabis)): ?>

                        <?php foreach ($stokHabis as $i => $barang): ?>

                            <tr>

                                <td><?= $i + 1 ?></td>

                                <td><?= htmlspecialchars($barang['kode_barang']) ?></td>

                                <td><?= htmlspecialchars($barang['nama_barang']) ?></td>

                                <td><?= htmlspecialchars($barang['nama_kategori']) ?></td>

                                <td>
                                    Rp <?= number_format($barang['harga_jual'], 0, ',', '.') ?>
                                </td>

                                <td>
                                    <span class="badge bg-danger">
                                        <?= $barang['stok'] ?>
                                    </span>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="6" class="text-center py-4">

                                <i class="bi bi-check-circle-fill text-success"></i>

                                <br>

                                Tidak ada produk yang kehabisan stok

                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<?php
$content = ob_get_clean();

$title = $pageTitle ?? 'Laporan Penjualan';

require __DIR__ . '/../layouts/main.php';
?>