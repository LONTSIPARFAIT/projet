<?php
namespace App\Controllers;

use Config\Database;
use Katorymnd\PawaPayIntegration\Api\ApiClient;

class DepositController {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function deposit() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_phone'])) {
            $phone = $_SESSION['user_phone'];
            $amount = floatval($_POST['amount']);
            $country_code = $_POST['country_code'] ?? substr($phone, 0, 4);
            $deposit_phone = $country_code . substr($phone, 4);
            $payment_network = $_POST['payment_network'] ?? 'MTN';

            if ($deposit_phone !== $phone) {
                $_SESSION['error'] = "Le numéro de téléphone pour le dépôt doit correspondre à celui utilisé pour créer votre compte.";
                header("Location: ?action=payment");
                exit;
            }

            if ($amount < 3000) {
                $_SESSION['error'] = "Montant invalide (minimum 3000 FCFA).";
                header("Location: ?action=payment");
                exit;
            }

            if (!$this->isValidPhoneNumber($deposit_phone)) {
                $_SESSION['error'] = "Numéro de téléphone invalide.";
                header("Location: ?action=payment");
                exit;
            }

            $apiToken = $_ENV['PAWAPAY_SANDBOX_API_TOKEN'];
            error_log("Token utilisé : " . $apiToken);
            $apiClient = new ApiClient($apiToken, 'sandbox');

            $depositData = [
                'depositId' => uniqid(),
                'amount' => $amount,
                'currency' => 'XAF',
                'correspondent' => $payment_network,
                'payer' => ['type' => 'MSISDN', 'address' => ['value' => $deposit_phone]],
                'statementDescription' => 'Dépôt EagleCash',
                'metadata' => ['is_first_deposit' => $this->isFirstDeposit($deposit_phone) ? 1 : 0]
            ];

            try {
                $response = $apiClient->initiateDeposit(
                    $depositData['depositId'],
                    $depositData['amount'],
                    $depositData['currency'],
                    $depositData['correspondent'],
                    $depositData['payer'],
                    $depositData['statementDescription'],
                    $depositData['metadata']
                );
                error_log("Réponse pawaPay : " . print_r($response, true));
                if ($response['status'] === 200) {
                    $redirectUrl = $response['response']['redirectUrl'] ?? $response['response']['paymentUrl'] ?? null;
                    if ($redirectUrl) {
                        header("Location: " . $redirectUrl);
                        exit;
                    } else {
                        $this->recordDeposit($deposit_phone, $amount, true);
                        if ($this->isFirstDeposit($deposit_phone)) {
                            $this->updateReferralBonuses($deposit_phone, $amount);
                        }
                        $_SESSION['success'] = "Dépôt de $amount FCFA initié via $payment_network. Veuillez finaliser le paiement.";
                    }
                } else {
                    $_SESSION['error'] = "Échec du dépôt via $payment_network : Statut " . $response['status'] . " - " . json_encode($response['response']);
                }
            } catch (\Exception $e) {
                $_SESSION['error'] = "Erreur avec $payment_network : " . $e->getMessage() . ". Veuillez contacter le support.";
                error_log("Erreur pawaPay : " . $e->getMessage());
            }
            header("Location: ?action=payment");
            exit;
        }
    }

    private function isValidPhoneNumber($phone) {
        $validPrefixes = ['+237', '+225', '+226', '+241', '+229', '+254', '+221', '+243'];
        $cleanedPhone = preg_replace('/[^0-9+]/', '', $phone);
        return in_array(substr($cleanedPhone, 0, 4), $validPrefixes) && strlen($cleanedPhone) >= 9;
    }

    private function isFirstDeposit($phone) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM deposits WHERE user_phone = ? AND is_pending = 0");
        $stmt->execute([$phone]);
        return $stmt->fetchColumn() == 0;
    }

    private function recordDeposit($phone, $amount, $pending = false) {
        $stmt = $this->db->prepare("INSERT INTO deposits (user_phone, amount, is_first_deposit, is_pending) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE amount = ?, is_pending = ?");
        $stmt->execute([$phone, $amount, $this->isFirstDeposit($phone) ? 1 : 0, $pending ? 1 : 0, $amount, $pending ? 1 : 0]);
    }

    private function getOperatorForCountry($phone) {
        $countryCode = substr(preg_replace('/[^0-9+]/', '', $phone), 0, 4);
        $operators = [
            '+237' => 'MTN',
            '+225' => 'MTN',
            '+226' => 'Orange',
            '+241' => 'Orange',
            '+229' => 'MTN',
            '+254' => 'M-Pesa',
            '+221' => 'Orange',
            '+243' => 'Orange'
        ];
        return $operators[$countryCode] ?? 'MTN';
    }

    private function updateReferralBonuses($user_phone, $amount) {
        if ($amount < 3000) return;

        $stmt = $this->db->prepare("SELECT referred_by FROM users WHERE phone = ? LIMIT 1");
        $stmt->execute([$user_phone]);
        $referrer = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($referrer) {
            $current_referrer = $referrer['referred_by'];
            $level = 1;

            while ($current_referrer && $level <= 3) {
                $stmt = $this->db->prepare("SELECT phone FROM users WHERE phone = ? OR affiliate_link = ? LIMIT 1");
                $stmt->execute([$current_referrer, $current_referrer]);
                $referrer_exists = $stmt->fetch(\PDO::FETCH_ASSOC);
                if (!$referrer_exists) break;

                $bonus = $this->getBonusForLevel($level);
                if ($bonus > 0) {
                    $stmt = $this->db->prepare("INSERT INTO referrals (user_phone, referred_by, level, bonus_earned, paid) VALUES (?, ?, ?, ?, 0) ON DUPLICATE KEY UPDATE bonus_earned = bonus_earned + ?");
                    $stmt->execute([$user_phone, $current_referrer, $level, $bonus, $bonus]);

                    $stmt = $this->db->prepare("UPDATE users SET balance = balance + ? WHERE phone = ?");
                    $stmt->execute([$bonus, $current_referrer]);
                }

                $stmt = $this->db->prepare("SELECT referred_by FROM users WHERE phone = ? OR affiliate_link = ? LIMIT 1");
                $stmt->execute([$current_referrer, $current_referrer]);
                $next_referrer = $stmt->fetch(\PDO::FETCH_ASSOC);
                $current_referrer = $next_referrer['referred_by'] ?? null;
                $level++;
            }
        }
    }

    private function getBonusForLevel($level) {
        return match ($level) {
            1 => 1500,
            2 => 700,
            3 => 350,
            default => 0
        };
    }
}