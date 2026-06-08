<?php
require_once __DIR__ . '/../config/db.php';

class Distributor
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function getAll(string $search = ''): array
    {
        if ($search !== '') {
            $like = "%$search%";
            $stmt = $this->db->prepare(
                "SELECT * FROM distributors
                 WHERE nama_distributor LIKE ? OR alamat LIKE ? OR no_hp LIKE ?
                 ORDER BY id DESC"
            );
            $stmt->bind_param('sss', $like, $like, $like);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        return $this->db->query(
            "SELECT * FROM distributors ORDER BY id DESC"
        )->fetch_all(MYSQLI_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM distributors WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result ?: null;
    }

    public function create(
        string  $nama_distributor,
        string  $alamat,
        string  $no_hp,
        ?string $email      = null,
        ?string $keterangan = null
    ): bool {
        $now  = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare(
            "INSERT INTO distributors (nama_distributor, alamat, no_hp, email, keterangan, createdAt, updatedAt)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            'sssssss',
            $nama_distributor, $alamat, $no_hp, $email, $keterangan, $now, $now
        );
        return $stmt->execute();
    }

    public function update(
        int     $id,
        string  $nama_distributor,
        string  $alamat,
        string  $no_hp,
        ?string $email      = null,
        ?string $keterangan = null
    ): bool {
        $now  = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare(
            "UPDATE distributors
             SET nama_distributor=?, alamat=?, no_hp=?, email=?, keterangan=?, updatedAt=?
             WHERE id=?"
        );
        $stmt->bind_param(
            'ssssssi',
            $nama_distributor, $alamat, $no_hp, $email, $keterangan, $now, $id
        );
        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM distributors WHERE id = ?");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }
}