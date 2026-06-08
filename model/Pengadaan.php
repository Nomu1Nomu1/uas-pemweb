<?php
require_once __DIR__ . '/../config/db.php';

class Pengadaan
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function getAll(string $search = '', string $status = ''): array
    {
        $sql = "SELECT pg.*, d.nama_distributor, u.nama AS user_nama
                FROM pengadaan pg
                JOIN distributors d ON pg.distributor_id = d.id
                JOIN users        u ON pg.user_id         = u.user_id
                WHERE 1=1";
        $params = [];
        $types  = '';

        if ($search !== '') {
            $like    = "%$search%";
            $sql    .= " AND (pg.no_pengadaan LIKE ? OR d.nama_distributor LIKE ?)";
            $params[] = $like;
            $params[] = $like;
            $types   .= 'ss';
        }

        if (in_array($status, ['Pending', 'Diterima', 'Dibatalkan'])) {
            $sql    .= " AND pg.status = ?";
            $params[] = $status;
            $types   .= 's';
        }

        $sql .= " ORDER BY pg.id DESC";

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
            "SELECT pg.*, d.nama_distributor, d.no_hp AS dist_no_hp, u.nama AS user_nama
             FROM pengadaan pg
             JOIN distributors d ON pg.distributor_id = d.id
             JOIN users        u ON pg.user_id         = u.user_id
             WHERE pg.id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result ?: null;
    }

    public function getDetail(int $pengadaan_id): array
    {
        $stmt = $this->db->prepare(
            "SELECT dp.*, p.nama_barang, p.kode_barang, p.satuan
             FROM detail_pengadaan dp
             JOIN product p ON dp.produk_id = p.id
             WHERE dp.id_pengadaan = ?"
        );
        $stmt->bind_param('i', $pengadaan_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function create(int $distributor_id, int $user_id, array $items, ?string $keterangan = null): int|false
    {
        if (empty($items)) return false;

        $now           = date('Y-m-d H:i:s');
        $no_pengadaan  = 'PGD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
        $total_harga   = 0;

        foreach ($items as $item) {
            $qty   = (int)   ($item['qty']          ?? 0);
            $harga = (float) ($item['harga_satuan'] ?? 0);
            $total_harga += $qty * $harga;
        }

        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO pengadaan
                    (no_pengadaan, distributor_id, user_id, tanggal_pengadaan,
                     total_harga, status, keterangan, createdAt, updatedAt)
                 VALUES (?, ?, ?, ?, ?, 'Pending', ?, ?, ?)"
            );
            $stmt->bind_param(
                'siisdss s',
                $no_pengadaan, $distributor_id, $user_id, $now,
                $total_harga, $keterangan, $now, $now
            );
            $stmt->execute();
            $pengadaan_id = $this->db->insert_id;

            foreach ($items as $item) {
                $produk_id  = (int)   ($item['produk_id']    ?? 0);
                $qty        = (int)   ($item['qty']          ?? 0);
                $harga_sat  = (float) ($item['harga_satuan'] ?? 0);
                $subtotal   = $qty * $harga_sat;

                if ($produk_id <= 0 || $qty <= 0) continue;

                $stmt2 = $this->db->prepare(
                    "INSERT INTO detail_pengadaan (id_pengadaan, produk_id, qty, harga_satuan, subtotal)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt2->bind_param('iiidd', $pengadaan_id, $produk_id, $qty, $harga_sat, $subtotal);
                $stmt2->execute();
            }

            $this->db->commit();
            return $pengadaan_id;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    public function terima(int $id): bool
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM pengadaan WHERE id = ? AND status = 'Pending'"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $pengadaan = $stmt->get_result()->fetch_assoc();

        if (!$pengadaan) return false;

        $details = $this->db->query(
            "SELECT produk_id, qty FROM detail_pengadaan WHERE id_pengadaan = $id"
        )->fetch_all(MYSQLI_ASSOC);

        $this->db->begin_transaction();
        try {
            $now  = date('Y-m-d H:i:s');
            $stmt = $this->db->prepare(
                "UPDATE pengadaan SET status='Diterima', updatedAt=? WHERE id=?"
            );
            $stmt->bind_param('si', $now, $id);
            $stmt->execute();

            foreach ($details as $d) {
                $stmt2 = $this->db->prepare(
                    "UPDATE product SET stock = stock + ?, updatedAt=? WHERE id=?"
                );
                $stmt2->bind_param('isi', $d['qty'], $now, $d['produk_id']);
                $stmt2->execute();
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    public function batal(int $id): bool
    {
        $stmt = $this->db->prepare(
            "SELECT id FROM pengadaan WHERE id = ? AND status = 'Pending'"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();

        if (!$stmt->get_result()->fetch_assoc()) return false;

        $now  = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare(
            "UPDATE pengadaan SET status='Dibatalkan', updatedAt=? WHERE id=?"
        );
        $stmt->bind_param('si', $now, $id);
        return $stmt->execute();
    }
}