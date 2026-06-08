<?php
require_once __DIR__ . '/../config/db.php';

class KategoriController {

    public function index() {
        if (!is_logged_in()) redirect('auth/login');

        $db        = getDB();
        $kategoris = $db->query(
            "SELECT k.*, COUNT(p.id) AS jumlah_produk
             FROM kategori_product k
             LEFT JOIN product p ON p.kategori_id = k.id
             GROUP BY k.id
             ORDER BY k.nama_kategori"
        )->fetch_all(MYSQLI_ASSOC);

        $pageTitle = 'Kategori Produk';
        require_once __DIR__ . '/../view/kategori/index.php';
    }

    public function create() {
        if (!is_logged_in()) redirect('auth/login');
        allow_roles(['owner', 'admin']);

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nama      = trim($_POST['nama_kategori'] ?? '');
            $deskripsi = trim($_POST['deskripsi']     ?? '') ?: null;

            if (empty($nama)) {
                $error = 'Nama kategori wajib diisi.';
            } else {
                $db   = getDB();
                $stmt = $db->prepare("INSERT INTO kategori_product (nama_kategori, deskripsi) VALUES (?, ?)");
                $stmt->bind_param('ss', $nama, $deskripsi);
                $stmt->execute();
                redirect('kategori/index');
            }
        }

        $pageTitle = 'Tambah Kategori';
        require_once __DIR__ . '/../view/kategori/create.php';
    }

    public function edit() {
        if (!is_logged_in()) redirect('auth/login');
        allow_roles(['owner', 'admin']);

        $id = (int)($_GET['id'] ?? 0);
        $db = getDB();

        $stmt = $db->prepare("SELECT * FROM kategori_product WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $kategori = $stmt->get_result()->fetch_assoc();

        if (!$kategori) redirect('kategori/index');

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nama      = trim($_POST['nama_kategori'] ?? '');
            $deskripsi = trim($_POST['deskripsi']     ?? '') ?: null;

            if (empty($nama)) {
                $error = 'Nama kategori wajib diisi.';
            } else {
                $stmt = $db->prepare("UPDATE kategori_product SET nama_kategori=?, deskripsi=? WHERE id=?");
                $stmt->bind_param('ssi', $nama, $deskripsi, $id);
                $stmt->execute();
                redirect('kategori/index');
            }
        }

        $pageTitle = 'Edit Kategori';
        require_once __DIR__ . '/../view/kategori/edit.php';
    }

    public function delete() {
        if (!is_logged_in()) redirect('auth/login');
        allow_roles(['owner', 'admin']);

        $id   = (int)($_GET['id'] ?? 0);
        $db   = getDB();
        $used = $db->query(
            "SELECT COUNT(*) AS c FROM product WHERE kategori_id = $id"
        )->fetch_assoc()['c'];

        if ($used > 0) {
            $_SESSION['flash'] = 'Kategori tidak dapat dihapus karena masih digunakan oleh produk.';
        } else {
            $stmt = $db->prepare("DELETE FROM kategori_product WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $_SESSION['flash'] = 'Kategori berhasil dihapus.';
        }

        redirect('kategori/index');
    }
}