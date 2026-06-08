<?php
require_once __DIR__ . '/../config/db.php';

class Transaksi
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function getAll(string $search = '', string $tanggal = ''): array
    {
        $sql = "SELECT t.*, u.nama AS kasir
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
            "SELECT t.*, u.nama AS kasir
             FROM transaksi t
             JOIN users u ON t.user_id = u.user_id
             WHERE t.id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result ?: null;
    }

    public function getDetail(int $trx_id): array
    {
        $stmt = $this->db->prepare(
            "SELECT dt.*, p.nama_barang, p.kode_barang, p.satuan
             FROM detail_transaksi dt
             JOIN product p ON dt.produk_id = p.id
             WHERE dt.id_trx = ?"
        );
        $stmt->bind_param('i', $trx_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function create(
        int    $user_id,
        array  $items,
        float  $bayar,
        ?string $keterangan = null,
        string &$errorMsg   = ''
    ): int|false {
        if (empty($items)) {
            $errorMsg = 'Tidak ada item yang dipilih.';
            return false;
        }

        $total_harga = 0;

        foreach ($items as $item) {
            $produk_id = (int)   ($item['produk_id']    ?? 0);
            $qty       = (int)   ($item['qty']          ?? 0);

            if ($produk_id <= 0 || $qty <= 0) continue;

            $stmt = $this->db->prepare(
                "SELECT nama_barang, stock, harga_jual FROM product WHERE id = ?"
            );
            $stmt->bind_param('i', $produk_id);
            $stmt->execute();
            $prod = $stmt->get_result()->fetch_assoc();

            if (!$prod || (int) $prod['stock'] < $qty) {
                $nama      = $prod['nama_barang'] ?? "ID $produk_id";
                $errorMsg  = "Stok produk \"$nama\" tidak mencukupi.";
                return false;
            }

            $harga        = (float) ($item['harga_satuan'] ?? $prod['harga_jual']);
            $total_harga += $qty * $harga;
        }

        $kembalian   = $bayar - $total_harga;
        $now         = date('Y-m-d H:i:s');
        $no_trx      = 'TRX-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO transaksi
                    (no_trx, user_id, tanggal_transaksi, total_harga, bayar, kembalian, status, keterangan)
                 VALUES (?, ?, ?, ?, ?, ?, 'Selesai', ?)"
            );
            $stmt->bind_param(
                'sisddd s',
                $no_trx, $user_id, $now, $total_harga, $bayar, $kembalian, $keterangan
            );
            $stmt->execute();
            $trx_id = $this->db->insert_id;

            foreach ($items as $item) {
                $produk_id = (int)   ($item['produk_id']    ?? 0);
                $qty       = (int)   ($item['qty']          ?? 0);
                $harga_sat = (float) ($item['harga_satuan'] ?? 0);
                $subtotal  = $qty * $harga_sat;

                if ($produk_id <= 0 || $qty <= 0) continue;

                $stmt2 = $this->db->prepare(
                    "INSERT INTO detail_transaksi (id_trx, produk_id, qty, harga_satuan, subtotal)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt2->bind_param('iiidd', $trx_id, $produk_id, $qty, $harga_sat, $subtotal);
                $stmt2->execute();

                $stmt3 = $this->db->prepare(
                    "UPDATE product SET stock = stock - ?, updatedAt=? WHERE id=?"
                );
                $stmt3->bind_param('isi', $qty, $now, $produk_id);
                $stmt3->execute();
            }

            $this->db->commit();
            return $trx_id;
        } catch (Exception $e) {
            $this->db->rollback();
            $errorMsg = 'Terjadi kesalahan: ' . $e->getMessage();
            return false;
        }
    }

    public function getLaporanHarian(string $dari, string $sampai): array
    {
        $stmt = $this->db->prepare(
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
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getProdukTerlaris(string $dari, string $sampai): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.kode_barang, p.nama_barang, k.nama_kategori,
                    SUM(dt.qty) AS total_terjual,
                    SUM(dt.subtotal) AS total_pendapatan
             FROM detail_transaksi dt
             JOIN transaksi        t  ON dt.id_trx      = t.id
             JOIN product          p  ON dt.produk_id   = p.id
             JOIN kategori_product k  ON p.kategori_id  = k.id
             WHERE t.status = 'Selesai'
               AND DATE(t.tanggal_transaksi) BETWEEN ? AND ?
             GROUP BY dt.produk_id
             ORDER BY total_terjual DESC
             LIMIT 10"
        );
        $stmt->bind_param('ss', $dari, $sampai);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getSummary(string $dari, string $sampai): array
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total_trx, COALESCE(SUM(total_harga), 0) AS grand_total
             FROM transaksi
             WHERE status = 'Selesai'
               AND DATE(tanggal_transaksi) BETWEEN ? AND ?"
        );
        $stmt->bind_param('ss', $dari, $sampai);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}