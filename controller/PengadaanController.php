<?php
require_once __DIR__ . '/../config/db.php';

class PengadaanController
{
    public function index()
    {
        if (!is_logged_in()) {
            redirect('auth/login');
        }

        $db = getDB();
        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');

        $sql = "SELECT pg.*, d.nama_distributor, u.nama AS user_nama FROM pengadaan pg JOIN distributors d ON pg.distributor_id = d.id JOIN users u ON pg.user_id = u.user_id WHERE 1=1";
        $params = [];
        $types = '';

        if ($search !== '') {
            $like = "%$search%";
            $sql .= " AND (pg.no_pengadaan LIKE ? OR d.nama_distributor LIKE ?)";
            $params[] = $like;
            $params[] = $like;
            $types .= 'ss';
        }

        if (in_array($status, ['Pending', 'Diterima', 'Dibatalkan'])) {
            $sql .= " AND pg.status = ?";
            $params = $status;
            $types .= 's';
        }

        $sql .= " ORDER BY pg.id DESC";

        if ($params) {
            $stmt = $db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $pengadaans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $pengadaans = $db->query($sql)->fetch_all(MYSQLI_ASSOC);
        }

        $pageTitle = 'Data Pengadaan';
        require_once __DIR__ . '/../view/pengadaan/index.php';
    }

    public function create()
    {
        if (!is_logged_in()) {
            redirect('auth/login');
        }
        allow_roles(['owner', 'admin']);

        $db = getDB();
        $distribs = $db->query("SELECT * FROM distributors ORDER BY nama_distributor")->fetch_all(MYSQLI_ASSOC);
        $products = $db->query("SELECT id, kode_barang, nama_barang, harga_beli, satuan FROM product ORDER BY nama_barang")->fetch_all(MYSQLI_ASSOC);
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $distributor_id = (int) ($_POST['distributor_id'] ?? 0);
            $keterangan = trim($_POST['keterangan'] ?? '') ?: null;
            $items = $_POST['items'] ?? [];

            if (!$distributor_id || empty($items)) {
                $error = 'Distributor dan minimal 1 item wajib diisi.';
            } else {
                $user_id = $_SESSION['users']['id'];
                $now = date('Y-m-d H:i:s');
                $no_pengadaan = 'PGD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
                $total_harga = 0;

                foreach ($items as $item) {
                    $qty = (int) ($item['qty'] ?? 0);
                    $harga = (float) ($item['harga_satuan'] ?? 0);
                    $total_harga += $qty * $harga;
                }

                $db->begin_transaction();
                try {
                    $stmt = $db->prepare(
                        "INSERT INTO pengadaan(no_pengadaan, distributor_id, user_id, tanggal_pengadaan, total_harga, status, keterangan, createdAt, updatedAt) VALUES (?, ?, ?, ?, ?, 'Pending', ?, ?, ?)"
                    );
                    $stmt->bind_param('siisdss', $no_pengadaan, $distributor_id, $user_id, $now, $total_harga, $keterangan, $now, $now);
                    $stmt->execute();
                    $pengadaan_id = $db->insert_id;

                    foreach ($items as $item) {
                        $produk_id = (int) ($item['produk_id'] ?? 0);
                        $qty = (int) ($item['qty'] ?? 0);
                        $harga_sat = (float) ($item['harga_satuan'] ?? 0);
                        $subtotal = $qty * $harga_sat;

                        if ($produk_id <= 0 || $qty <= 0)
                            continue;

                        $stmt2 = $db->prepare(
                            "INSERT INTO detail_pengadaan (id_pengadaan, produk_id, qty, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)"
                        );
                        $stmt2->bind_param('iiidd', $pengadaan_id, $produk_id, $qty, $harga_sat, $subtotal);
                        $stmt2->execute();
                    }

                    $db->commit();
                    redirect('pengadaan/index');
                } catch (Exception $e) {
                    $db->rollback();
                    $error = 'Terjadi kesalahan: ' . $e->getMessage();
                }
            }
        }

        $pageTitle = 'Buat Pengadaan';
        require_once __DIR__ . '/../view/pengadaan/create.php';
    }

    public function detail()
    {
        if (!is_logged_in())
            redirect('auth/login');

        $id = (int) ($_GET['id'] ?? 0);
        $db = getDB();

        $stmt = $db->prepare(
            "SELECT pg.*, d.nama_distributor, d.no_hp AS dist_no_hp, u.nama AS user_nama FROM pengadaan pg JOIN distributors d ON pg.distributor_id = d.id JOIN users        u ON pg.user_id        = u.user_id WHERE pg.id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $pengadaan = $stmt->get_result()->fetch_assoc();

        if (!$pengadaan)
            redirect('pengadaan/index');

        $stmt2 = $db->prepare(
            "SELECT dp.*, p.nama_barang, p.kode_barang, p.satuan FROM detail_pengadaan dp JOIN product p ON dp.produk_id = p.id WHERE dp.id_pengadaan = ?"
        );
        $stmt2->bind_param('i', $id);
        $stmt2->execute();
        $details = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

        $pageTitle = 'Detail Pengadaan';
        require_once __DIR__ . '/../view/pengadaan/detail.php';
    }

    public function terima()
    {
        if (!is_logged_in())
            redirect('auth/login');
        allow_roles(['owner', 'admin']);

        $id = (int) ($_GET['id'] ?? 0);
        $db = getDB();

        $stmt = $db->prepare("SELECT * FROM pengadaan WHERE id = ? AND status = 'Pending'");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $pengadaan = $stmt->get_result()->fetch_assoc();

        if (!$pengadaan)
            redirect('pengadaan/index');

        $details = $db->query("SELECT produk_id, qty FROM detail_pengadaan WHERE id_pengadaan = $id")->fetch_all(MYSQLI_ASSOC);

        $db->begin_transaction();
        try {
            $now = date('Y-m-d H:i:s');
            $stmt = $db->prepare("UPDATE pengadaan SET status='Diterima', updatedAt=? WHERE id=?");
            $stmt->bind_param('si', $now, $id);
            $stmt->execute();

            foreach ($details as $d) {
                $stmt2 = $db->prepare("UPDATE product SET stock = stock + ? WHERE id = ?");
                $stmt2->bind_param('ii', $d['qty'], $d['produk_id']);
                $stmt2->execute();
            }

            $db->commit();
            $_SESSION['flash'] = 'Pengadaan diterima dan stok telah diperbarui.';
        } catch (Exception $e) {
            $db->rollback();
            $_SESSION['flash'] = 'Gagal memproses pengadaan: ' . $e->getMessage();
        }

        redirect('pengadaan/index');
    }

    public function batal()
    {
        if (!is_logged_in())
            redirect('auth/login');
        allow_roles(['owner', 'admin']);

        $id = (int) ($_GET['id'] ?? 0);
        $db = getDB();

        $stmt = $db->prepare("SELECT * FROM pengadaan WHERE id = ? AND status = 'Pending'");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $pengadaan = $stmt->get_result()->fetch_assoc();

        if (!$pengadaan)
            redirect('pengadaan/index');

        $now = date('Y-m-d H:i:s');
        $stmt = $db->prepare("UPDATE pengadaan SET status='Dibatalkan', updatedAt=? WHERE id=?");
        $stmt->bind_param('si', $now, $id);
        $stmt->execute();

        $_SESSION['flash'] = 'Pengadaan berhasil dibatalkan.';
        redirect('pengadaan/index');
    }
}