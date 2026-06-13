<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../model/User.php';
require_once __DIR__ . '/../../controller/AuthController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$success = '';

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

$error = '';

if (is_logged_in()) {
    header('Location: ../dashboard/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password tidak boleh kosong.';
    } else {
        $userModel = new User();
        $user = $userModel->findByUsername($username);

        if ($user && $userModel->verifyPassword($password, $user['password'])) {
            $_SESSION['users'] = [
                'id' => $user['user_id'],
                'nama' => $user['nama'],
                'role' => $user['role'],
                'username' => $user['username'],
                'email' => $user['email'] ?? '',
            ];
            header('Location: ../dashboard/index.php');
            exit;
        } else {
            $error = 'Username atau password salah.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Login - UMKM Inventory</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../../assets/css/auth.css">
</head>

<body class="auth-page">

    <div class="container">

        <div class="row justify-content-center align-items-center min-vh-100">

            <div class="col-lg-10">
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <div class="card auth-card">

                    <div class="row g-0">

                        <div class="col-lg-6 auth-left">

                            <div class="auth-logo mb-4">
                                <i class="bi bi-box-seam"></i>
                            </div>

                            <h2 class="auth-title">
                                Selamat Datang
                            </h2>

                            <p class="auth-subtitle">
                                Login untuk mengakses sistem inventory.
                            </p>

                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger">
                                    <?= htmlspecialchars($error) ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST">

                                <div class="mb-3">
                                    <label class="form-label">
                                        Username
                                    </label>

                                    <input type="text" name="username" class="form-control" required>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">
                                        Password
                                    </label>

                                    <input type="password" name="password" class="form-control" required>
                                </div>

                                <button class="btn btn-primary btn-auth w-100">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>
                                    Login
                                </button>

                            </form>

                            <div class="auth-link">
                                Belum punya akun?
                                <a href="register.php">
                                    Daftar
                                </a>
                            </div>

                        </div>

                        <div class="col-lg-6 auth-right d-flex flex-column justify-content-center">

                            <h2 class="fw-bold mb-3">
                                UMKM Inventory
                            </h2>

                            <p class="mb-4">
                                Kelola stok, penjualan, dan laporan bisnis lebih mudah.
                            </p>

                            <div class="feature-item">
                                <i class="bi bi-box-seam"></i>
                                <span>Monitoring stok real-time</span>
                            </div>

                            <div class="feature-item">
                                <i class="bi bi-cart-check"></i>
                                <span>Transaksi cepat dan akurat</span>
                            </div>

                            <div class="feature-item">
                                <i class="bi bi-graph-up"></i>
                                <span>Laporan lengkap</span>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</body>

</html>