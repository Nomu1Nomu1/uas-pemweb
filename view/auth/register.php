<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../model/User.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (is_logged_in()) {
    header('Location: ../dashboard/index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '') ?: null;
    $no_hp = trim($_POST['no_hp'] ?? '') ?: null;
    $role = trim($_POST['role'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    $userModel = new User();

    if (empty($nama) || empty($username) || empty($role) || empty($password)) {
        $error = 'Nama, username, peran, dan password wajib diisi.';

    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Username hanya boleh huruf, angka, dan underscore (tanpa spasi).';

    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';

    } elseif ($password !== $password_confirm) {
        $error = 'Konfirmasi password tidak cocok.';

    } elseif (!in_array($role, ['owner', 'admin', 'kasir'])) {
        $error = 'Peran tidak valid.';

    } elseif ($userModel->usernameExists($username)) {
        $error = 'Username sudah digunakan, pilih yang lain.';

    } elseif ($email && $userModel->emailExists($email)) {
        $error = 'Email sudah terdaftar.';

    } else {
        if ($userModel->create($username, $password, $nama, $role, $email, $no_hp)) {

            $_SESSION['success'] = 'Akun berhasil dibuat! Silakan login.';

            header('Location: login.php');
            exit;

        } else {
            $error = 'Gagal menyimpan data. Coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Register - UMKM Inventory</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../../assets/css/auth.css">
</head>

<body class="auth-page">

    <div class="container">

        <div class="row justify-content-center align-items-center min-vh-100">

            <div class="col-lg-11">

                <div class="card auth-card">

                    <div class="row g-0">

                        <div class="col-lg-7 auth-left">

                            <div class="auth-logo mb-4">
                                <i class="bi bi-person-plus"></i>
                            </div>

                            <h2 class="auth-title">
                                Buat Akun Baru
                            </h2>

                            <p class="auth-subtitle">
                                Lengkapi data berikut untuk mendaftar.
                            </p>

                            <form method="POST">

                                <div class="row">

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nama</label>
                                        <input type="text" name="nama" class="form-control" required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Username</label>
                                        <input type="text" name="username" class="form-control" required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">No HP</label>
                                        <input type="tel" name="no_hp" class="form-control">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Role</label>
                                        <select name="role" class="form-select" required>

                                            <option value="">Pilih Role</option>
                                            <option value="admin">Admin</option>
                                            <option value="owner">Owner</option>
                                            <option value="kasir">Kasir</option>

                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Password</label>
                                        <input type="password" name="password" class="form-control" required>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">
                                            Konfirmasi Password
                                        </label>

                                        <input type="password" name="password_confirm" class="form-control" required>
                                    </div>

                                </div>

                                <button class="btn btn-primary btn-auth w-100">
                                    <i class="bi bi-person-check me-2"></i>
                                    Daftar Sekarang
                                </button>

                            </form>

                            <div class="auth-link">
                                Sudah punya akun?
                                <a href="login.php">
                                    Login
                                </a>
                            </div>

                        </div>

                        <div class="col-lg-5 auth-right d-flex flex-column justify-content-center">

                            <h2 class="fw-bold mb-3">
                                UMKM Inventory
                            </h2>

                            <p class="mb-4">
                                Sistem manajemen inventaris modern untuk UMKM.
                            </p>

                            <div class="feature-item">
                                <i class="bi bi-check-circle"></i>
                                <span>Kelola produk</span>
                            </div>

                            <div class="feature-item">
                                <i class="bi bi-check-circle"></i>
                                <span>Kelola transaksi</span>
                            </div>

                            <div class="feature-item">
                                <i class="bi bi-check-circle"></i>
                                <span>Kelola laporan</span>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</body>

</html>