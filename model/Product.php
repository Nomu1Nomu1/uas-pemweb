<?php
require_once __DIR__ . '/../config/db.php';

class Product
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function getAll(string $search = '', int $kategori_id = 0): array
    {
        $sql = "SELECT p.*, k.nama_kategori, d.nama_distributor
                FROM product p
                JOIN kategori_product k ON p.kategori_id   = k.id
                JOIN distributors     d ON p.distributor_id = d.id
                WHERE 1=1";
        $params = [];
        $types  = '';

        if ($search !== '') {
            $like    = "%$search%";
            $sql    .= " AND (p.nama_barang LIKE ? OR p.kode_barang LIKE ?)";
            $params[] = $like;
            $params[] = $like;
            $types   .= 'ss';
        }

        if ($kategori_id > 0) {
            $sql    .= " AND p.kategori_id = ?";
            $params[] = $kategori_id;
            $types   .= 'i';
        }

        $sql .= " ORDER BY p.id DESC";

        if ($params) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, k.nama_kategori, d.nama_distributor
             FROM product p
             JOIN kategori_product k ON p.kategori_id   = k.id
             JOIN distributors     d ON p.distributor_id = d.id
             WHERE p.id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result ?: null;
    }


    public function getForSelect(bool $onlyInStock = false): array
    {
        $where = $onlyInStock ? "WHERE stock > 0" : "";
        return $this->db->query(
            "SELECT id, kode_barang, nama_barang, harga_jual, harga_beli, stock, satuan
             FROM product $where ORDER BY nama_barang"
        )->fetch_all(MYSQLI_ASSOC);
    }

    public function getStokMenipis(): array
    {
        return $this->db->query(
            "SELECT p.kode_barang, p.nama_barang, p.stock, p.stock_min,
                    p.satuan, k.nama_kategori, d.nama_distributor, d.no_hp AS dist_no_hp
             FROM product p
             JOIN kategori_product k ON p.kategori_id   = k.id
             JOIN distributors     d ON p.distributor_id = d.id
             WHERE p.stock <= p.stock_min
             ORDER BY p.stock ASC"
        )->fetch_all(MYSQLI_ASSOC);
    }

    public function create(
        string  $kode_barang,
        string  $nama_barang,
        int     $kategori_id,
        int     $distributor_id,
        int     $stock,
        int     $stock_min,
        float   $harga_beli,
        float   $harga_jual,
        string  $satuan,
        ?string $deskripsi = null
    ): bool {
        $now  = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare(
            "INSERT INTO product
                (kode_barang, nama_barang, kategori_id, distributor_id,
                 stock, stock_min, harga_beli, harga_jual, satuan, deskripsi, createdAt, updatedAt)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            'ssiiiiddss ss',
            $kode_barang, $nama_barang, $kategori_id, $distributor_id,
            $stock, $stock_min, $harga_beli, $harga_jual, $satuan, $deskripsi, $now, $now
        );
        return $stmt->execute();
    }

    public function update(
        int     $id,
        string  $kode_barang,
        string  $nama_barang,
        int     $kategori_id,
        int     $distributor_id,
        int     $stock,
        int     $stock_min,
        float   $harga_beli,
        float   $harga_jual,
        string  $satuan,
        ?string $deskripsi = null
    ): bool {
        $now  = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare(
            "UPDATE product
             SET kode_barang=?, nama_barang=?, kategori_id=?, distributor_id=?,
                 stock=?, stock_min=?, harga_beli=?, harga_jual=?, satuan=?, deskripsi=?, updatedAt=?
             WHERE id=?"
        );
        $stmt->bind_param(
            'ssiiiiddssi',
            $kode_barang, $nama_barang, $kategori_id, $distributor_id,
            $stock, $stock_min, $harga_beli, $harga_jual, $satuan, $deskripsi, $now, $id
        );
        return $stmt->execute();
    }

    public function tambahStok(int $id, int $qty): bool
    {
        $now  = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare(
            "UPDATE product SET stock = stock + ?, updatedAt=? WHERE id=?"
        );
        $stmt->bind_param('isi', $qty, $now, $id);
        return $stmt->execute();
    }

    public function kurangiStok(int $id, int $qty): bool
    {
        $now  = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare(
            "UPDATE product SET stock = stock - ?, updatedAt=? WHERE id=?"
        );
        $stmt->bind_param('isi', $qty, $now, $id);
        return $stmt->execute();
    }

    public function cekStok(int $id, int $qty): bool
    {
        $stmt = $this->db->prepare("SELECT stock FROM product WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row && (int) $row['stock'] >= $qty;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM product WHERE id = ?");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }
}