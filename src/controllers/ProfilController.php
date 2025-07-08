<?php
namespace App\Controllers;

use Config\Database;

class ProfilController {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function index() {
        $email = $_SESSION['user_email'] ?? null;
        $user = null;
        if ($email) {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        }
        $content = "<h2>Profil</h2>";
        if ($user) {
            $content .= "<p>Nom : " . htmlspecialchars($user['name']) . "</p>";
            $content .= "<p>Email : " . htmlspecialchars($user['email']) . "</p>";
            $content .= "<p>Téléphone : " . htmlspecialchars($user['phone']) . "</p>";
        } else {
            $content .= "<p>Utilisateur non connecté.</p>";
        }
        require_once __DIR__ . '/../../public/views/layout.php';
    }
}