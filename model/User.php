<?php
require_once __DIR__ . '/../config/db.php';

class User
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    /* ─── READ ─────────────────────────────────────── */

    public function getAll(): array
    {
        return $this->db->query(
            "SELECT user_id, username, nama, role, email, no_hp, is_active, createdAt
             FROM users ORDER BY user_id DESC"
        )->fetch_all(MYSQLI_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE username = ? AND is_active = 'Y' LIMIT 1"
        );
        $stmt->bind_param('s', $username);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function usernameExists(string $username): bool
    {
        $stmt = $this->db->prepare("SELECT user_id FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    /* ─── CREATE / REGISTER ────────────────────────── */

    public function create(
        string  $username,
        string  $password,
        string  $nama,
        string  $role,
        ?string $email = null,
        ?string $no_hp = null
    ): bool {
        $hash = password_hash($password, PASSWORD_DEFAULT); // bcrypt
        $now  = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            "INSERT INTO users
                (username, password, nama, role, email, no_hp, is_active, createdAt, updatedAt)
             VALUES (?, ?, ?, ?, ?, ?, 'Y', ?, ?)"
        );
        $stmt->bind_param('ssssssss',
            $username, $hash, $nama, $role, $email, $no_hp, $now, $now
        );
        return $stmt->execute();
    }

    /* ─── UPDATE ────────────────────────────────────── */

    public function update(
        int     $id,
        string  $nama,
        string  $role,
        ?string $email    = null,
        ?string $no_hp    = null,
        ?string $password = null
    ): bool {
        $now = date('Y-m-d H:i:s');

        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare(
                "UPDATE users SET nama=?, role=?, email=?, no_hp=?, password=?, updatedAt=?
                 WHERE user_id=?"
            );
            $stmt->bind_param('ssssssi', $nama, $role, $email, $no_hp, $hash, $now, $id);
        } else {
            $stmt = $this->db->prepare(
                "UPDATE users SET nama=?, role=?, email=?, no_hp=?, updatedAt=?
                 WHERE user_id=?"
            );
            $stmt->bind_param('sssssi', $nama, $role, $email, $no_hp, $now, $id);
        }
        return $stmt->execute();
    }

    /* ─── TOGGLE AKTIF ──────────────────────────────── */

    public function toggleAktif(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT is_active FROM users WHERE user_id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if (!$user) return false;

        $newStatus = $user['is_active'] === 'Y' ? 'N' : 'Y';
        $now = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare("UPDATE users SET is_active=?, updatedAt=? WHERE user_id=?");
        $stmt->bind_param('ssi', $newStatus, $now, $id);
        return $stmt->execute();
    }

    /* ─── DELETE ─────────────────────────────────────── */

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE user_id=?");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    /* ─── HELPER ─────────────────────────────────────── */

    /** Verifikasi password saat login */
    public function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }
}
