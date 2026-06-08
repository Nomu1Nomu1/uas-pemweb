<?php
require_once __DIR__ . '/../config/db.php';

class LaporanController {

    public function index() {
        if (!is_logged_in()) redirect('auth/login');
        allow_roles(['owner', 'admin']);

        $pageTitle = 'Laporan';
        require_once __DIR__ . '/../view/laporan/index.php';
    }

    public function penjualan() {
        if (!is_logged_in()) redirect('auth/login');
        allow_roles(['owner', 'admin']);

        $db        = getDB();
        $dari      = $_GET['dari']   ?? date('Y-m-01');
        $sampai    = $_GET['sampai'] ?? date('Y-m-d');
        
        $stmt = $db->prepare(
            "SELECT DATE(tanggal_transaksi) AS tgl,
                    COUNT(*) AS jumlah_trx,
                    SUM(total_harga) AS total_penjualan
             FROM transaksi
             WHERE status = 'Selesai'
               AND DATE(tanggal_transaksi) BETWEEN ? AND ?
             GROUP BY DATE(tanggal_transaksi)
             ORDER BY tgl ASC"
        );
        $stmt->bind_param('ss', $dari, $sampai);
        $stmt->execute();
        $penjualanHarian = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $stmt2 = $db->prepare(
            "SELECT p.kode_barang, p.nama_barang, k.nama_kategori,
                    SUM(dt.qty) AS total_terjual,
                    SUM(dt.subtotal) AS total_pendapatan
             FROM detail_transaksi dt
             JOIN transaksi t ON dt.id_trx = t.id
             JOIN product p   ON dt.produk_id = p.id
             JOIN kategori_product k ON p.kategori_id = k.id
             WHERE t.status = 'Selesai'
               AND DATE(t.tanggal_transaksi) BETWEEN ? AND ?
             GROUP BY dt.produk_id
             ORDER BY total_terjual DESC
             LIMIT 10"
        );
        $stmt2->bind_param('ss', $dari, $sampai);
        $stmt2->execute();
        $produkTerlaris = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

        $stmt3 = $db->prepare(
            "SELECT COUNT(*) AS total_trx, COALESCE(SUM(total_harga), 0) AS grand_total
             FROM transaksi
             WHERE status = 'Selesai'
               AND DATE(tanggal_transaksi) BETWEEN ? AND ?"
        );
        $stmt3->bind_param('ss', $dari, $sampai);
        $stmt3->execute();
        $summary = $stmt3->get_result()->fetch_assoc();

        $pageTitle = 'Laporan Penjualan';
        require_once __DIR__ . '/../view/laporan/penjualan.php';
    }

    public function stokHabis() {
        if (!is_logged_in()) redirect('auth/login');
        allow_roles(['owner', 'admin']);

        $db = getDB();

        $produkStokHabis = $db->query(
            "SELECT p.kode_barang, p.nama_barang, p.stock, p.stock_min,
                    p.satuan, k.nama_kategori, d.nama_distributor, d.no_hp AS dist_no_hp
             FROM product p
             JOIN kategori_product k ON p.kategori_id   = k.id
             JOIN distributors     d ON p.distributor_id = d.id
             WHERE p.stock <= p.stock_min
             ORDER BY p.stock ASC"
        )->fetch_all(MYSQLI_ASSOC);

        $pageTitle = 'Laporan Stok Menipis / Habis';
        require_once __DIR__ . '/../view/laporan/stok_habis.php';
    }
}