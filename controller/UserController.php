<?php
require_once __DIR__ . '/../config/db.php';

class UserController {

    public function index() {
        if (!is_logged_in()) redirect('auth/login');
        allow_roles(['owner']);

        $db    = getDB();
        $users = $db->query(
            "SELECT user_id, username, nama, role, email, no_hp, is_active, createdAt
             FROM users ORDER BY user_id DESC"
        )->fetch_all(MYSQLI_ASSOC);

        $pageTitle = 'Manajemen User';
        require_once __DIR__ . '/../view/user/index.php';
    }

    public function create() {
        if (!is_logged_in()) redirect('auth/login');
        allow_roles(['owner']);

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username  = trim($_POST['username'] ?? '');
            $password  = $_POST['password']  ?? '';
            $nama      = trim($_POST['nama']  ?? '');
            $role      = $_POST['role']       ?? '';
            $email     = trim($_POST['email'] ?? '') ?: null;
            $no_hp     = trim($_POST['no_hp'] ?? '') ?: null;

            $valid_roles = ['owner', 'admin', 'kasir'];

            if (empty($username) || empty($password) || empty($nama) || !in_array($role, $valid_roles)) {
                $error = 'Semua field wajib diisi dengan benar.';
            } elseif (strlen($password) < 6) {
                $error = 'Password minimal 6 karakter.';
            } else {
                $db   = getDB();
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $now  = date('Y-m-d H:i:s');

                $stmt = $db->prepare(
                    "INSERT INTO users (username, password, nama, role, email, no_hp, is_active, createdAt, updatedAt)
                     VALUES (?, ?, ?, ?, ?, ?, 'Y', ?, ?)"
                );
                $stmt->bind_param('ssssssss', $username, $hash, $nama, $role, $email, $no_hp, $now, $now);

                if ($stmt->execute()) {
                    redirect('user/index');
                } else {
                    $error = 'Username atau email sudah terdaftar.';
                }
            }
        }

        $pageTitle = 'Tambah User';
        require_once __DIR__ . '/../view/user/create.php';
    }

    public function edit() {
        if (!is_logged_in()) redirect('auth/login');
        allow_roles(['owner']);

        $id = (int)($_GET['id'] ?? 0);
        $db = getDB();

        $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user) redirect('user/index');

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nama    = trim($_POST['nama']  ?? '');
            $role    = $_POST['role']       ?? '';
            $email   = trim($_POST['email'] ?? '') ?: null;
            $no_hp   = trim($_POST['no_hp'] ?? '') ?: null;
            $password = $_POST['password']  ?? '';

            $valid_roles = ['owner', 'admin', 'kasir'];

            if (empty($nama) || !in_array($role, $valid_roles)) {
                $error = 'Nama dan role wajib diisi dengan benar.';
            } else {
                $now = date('Y-m-d H:i:s');

                if (!empty($password)) {
                    if (strlen($password) < 6) {
                        $error = 'Password minimal 6 karakter.';
                    } else {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $db->prepare(
                            "UPDATE users SET nama=?, role=?, email=?, no_hp=?, password=?, updatedAt=? WHERE user_id=?"
                        );
                        $stmt->bind_param('ssssssi', $nama, $role, $email, $no_hp, $hash, $now, $id);
                        $stmt->execute();
                        redirect('user/index');
                    }
                } else {
                    $stmt = $db->prepare(
                        "UPDATE users SET nama=?, role=?, email=?, no_hp=?, updatedAt=? WHERE user_id=?"
                    );
                    $stmt->bind_param('sssssi', $nama, $role, $email, $no_hp, $now, $id);
                    $stmt->execute();
                    redirect('user/index');
                }
            }
        }

        $pageTitle = 'Edit User';
        require_once __DIR__ . '/../view/user/edit.php';
    }

    public function toggleAktif() {
        if (!is_logged_in()) redirect('auth/login');
        allow_roles(['owner']);

        $id = (int)($_GET['id'] ?? 0);

        if ($id === (int)$_SESSION['users']['id']) {
            $_SESSION['flash'] = 'Tidak dapat menonaktifkan akun Anda sendiri.';
            redirect('user/index');
        }

        $db   = getDB();
        $stmt = $db->prepare("SELECT is_active FROM users WHERE user_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user) redirect('user/index');

        $newStatus = $user['is_active'] === 'Y' ? 'N' : 'Y';
        $now       = date('Y-m-d H:i:s');

        $stmt = $db->prepare("UPDATE users SET is_active=?, updatedAt=? WHERE user_id=?");
        $stmt->bind_param('ssi', $newStatus, $now, $id);
        $stmt->execute();

        $_SESSION['flash'] = 'Status user berhasil diperbarui.';
        redirect('user/index');
    }

    public function delete() {
        if (!is_logged_in()) redirect('auth/login');
        allow_roles(['owner']);

        $id = (int)($_GET['id'] ?? 0);

        if ($id === (int)$_SESSION['users']['id']) {
            $_SESSION['flash'] = 'Tidak dapat menghapus akun Anda sendiri.';
            redirect('user/index');
        }

        $db   = getDB();
        $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $_SESSION['flash'] = 'User berhasil dihapus.';
        redirect('user/index');
    }
}