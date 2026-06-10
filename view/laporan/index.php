<?php ob_start(); ?>

<div class="report-header">

    <div>
        <h1 class="page-title">Laporan</h1>
        <p class="page-subtitle">
            Analisis penjualan dan inventaris
        </p>
    </div>

    <button class="btn btn-success">
        <i class="bi bi-download"></i>
        Export PDF
    </button>

</div>

<div class="report-filter card-section">

    <div class="d-flex gap-3 align-items-center">

        <i class="bi bi-calendar3"></i>

        <select class="form-select report-select">
            <option>Bulan Ini</option>
            <option>3 Bulan</option>
            <option>6 Bulan</option>
            <option>1 Tahun</option>
        </select>

        <button class="btn btn-primary">
            Terapkan
        </button>

    </div>

</div>

<div class="row g-4 mt-2">

    <div class="col-md-4">

        <div class="stat-card stat-blue">

            <small>Total Penjualan</small>

            <h2>Rp 329M</h2>

            <span>+18% dari periode lalu</span>

        </div>

    </div>

    <div class="col-md-4">

        <div class="stat-card stat-green">

            <small>Transaksi</small>

            <h2>2,450</h2>

            <span>+12% dari periode lalu</span>

        </div>

    </div>

    <div class="col-md-4">

        <div class="stat-card stat-purple">

            <small>Rata-rata Transaksi</small>

            <h2>Rp 134K</h2>

            <span>+5% dari periode lalu</span>

        </div>

    </div>

</div>

<div class="row g-4 mt-2">

    <div class="col-lg-6">

        <div class="card-section">

            <h3>Grafik Penjualan 6 Bulan Terakhir</h3>

            <div class="report-bar">
                <span>Jan</span>
                <div class="bar-track">
                    <div class="bar-fill" style="width:70%"></div>
                </div>
                <strong>Rp 45M</strong>
            </div>

            <div class="report-bar">
                <span>Feb</span>
                <div class="bar-track">
                    <div class="bar-fill" style="width:80%"></div>
                </div>
                <strong>Rp 52M</strong>
            </div>

            <div class="report-bar">
                <span>Mar</span>
                <div class="bar-track">
                    <div class="bar-fill" style="width:74%"></div>
                </div>
                <strong>Rp 48M</strong>
            </div>

        </div>

    </div>

    <div class="col-lg-6">

        <div class="card-section">

            <h3>Produk Terlaris</h3>

            <div class="top-product">

                <div class="rank">1</div>

                <div>
                    <strong>Beras Premium 5kg</strong>
                    <div>Terjual: 1250 unit</div>
                </div>

                <span class="price">
                    Rp 93.8M
                </span>

            </div>

        </div>

    </div>

</div>

<div class="card-section mt-4">

    <h3>Jenis Laporan</h3>

    <div class="row g-3 mt-2">

        <div class="col-md-4">
            <div class="report-type">
                <i class="bi bi-bar-chart-line"></i>
                <h5>Laporan Penjualan</h5>
                <p>Detail transaksi penjualan</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="report-type">
                <i class="bi bi-graph-up-arrow"></i>
                <h5>Laporan Stok</h5>
                <p>Kondisi stok saat ini</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="report-type">
                <i class="bi bi-truck"></i>
                <h5>Laporan Pengadaan</h5>
                <p>Riwayat pembelian barang</p>
            </div>
        </div>

    </div>

</div>

<?php
$content = ob_get_clean();
require_once '../layouts/main.php';
?>