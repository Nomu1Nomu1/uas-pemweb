<?php
require_once __DIR__ . '/../config/db.php';

class ProductController
{

    public function index()
    {
        if (!is_logged_in())
            redirect('auth/login');

        $db = getDB();
        $search = trim($_GET['search'] ?? '');
        $katId = (int) ($_GET['kategori_id'] ?? 0);

        $sql = "SELECT p.*, k.nama_kategori, d.nama_distributor FROM product p JOIN kategori_product k ON p.kategori_id  = k.id JOIN distributorsd ON p.distributor_id = d.id WHERE 1=1";
        $params = [];
        $types = '';

        if ($search !== '') {
            $like = "%$search%";
            $sql .= " AND (p.nama_barang LIKE ? OR p.kode_barang LIKE ?)";
            $params[] = $like;
            $params[] = $like;
            $types .= 'ss';
        }

        if ($katId > 0) {
            $sql .= " AND p.kategori_id = ?";
            $params[] = $katId;
            $types .= 'i';
        }

        $sql .= " ORDER BY p.id DESC";

        if ($params) {
            $stmt = $db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $products = $db->query($sql)->fetch_all(MYSQLI_ASSOC);
        }

        $kategoris = $db->query("SELECT * FROM kategori_product ORDER BY nama_kategori")->fetch_all(MYSQLI_ASSOC);

        $pageTitle = 'Data Produk';
        require_once __DIR__ . '/../view/produk/index.php';
    }

    public function show()
    {
        if (!is_logged_in())
            redirect('auth/login');

        $id = (int) ($_GET['id'] ?? 0);
        $db = getDB();

        $stmt = $db->prepare(
            "SELECT p.*, k.nama_kategori, d.nama_distributor FROM product p JOIN kategori_product k ON p.kategori_id = k.id JOIN distributors d ON p.distributor_id = d.id WHERE p.id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();

        if (!$product) redirect('product/index');

        $pageTitle = 'Detail Produk';
        require_once __DIR__ . '/../view/produk/show.php';
    }

    public function create()
    {
        if (!is_logged_in()) redirect('auth/login');
        allow_roles(['owner', 'admin']);

        $db = getDB();
        $kategoris = $db->query("SELECT * FROM kategori_product ORDER BY nama_kategori")->fetch_all(MYSQLI_ASSOC);
        $distribs = $db->query("SELECT * FROM distributors ORDER BY nama_distributor")->fetch_all(MYSQLI_ASSOC);
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $kode = trim($_POST['kode_barang'] ?? '');
            $nama = trim($_POST['nama_barang'] ?? '');
            $kategori_id = (int) ($_POST['kategori_id'] ?? 0);
            $dist_id = (int) ($_POST['distributor_id'] ?? 0);
            $stock = (int) ($_POST['stock'] ?? 0);
            $stock_min = (int) ($_POST['stock_min'] ?? 0);
            $harga_beli = (float) ($_POST['harga_beli'] ?? 0);
            $harga_jual = (float) ($_POST['harga_jual'] ?? 0);
            $satuan = trim($_POST['satuan'] ?? '');
            $deskripsi = trim($_POST['deskripsi'] ?? '') ?: null;

            if (empty($kode) || empty($nama) || !$kategori_id || !$dist_id || empty($satuan)) {
                $error = 'Semua field wajib diisi.';
            } else {
                $now = date('Y-m-d H:i:s');
                $stmt = $db->prepare(
                    "INSERT INTO product (kode_barang, nama_barang, kategori_id, distributor_id, stock, stock_min, harga_beli, harga_jual, satuan, deskripsi, createdAt, updatedAt) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param(
                    'ssiiiiddssss',
                    $kode,
                    $nama,
                    $kategori_id,
                    $dist_id,
                    $stock,
                    $stock_min,
                    $harga_beli,
                    $harga_jual,
                    $satuan,
                    $deskripsi,
                    $now,
                    $now
                );
                if ($stmt->execute()) {
                    redirect('product/index');
                } else {
                    $error = 'Kode barang sudah digunakan.';
                }
            }
        }

        $pageTitle = 'Tambah Produk';
        require_once __DIR__ . '/../view/produk/create.php';
    }

    public function edit()
    {
        if (!is_logged_in()) redirect('auth/login');
        allow_roles(['owner', 'admin']);

        $id = (int) ($_GET['id'] ?? 0);
        $db = getDB();

        $stmt = $db->prepare("SELECT * FROM product WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();

        if (!$product)
            redirect('product/index');

        $kategoris = $db->query("SELECT * FROM kategori_product ORDER BY nama_kategori")->fetch_all(MYSQLI_ASSOC);
        $distribs = $db->query("SELECT * FROM distributors ORDER BY nama_distributor")->fetch_all(MYSQLI_ASSOC);
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $kode = trim($_POST['kode_barang'] ?? '');
            $nama = trim($_POST['nama_barang'] ?? '');
            $kategori_id = (int) ($_POST['kategori_id'] ?? 0);
            $dist_id = (int) ($_POST['distributor_id'] ?? 0);
            $stock = (int) ($_POST['stock'] ?? 0);
            $stock_min = (int) ($_POST['stock_min'] ?? 0);
            $harga_beli = (float) ($_POST['harga_beli'] ?? 0);
            $harga_jual = (float) ($_POST['harga_jual'] ?? 0);
            $satuan = trim($_POST['satuan'] ?? '');
            $deskripsi = trim($_POST['deskripsi'] ?? '') ?: null;

            if (empty($kode) || empty($nama) || !$kategori_id || !$dist_id || empty($satuan)) {
                $error = 'Semua field wajib diisi.';
            } else {
                $now = date('Y-m-d H:i:s');
                $stmt = $db->prepare(
                    "UPDATE product SET kode_barang=?, nama_barang=?, kategori_id=?, distributor_id=?, stock=?, stock_min=?, harga_beli=?, harga_jual=?, satuan=?, deskripsi=?, updatedAt=? WHERE id=?");
                $stmt->bind_param(
                    'ssiiiiddsssi',
                    $kode,
                    $nama,
                    $kategori_id,
                    $dist_id,
                    $stock,
                    $stock_min,
                    $harga_beli,
                    $harga_jual,
                    $satuan,
                    $deskripsi,
                    $now,
                    $id
                );
                if ($stmt->execute()) {
                    redirect('product/index');
                } else {
                    $error = 'Kode barang sudah digunakan oleh produk lain.';
                }
            }
        }

        $pageTitle = 'Edit Produk';
        require_once __DIR__ . '/../view/produk/edit.php';
    }

    public function delete()
    {
        if (!is_logged_in()) redirect('auth/login');
        allow_roles(['owner', 'admin']);

        $id = (int) ($_GET['id'] ?? 0);
        $db = getDB();

        $used = $db->query("SELECT COUNT(*) AS c FROM detail_transaksi WHERE produk_id = $id")->fetch_assoc()['c'];

        if ($used > 0) {
            $_SESSION['flash'] = 'Produk tidak dapat dihapus karena sudah ada di transaksi.';
        } else {
            $stmt = $db->prepare("DELETE FROM product WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $_SESSION['flash'] = 'Produk berhasil dihapus.';
        }

        redirect('product/index');
    }
}