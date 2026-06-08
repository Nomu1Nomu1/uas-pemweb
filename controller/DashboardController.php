<?php
require_once __DIR__ . '/../config/db.php';

class DashboardController
{
    public function index()
    {
        if (!is_logged_in()) {
            redirect('auth/login');
        }

        $db = getDB();

        $totalProduk = $db->query("SELECT COUNT(*) AS total FROM product")->fetch_assoc()['total'];

        $stockMenipis = $db->query("SELECT COUNT(*) AS total FROM product WHERE stock <= stock_min")->fetch_assoc()['total'];

        $totalTRXHariIni = $db->query("SELECT COUNT(*) AS total FROM transaksi WHERE DATE(tanggal_transaksi) = CURDATE() AND status = 'Selesai'")->fetch_assoc()['total'];

        $pendapatanHariIni = $db->query("SELECT COALESCE(SUM(total_harga), 0) AS total FROM transaksi WHERE DATE(tanggal_transaksi) = CURDATE() AND status = 'Selesai'")->fetch_assoc()['total'];

        $pengadaanPending = $db->query("SELECT COUNT(*) AS total FROM pengadaan WHERE status = 'Pending'")->fetch_assoc()['total'];

        $transaksiTerakhir = $db->query("SELECT t.no_trx, t.tanggal_transaksi, t.total_harga, t.status, u.nama AS kasir FROM transaksi t JOIN users u ON t.user_id = u.user_id ORDER BY t.id DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
        
        $listStockMenipis = $db->query("SELECT p.kode_barang, p.nama_barang, p.stock, p.stock_min, k.nama_kategori FROM product p JOIN kategori_product k ON p.kategori_id = k.id WHERE p.stock <= p.stock_min ORDER BY p.stock ASC LIMIT 10")->fetch_all(MYSQLI_ASSOC);

        $pageTitle = 'Dashboard';
        require_once __DIR__ . '/../view/dashboard/index.php';
    }
}