<?php
namespace App\Controllers;

use Config\Database;

class ChinaImportController {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function index() {
        global $content;
        $phone = $_SESSION['user_phone'] ?? null;

        if ($phone) {
            $content = '
                <div class="header-section">
                    <img src="images/logo.png" alt="EagleCash Logo" class="logo">
                    <h2>Achat en Chine</h2>
                </div>
                <div class="content-section">
                    <img src="images/china-bg.jpg" alt="China Import" class="bg-image">
                    <p class="intro">Importez facilement avec nos guides :</p>
                    <ul class="guides">
                        <li><strong>Guide 1 :</strong> Importer via 1688 (30 min)</li>
                        <li><strong>Guide 2 :</strong> Négocier sur Alibaba (25 min)</li>
                    </ul>
                </div>
                <div class="resource-section">
                    <p class="resource">Ressource : Accédez à nos bases de données WhatsApp pour vos prospects.</p>
                </div>';
        } else {
            $content = '<div class="content-section><p>Connectez-vous pour accéder aux guides.</p></div>';
        }
    }
}