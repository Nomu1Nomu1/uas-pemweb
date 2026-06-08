<?php
require_once __DIR__ . '/../config/db.php';

class TransaksiController {

    public function index() {
        if (!is_logged_in()) redirect('auth/login');

        $db      = getDB();
        $search  = trim($_GET['search'] ?? '');
        $tanggal = trim($_GET['tanggal'] ?? '');

        $sql    = "SELECT t.*, u.nama AS kasir
                   FROM transaksi t
                   JOIN users u ON t.user_id = u.user_id
                   WHERE 1=1";
        $params = [];
        $types  = '';

        if ($search !== '') {
            $like    = "%$search%";
            $sql    .= " AND (t.no_trx LIKE ? OR u.nama LIKE ?)";
            $params[] = $like;
            $params[] = $like;
            $types   .= 'ss';
        }

        if ($tanggal !== '') {
            $sql    .= " AND DATE(t.tanggal_transaksi) = ?";
            $params[] = $tanggal;
            $types   .= 's';
        }

        $sql .= " ORDER BY t.id DESC";

        if ($params) {
            $stmt = $db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $transaksis = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $transaksis = $db->query($sql)->fetch_all(MYSQLI_ASSOC);
        }

        $pageTitle = 'Data Transaksi';
        require_once __DIR__ . '/../view/transaksi/index.php';
    }

    public function kasir() {
        if (!is_logged_in()) redirect('auth/login');

        $db       = getDB();
        $products = $db->query(
            "SELECT id, kode_barang, nama_barang, harga_jual, stock, satuan
             FROM product WHERE stock > 0 ORDER BY nama_barang"
        )->fetch_all(MYSQLI_ASSOC);

        $pageTitle = 'Kasir / POS';
        require_once __DIR__ . '/../view/transaksi/kasir.php';
    }

    public function create() {
        if (!is_logged_in()) redirect('auth/login');

        $db = getDB();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('transaksi/kasir');

        $items     = $_POST['items']  ?? [];
        $bayar     = (float)($_POST['bayar'] ?? 0);
        $keterangan = trim($_POST['keterangan'] ?? '') ?: null;

        if (empty($items)) {
            $_SESSION['flash'] = 'Tidak ada item yang dipilih.';
            redirect('transaksi/kasir');
        }

        $user_id     = $_SESSION['users']['id'];
        $now         = date('Y-m-d H:i:s');
        $no_trx      = 'TRX-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
        $total_harga = 0;

        // Validasi stok dan hitung total
        foreach ($items as $item) {
            $produk_id = (int)($item['produk_id'] ?? 0);
            $qty       = (int)($item['qty']       ?? 0);
            $harga     = (float)($item['harga_satuan'] ?? 0);

            if ($produk_id <= 0 || $qty <= 0) continue;

            $stmt = $db->prepare("SELECT stock, harga_jual FROM product WHERE id = ?");
            $stmt->bind_param('i', $produk_id);
            $stmt->execute();
            $prod = $stmt->get_result()->fetch_assoc();

            if (!$prod || $prod['stock'] < $qty) {
                $_SESSION['flash'] = "Stok produk tidak mencukupi.";
                redirect('transaksi/kasir');
            }

            $total_harga += $qty * $harga;
        }

        if ($bayar < $total_harga) {
            $_SESSION['flash'] = 'Jumlah bayar kurang dari total harga.';
            redirect('transaksi/kasir');
        }

        $kembalian = $bayar - $total_harga;

        $db->begin_transaction();
        try {
            $stmt = $db->prepare(
                "INSERT INTO transaksi
                 (no_trx, user_id, tanggal_transaksi, total_harga, bayar, kembalian, status, keterangan)
                 VALUES (?, ?, ?, ?, ?, ?, 'Selesai', ?)"
            );
            $stmt->bind_param('sisdddss', $no_trx, $user_id, $now, $total_harga, $bayar, $kembalian, $keterangan);
            $stmt->execute();
            $trx_id = $db->insert_id;

            foreach ($items as $item) {
                $produk_id = (int)($item['produk_id']    ?? 0);
                $qty       = (int)($item['qty']          ?? 0);
                $harga_sat = (float)($item['harga_satuan'] ?? 0);
                $subtotal  = $qty * $harga_sat;

                if ($produk_id <= 0 || $qty <= 0) continue;

                $stmt2 = $db->prepare(
                    "INSERT INTO detail_transaksi (id_trx, produk_id, qty, harga_satuan, subtotal)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt2->bind_param('iiidd', $trx_id, $produk_id, $qty, $harga_sat, $subtotal);
                $stmt2->execute();

                // Kurangi stok
                $stmt3 = $db->prepare("UPDATE product SET stock = stock - ? WHERE id = ?");
                $stmt3->bind_param('ii', $qty, $produk_id);
                $stmt3->execute();
            }

            $db->commit();
            $_SESSION['flash']    = "Transaksi $no_trx berhasil disimpan. Kembalian: Rp " . number_format($kembalian, 0, ',', '.');
            $_SESSION['last_trx'] = $trx_id;
            redirect('transaksi/index');
        } catch (Exception $e) {
            $db->rollback();
            $_SESSION['flash'] = 'Transaksi gagal: ' . $e->getMessage();
            redirect('transaksi/kasir');
        }
    }

    public function batal() {
        if (!is_logged_in()) redirect('auth/login');
        allow_roles(['owner', 'admin']);

        $id = (int)($_GET['id'] ?? 0);
        $db = getDB();

        $stmt = $db->prepare("SELECT * FROM transaksi WHERE id = ? AND status = 'Selesai'");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $trx = $stmt->get_result()->fetch_assoc();

        if (!$trx) redirect('transaksi/index');

        $details = $db->query(
            "SELECT produk_id, qty FROM detail_transaksi WHERE id_trx = $id"
        )->fetch_all(MYSQLI_ASSOC);

        $db->begin_transaction();
        try {
            $stmt = $db->prepare("UPDATE transaksi SET status='Batal' WHERE id=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();

            // Kembalikan stok
            foreach ($details as $d) {
                $stmt2 = $db->prepare("UPDATE product SET stock = stock + ? WHERE id = ?");
                $stmt2->bind_param('ii', $d['qty'], $d['produk_id']);
                $stmt2->execute();
            }

            $db->commit();
            $_SESSION['flash'] = 'Transaksi berhasil dibatalkan dan stok dikembalikan.';
        } catch (Exception $e) {
            $db->rollback();
            $_SESSION['flash'] = 'Gagal membatalkan transaksi: ' . $e->getMessage();
        }

        redirect('transaksi/index');
    }

    public function detail() {
        if (!is_logged_in()) redirect('auth/login');

        $id = (int)($_GET['id'] ?? 0);
        $db = getDB();

        $stmt = $db->prepare(
            "SELECT t.*, u.nama AS kasir
             FROM transaksi t JOIN users u ON t.user_id = u.user_id
             WHERE t.id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $transaksi = $stmt->get_result()->fetch_assoc();

        if (!$transaksi) redirect('transaksi/index');

        $stmt2 = $db->prepare(
            "SELECT dt.*, p.nama_barang, p.kode_barang, p.satuan
             FROM detail_transaksi dt JOIN product p ON dt.produk_id = p.id
             WHERE dt.id_trx = ?"
        );
        $stmt2->bind_param('i', $id);
        $stmt2->execute();
        $details = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

        $pageTitle = 'Detail Transaksi';
        require_once __DIR__ . '/../view/transaksi/detail.php';
    }
}