<?php
require_once __DIR__ . '/../config/db.php';

 if (!is_logged_in()) {
            redirect('auth/login');
        }