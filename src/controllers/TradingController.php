<?php
namespace App\Controllers;

use Config\Database;

class TradingController {
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
                    <h2>Formations Trading</h2>
                </div>
                <div class="content-section">
                    <img src="images/trading-bg.jpg" alt="Trading Chart" class="bg-image">
                    <p class="intro">Maîtrisez le marché avec nos cours interactifs :</p>
                    <ul class="lessons">
                        <li><strong>Leçon 1 :</strong> Lire les graphiques (15 min)</li>
                        <li><strong>Leçon 2 :</strong> Analyse technique (20 min)</li>
                        <li><strong>Leçon 3 :</strong> Gestion des risques (10 min)</li>
                    </ul>
                </div>
                <div class="tip-section">
                    <p class="tip">Astuce : Commencez avec un petit capital sur Forex ou crypto pour pratiquer !</p>
                </div>';
        } else {
            $content = '<div class="content-section"><p>Connectez-vous pour accéder aux cours.</p></div>';
        }
    }
}