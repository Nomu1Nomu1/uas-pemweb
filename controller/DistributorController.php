<?php
require_once __DIR__ . '/../config/db.php';

class DistributorController {
    public function index() {
        if (!is_logged_in()) {
            redirect('auth/login');
        }

        $db = getDB();
        $search = trim($_GET['search'] ?? '');

        if ($search !== '') {
            $like = "%$search%";
            $stmt = $db->prepare("SELECT * FROM distributors WHERE nama_distributor LIKE ? OR alamat LIKE ? OR no_hp LIKE ? ORDER BY id DESC");

            $stmt->bind_param('sss', $like, $like, $like);
            $stmt->execute();
            $distributors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $distributors = $db->query("SELECT * FROM distributors ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
        }

        $pageTitle = 'Data Distributor';
        require_once __DIR__ . '/../view/distributor/';
    }
}