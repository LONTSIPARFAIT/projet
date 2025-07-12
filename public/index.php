<?php
require_once __DIR__ . '/../vendor/autoload.php';
$databasePath = __DIR__ . '/../config/Database.php';
// if (file_exists($databasePath)) {
//     require_once $databasePath;
//     echo "Fichier Database.php inclus avec succès à : " . $databasePath . "<br>";
//     if (class_exists('Config\Database')) {
//         echo "Classe Config\Database est définie.<br>";
//     } else {
//         echo "Erreur : La classe Config\Database n'est pas définie après inclusion.<br>";
//     }
// } else {
//     die("Fichier Database.php non trouvé à : " . $databasePath);
// }

// Activer les erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Déplacer les use avant l'utilisation
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\AffiliateController;
use App\Controllers\TradingController;
use App\Controllers\ChinaImportController;
use App\Controllers\AccountController;
use App\Controllers\LogoutController;
use App\Controllers\DepositController;
use App\Controllers\WithdrawController;
use App\Controllers\FormationController;
use App\Controllers\MarketplaceController;
use App\Controllers\AffiliationController;
use Config\Database;
use App\Services\SessionManager;

// Réinitialiser la session à chaque accès à la racine
session_start();
if (!isset($_GET['action'])) {
    session_destroy();
    session_start();
    // echo "Débogage - Session réinitialisée à la racine<br>";
}

$sessionManager = new SessionManager();
$controller = null;
$action = $_GET['action'] ?? null;
$content = '';

// Débogage détaillé de la session
$userPhone = $sessionManager->get('user_phone');
// echo "Débogage - user_phone (après session) : '" . ($userPhone ?? 'null') . "'<br>";
// echo "Débogage - Session complète : " . print_r($_SESSION, true) . "<br>";

// Définir les actions publiques (ne nécessitant pas de connexion)
$publicActions = ['register', 'login', 'payment'];

// Rediriger si l'utilisateur n'est pas connecté et tente d'accéder à une page protégée
$protectedActions = ['dashboard-unique', 'affiliate-unique', 'formation-unique', 'marketplace-unique', 'account-unique', 'deposit', 'withdraw'];
if (!$userPhone && in_array($action, $protectedActions)) {
    echo "Débogage - Redirection vers login car non connecté et action protégée<br>";
    header("Location: ?action=login");
    exit;
}

// Logique initiale : rediriger selon l'état de connexion
if (!$action) {
    if (!$userPhone) {
        echo "Débogage - Redirection vers register car non connecté<br>";
        header("Location: ?action=register");
        exit;
    } else {
        try {
            $db = (new Config\Database())->getConnection();
            $stmt = $db->prepare("SELECT payment_verified, payment_amount FROM users WHERE phone = ? LIMIT 1");
            $stmt->execute([$userPhone]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            echo "Débogage - Utilisateur : " . ($userPhone ?? 'non connecté') . ", Payment verified: " . ($user['payment_verified'] ?? 'non défini') . ", Amount: " . ($user['payment_amount'] ?? '0') . "<br>";
            if ($user && $user['payment_verified'] == 0 && $user['payment_amount'] < 3000.00) {
                header("Location: ?action=payment");
                exit;
            } else {
                header("Location: ?action=dashboard-unique");
                exit;
            }
        } catch (Exception $e) {
            $sessionManager->set('error', "Erreur de connexion à la base de données : " . htmlspecialchars($e->getMessage()));
            header("Location: ?action=login");
            exit;
        }
    }
}

try {
    if ($action) {
        // echo "Traitement de l'action : $action<br>";
        switch ($action) {
            case 'register':
                $controller = new AuthController();
                $controller->register();
                break;
            case 'login':
                $controller = new AuthController();
                $controller->login();
                break;
            case 'dashboard-unique':
                if (!isPaymentVerified($sessionManager)) {
                    header("Location: ?action=payment");
                    exit;
                }
                $controller = new DashboardController();
                $controller->index();
                break;
            case 'payment':
                global $content;
                if (!$sessionManager->get('user_phone')) {
                    $sessionManager->set('error', "Vous devez être connecté pour effectuer un paiement.");
                    header("Location: ?action=login");
                    exit;
                }
                $phone = $sessionManager->get('user_phone');
                $countryCode = substr($phone, 0, 4);
                $networks = [
                    '+237' => ['MTN', 'Orange'],
                    '+225' => ['MTN', 'Orange', 'Moov'],
                    '+226' => ['Orange', 'Moov'],
                    '+241' => ['Orange', 'Airtel'],
                    '+229' => ['MTN', 'Orange'],
                    '+254' => ['M-Pesa', 'Airtel'],
                    '+221' => ['Orange', 'Wave'],
                    '+243' => ['Orange', 'M-Pesa', 'Airtel']
                ];
                $availableNetworks = $networks[$countryCode] ?? ['MTN', 'Orange'];
                $content = '
                    <div style="position: relative; min-height: calc(100vh - 60px); margin-top: 60px; background: linear-gradient(135deg, #f7fafc, #e6f0fa); display: flex; justify-content: center; align-items: center; padding: 1rem;">
                        <div style="background: white; padding: 2rem; border-radius: 0.75rem; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); max-width: 400px; width: 100%; text-align: center;">
                        <img src="/mes-projets/eaglecash-poo/public/img/logo.jpg" alt="Logo de la plateforme" style="max-width: 100px; margin-bottom: 1rem;">
                            <h2 style="color: #2d3748; font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem;">Paiement d\'inscription</h2>
                            <p style="color: #4a5568; font-size: 1rem; margin-bottom: 1.5rem;">Le montant minimum est de 3000 FCFA. Vous pouvez payer plus si vous le souhaitez.</p>
                            <p style="color: #718096; font-size: 0.875rem; margin-bottom: 1.5rem;">Choisissez votre réseau de paiement et entrez le montant.</p>
                            <form method="POST" action="?action=deposit" style="display: flex; flex-direction: column; gap: 1rem;" onsubmit="return validatePaymentForm();">
                                <div style="display: flex; gap: 0.5rem;">
                                    <input type="text" id="country_code" name="country_code" required placeholder="+237" style="padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 0.375rem; font-size: 1rem; outline: none; width: 80px; transition: border-color 0.3s;" value="' . htmlspecialchars($countryCode) . '" readonly>
                                    <input type="number" name="amount" min="3000" step="100" required placeholder="Montant (FCFA)" style="padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 0.375rem; font-size: 1rem; outline: none; flex: 1; transition: border-color 0.3s;">
                                </div>
                                <select name="payment_network" style="padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 0.375rem; font-size: 1rem; outline: none; appearance: none; background: url(\'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="%234a5568" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>\') no-repeat right 0.75rem center; cursor: pointer; transition: border-color 0.3s;">';
                foreach ($availableNetworks as $network) {
                    $content .= '<option value="' . htmlspecialchars($network) . '">' . htmlspecialchars($network) . ' Money</option>';
                }
                $content .= '</select>
                                <button type="submit" style="padding: 0.75rem; background: linear-gradient(90deg, #f59e0b, #f97316); color: white; border: none; border-radius: 0.375rem; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.3s;">Payer</button>
                            </form>
                        </div>
                    </div>
                    <style>
                        input:focus, select:focus { border-color: #f59e0b; }
                        button:hover { background: linear-gradient(90deg, #d97706, #ea580c); }
                        @media (max-width: 480px) {
                            div[style*="max-width: 400px"] { padding: 1rem; }
                            div[style*="display: flex; gap: 0.5rem;"] { flex-direction: column; }
                            input#country_code { width: 100%; }
                        }
                    </style>
                    <script>
                        function validatePaymentForm() {
                            return true;
                        }
                        document.addEventListener("DOMContentLoaded", function() {
                            validatePaymentForm();
                        });
                    </script>';
                break;
            case 'deposit':
                $controller = new DepositController();
                $controller->deposit();
                break;
            case 'affiliate-unique':
                if (!isPaymentVerified($sessionManager)) {
                    header("Location: ?action=payment");
                    exit;
                }
                $controller = new AffiliateController();
                $controller->index();
                break;
            case 'formation-unique':
                if (!isPaymentVerified($sessionManager)) {
                    header("Location: ?action=payment");
                    exit;
                }
                $controller = new FormationController();
                $controller->index();
                break;
            case 'marketplace-unique':
                if (!isPaymentVerified($sessionManager)) {
                    header("Location: ?action=payment");
                    exit;
                }
                $controller = new MarketplaceController();
                $controller->index();
                break;
            case 'account-unique':
                if (!isPaymentVerified($sessionManager)) {
                    header("Location: ?action=payment");
                    exit;
                }
                $controller = new AccountController();
                $controller->index();
                break;
            case 'withdraw':
                if (!isPaymentVerified($sessionManager)) {
                    header("Location: ?action=payment");
                    exit;
                }
                $controller = new WithdrawController();
                $controller->index();
                break;
            case 'logout':
                $controller = new LogoutController();
                $controller->logout();
                break;
            case 'affiliation-guide':
                $controller = new AffiliationController();
                $controller->affiliationGuide();
                break;
            default:
                $content = '<div class="container"><h2>Page non trouvée</h2><p>L\'action demandée n\'existe pas.</p></div>';
        }
    } else {
        if (!$userPhone) {
            header("Location: ?action=register");
            exit;
        }
    }
} catch (Exception $e) {
    $content = '<div class="container"><h2>Erreur</h2><p>Une erreur s\'est produite : ' . htmlspecialchars($e->getMessage()) . '</p></div>';
    echo "Erreur capturée : " . htmlspecialchars($e->getMessage()) . "<br>";
}

if (empty($content)) {
    $content = '<div class="container"><h2>Page non trouvée</h2><p>Contenu non défini pour cette action.</p></div>';
    // echo "Contenu vide, action : $action<br>";
}

// Passer $sessionManager à layout.php via une variable globale
global $sessionManager;
require_once __DIR__ . '/views/layout.php';

// Fonction globale pour vérifier le paiement
function isPaymentVerified($sessionManager) {
    if (!$sessionManager->get('user_phone')) return false;
    try {
        $db = (new Config\Database())->getConnection();
        $stmt = $db->prepare("SELECT payment_verified, payment_amount FROM users WHERE phone = ? LIMIT 1");
        $stmt->execute([$sessionManager->get('user_phone')]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $user && $user['payment_verified'] == 1 && $user['payment_amount'] >= 3000.00;
    } catch (Exception $e) {
        return false;
    }
}