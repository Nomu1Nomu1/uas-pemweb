<?php
require_once __DIR__ . '/../config/db.php';

class KategoriProduct
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function getAll(): array
    {
        return $this->db->query(
            "SELECT k.*, COUNT(p.id) AS jumlah_produk
             FROM kategori_product k
             LEFT JOIN product p ON p.kategori_id = k.id
             GROUP BY k.id
             ORDER BY k.nama_kategori"
        )->fetch_all(MYSQLI_ASSOC);
    }

    public function getAllSimple(): array
    {
        return $this->db->query(
            "SELECT * FROM kategori_product ORDER BY nama_kategori"
        )->fetch_all(MYSQLI_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM kategori_product WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result ?: null;
    }

    public function create(string $nama_kategori, ?string $deskripsi = null): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO kategori_product (nama_kategori, deskripsi) VALUES (?, ?)"
        );
        $stmt->bind_param('ss', $nama_kategori, $deskripsi);
        return $stmt->execute();
    }

    public function update(int $id, string $nama_kategori, ?string $deskripsi = null): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE kategori_product SET nama_kategori=?, deskripsi=? WHERE id=?"
        );
        $stmt->bind_param('ssi', $nama_kategori, $deskripsi, $id);
        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $used = (int) $this->db->query(
            "SELECT COUNT(*) AS c FROM product WHERE kategori_id = $id"
        )->fetch_assoc()['c'];

        if ($used > 0) return false;

        $stmt = $this->db->prepare("DELETE FROM kategori_product WHERE id = ?");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    public function isUsed(int $id): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS c FROM product WHERE kategori_id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return (int) $stmt->get_result()->fetch_assoc()['c'] > 0;
    }
}