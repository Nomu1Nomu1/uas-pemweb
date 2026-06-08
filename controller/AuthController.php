<?php
require_once __DIR__ . '/../config/db.php';

class AuthController {
    public function login() {
        if (is_logged_in()) {
            redirect('dashboard/index');
        }

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($password)) {
                $error = 'Username dan password tidak boleh kosong.';
            } else {
                $db = getDB();
                $sql = "SELECT * FROM users WHERE username = ? AND is_active = 'Y' LIMIT 1";
                $stmt = $db -> prepare($sql);
                $stmt ->bind_param('s', $username);
                $stmt -> execute();
                
                $result = $stmt -> get_result();
                $user = $result -> fetch_assoc();

                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['users'] = [
                        'id'       => $user['user_id'],
                        'nama'     => $user['nama'],
                        'role'     => $user['role'],
                        'username' => $user['username'],
                    ];
                    redirect('dashboard/index');
                } else {
                    $error = 'Username atau password salah.';
                }
            }
        }
        $page_title = 'Login';
        require_once __DIR__ . '/../view/auth/login.php';
    }

    public function logout() {
        session_destroy();
        redirect('auth/login');
    }
}