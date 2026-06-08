<?php
require_once __DIR__ . '/../config/db.php';


class PengadaanController {
    if (!is_logged_in()) {
        redirect('auth/login');
    }
}