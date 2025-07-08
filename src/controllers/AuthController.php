<?php
namespace App\Controllers;

use Config\Database;
use App\Services\SessionManager;
use App\Services\AuthService;

class AuthController {
    private $db;
    private $sessionManager;
    private $authService;

    public function __construct() {
        $this->db = (new Database())->getConnection();
        $this->sessionManager = new SessionManager();
        $this->authService = new AuthService();
    }

    public function register() {
        global $content, $title;
        $title = "Inscription";
        $phone = $this->sessionManager->get('user_phone') ?? null;
        $referral = $_GET['ref'] ?? null;

        if ($phone) {
            header("Location: ?action=dashboard-unique");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
            $country_code = filter_var($_POST['country_code'], FILTER_SANITIZE_STRING); // Récupérer country_code
            $phone_number = filter_var($_POST['phone'], FILTER_SANITIZE_STRING); // Numéro sans code
            $phone = $country_code . $phone_number; // Concaténer pour phone
            $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $referred_by = $_POST['referred_by'] ?? null;

            if (empty($name) || empty($phone_number) || empty($_POST['password'])) {
                $this->sessionManager->set('error', "Tous les champs sont requis.");
            } else if ($this->authService->validateUser($name, null, $phone, $password)) {
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE phone = ?");
                $stmt->execute([$phone]);
                if ($stmt->fetchColumn() > 0) {
                    $this->sessionManager->set('error', "Ce numéro est déjà utilisé.");
                } else {
                    $affiliate_link = $this->generateAffiliateLink($phone);
                    $stmt = $this->db->prepare("INSERT INTO users (name, phone, country_code, password, affiliate_link, referred_by, payment_verified, payment_amount) VALUES (?, ?, ?, ?, ?, ?, 0, 0.00)");
                    $stmt->execute([$name, $phone, $country_code, $password, $affiliate_link, $referred_by]);
                    if ($referred_by) {
                        $this->creditReferral($referred_by, $phone);
                    }
                    $this->sessionManager->set('user_phone', $phone);
                    $this->sessionManager->set('success', "Inscription réussie ! Veuillez effectuer le paiement d'au moins 3000 FCFA.");
                    header("Location: ?action=payment");
                    exit;
                }
            }
            header("Location: ?action=register" . ($referral ? "?ref=" . urlencode($referral) : ""));
            exit;
        } else {
            require_once __DIR__ . '/../../public/views/register.php';
        }
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $phone = $_POST['country_code'] . $_POST['phone'];
            $password = $_POST['password'];

            if (empty($phone) || empty($password)) {
                $this->sessionManager->set('error', "Téléphone et mot de passe requis.");
            } else if ($this->authService->validateUser(null, null, $phone, $password)) {
                $stmt = $this->db->prepare("SELECT * FROM users WHERE phone = ? LIMIT 1");
                $stmt->execute([$phone]);
                $user = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($user && password_verify($password, $user['password'])) {
                    $this->sessionManager->set('user_phone', $phone);
                    if ($user['payment_verified'] == 0 || $user['payment_amount'] < 3000.00) {
                        $this->sessionManager->set('success', "Connexion réussie ! Veuillez effectuer le paiement d'au moins 3000 FCFA.");
                        header("Location: ?action=payment");
                        exit;
                    } else {
                        $this->sessionManager->set('success', "Connexion réussie !");
                        header("Location: ?action=dashboard-unique");
                        exit;
                    }
                } else {
                    $this->sessionManager->set('error', "Téléphone ou mot de passe incorrect.");
                }
            }
            header("Location: ?action=login");
            exit;
        } else {
            require_once __DIR__ . '/../../public/views/login.php';
        }
    }

    public function payment() {
        global $content, $title;
        $title = "Paiement Initial";
        $phone = $this->sessionManager->get('user_phone') ?? null;

        if (!$phone) {
            $this->sessionManager->set('error', "Vous devez être connecté pour effectuer un paiement.");
            header("Location: ?action=login");
            exit;
        }

        if (isset($_SESSION['initial_deposit_done'])) {
            $stmt = $this->db->prepare("UPDATE users SET payment_verified = 1, payment_amount = ? WHERE phone = ?");
            $stmt->execute([$_SESSION['initial_deposit_amount'] ?? 3000.00, $phone]);
            $this->sessionManager->set('success', "Paiement initial de " . ($_SESSION['initial_deposit_amount'] ?? 3000.00) . " FCFA confirmé ! Vous pouvez maintenant accéder au tableau de bord.");
            unset($_SESSION['initial_deposit_done']);
            unset($_SESSION['initial_deposit_amount']);
            header("Location: ?action=dashboard-unique");
            exit;
        }

        $content = '<div style="text-align: center; padding: 2rem; margin-top: 60px;"><p style="color: #4a5568;">Veuillez effectuer le paiement via la page de dépôt.</p><a href="?action=deposit" style="color: #3b82f6; text-decoration: underline;">Aller à la page de dépôt</a></div>';
        require_once __DIR__ . '/../../public/views/layout.php';
    }

    private function generateAffiliateLink($phone) {
        return "https://eaglecash.com/ref/" . bin2hex(random_bytes(8)) . "-" . $phone;
    }

    private function creditReferral($referred_by, $user_phone) {
        try {
            $stmt = $this->db->prepare("SELECT referred_by FROM users WHERE phone = ? OR affiliate_link = ? LIMIT 1");
            $current_referrer = $referred_by;
            $level = 1;

            while ($current_referrer && $level <= 3) {
                $amount = 0;
                switch ($level) {
                    case 1:
                        $amount = 1500;
                        break;
                    case 2:
                        $amount = 700;
                        break;
                    case 3:
                        $amount = 350;
                        break;
                }

                if ($amount > 0) {
                    $stmt = $this->db->prepare("INSERT INTO referrals (user_phone, referred_by, level, bonus_earned, paid) VALUES (?, ?, ?, ?, 0) ON DUPLICATE KEY UPDATE bonus_earned = bonus_earned");
                    $stmt->execute([$user_phone, $current_referrer, $level, $amount]);
                }

                $stmt->execute([$current_referrer, $current_referrer]);
                $next_referrer = $stmt->fetch(\PDO::FETCH_ASSOC);
                $current_referrer = $next_referrer['referred_by'] ?? null;
                $level++;
            }
        } catch (Exception $e) {
            error_log("Erreur creditReferral : " . $e->getMessage());
        }
    }
}