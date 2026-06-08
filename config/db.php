<?php

session_start();

define('DB_HOST', 'localhost');
define('DB_user', 'root');
define('DB_PASS', '');
define('DB_NAME', 'inventory');

function getDB(): mysqli
{

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die('Koneksi database gagal: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');

    return $conn;
}

function redirect(string $path): void
{
    $base = rtrim(defined('BASE_URL') ? BASE_URL : '/', '/');
    header("Location: $base/$path");
    exit;
}

function is_logged_in(): bool
{
    return isset($_SESSION['users']['id']);
}

function allow_roles(array $roles): void
{
    $current_role = strtolower($_SESSION['users']['role'] ?? '');
    $allowed = array_map('strtolower', $roles);

    if (!in_array($current_role, $allowed)) {
        $_SESSION['flash'] = 'Anda tidak memiliki akses ke halaman ini.';
        redirect('dashboard/index');
    }
}

function format_rupiah(float $angka): string
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
}