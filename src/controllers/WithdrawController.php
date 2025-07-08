<?php
namespace App\Controllers;

use Config\Database;
use Katorymnd\PawaPayIntegration\Api\ApiClient;

class WithdrawController {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function index() {
        global $content, $title;
        $title = "Retrait";
        $phone = $_SESSION['user_phone'] ?? null;

        if ($phone) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $amount = floatval($_POST['amount']);
                $withdraw_phone = $_POST['withdraw_phone'];
                $operator = $_POST['operator'] ?? 'MTN'; // Opérateur par défaut

                $stmt = $this->db->prepare("SELECT balance FROM users WHERE phone = ? LIMIT 1");
                $stmt->execute([$phone]);
                $balance = $stmt->fetchColumn();

                $commission = $amount * 0.10;
                $net_amount = $amount - $commission; // Montant net reçu
                $total_deducted = $amount; // Montant total déduit du solde

                if ($amount > 0 && $total_deducted <= $balance) {
                    // Intégration de l'API pawaPay pour le retrait
                    $apiToken = $_ENV['APP_ENV'] === 'production' ? $_ENV['PAWAPAY_PRODUCTION_API_TOKEN'] : $_ENV['PAWAPAY_SANDBOX_API_TOKEN'];
                    $apiClient = new ApiClient($apiToken, $_ENV['APP_ENV'] ?? 'sandbox');

                    $payoutData = [
                        'payoutId' => uniqid(),
                        'amount' => $net_amount,
                        'currency' => 'XAF',
                        'correspondent' => $operator, // Opérateur choisi
                        'recipient' => [
                            'type' => 'MSISDN',
                            'address' => [
                                'value' => $withdraw_phone
                            ]
                        ],
                        'statementDescription' => 'Retrait EagleCash',
                        'metadata' => [
                            'commission' => $commission
                        ]
                    ];

                    try {
                        $response = $apiClient->initiatePayout(
                            $payoutData['payoutId'],
                            $payoutData['amount'],
                            $payoutData['currency'],
                            $payoutData['correspondent'],
                            $payoutData['recipient'],
                            $payoutData['statementDescription'],
                            $payoutData['metadata']
                        );
                        if ($response['status'] === 200) {
                            $redirectUrl = $response['response']['redirectUrl'] ?? $response['response']['paymentUrl'] ?? null;
                            if ($redirectUrl) {
                                $stmt = $this->db->prepare("UPDATE users SET balance = balance - ? WHERE phone = ?");
                                $stmt->execute([$total_deducted, $phone]);
                                header("Location: " . $redirectUrl);
                                exit;
                            } else {
                                $_SESSION['success'] = "Retrait de " . $net_amount . " FCFA initié via " . $operator . ". Traitement en cours...";
                                $stmt = $this->db->prepare("UPDATE users SET balance = balance - ? WHERE phone = ?");
                                $stmt->execute([$total_deducted, $phone]);
                                if ($stmt->rowCount()) {
                                    $stmt = $this->db->prepare("INSERT INTO withdrawals (user_phone, amount, commission, total_deducted, operator) VALUES (?, ?, ?, ?, ?)");
                                    $stmt->execute([$phone, $net_amount, $commission, $total_deducted, $operator]);
                                }
                            }
                        } else {
                            $_SESSION['error'] = "Échec du retrait via " . $operator . " : " . json_encode($response['response']);
                        }
                    } catch (\Exception $e) {
                        $_SESSION['error'] = "Erreur avec " . $operator . " : " . $e->getMessage();
                    }
                } else {
                    $_SESSION['error'] = "Montant invalide ou solde insuffisant (après déduction de la commission de 10%).";
                }
                header("Location: ?action=account");
                exit;
            }

            // Définir les opérateurs disponibles par pays
            $countryCode = substr($phone, 0, 4);
            $operators = [
                '+237' => ['MTN', 'Orange'], // Cameroun
                '+225' => ['MTN', 'Orange'], // Côte d'Ivoire
                '+226' => ['Orange', 'Moov'], // Burkina Faso
                '+241' => ['Orange', 'Airtel'], // Gabon
                '+229' => ['MTN', 'Orange'], // Bénin
                '+254' => ['M-Pesa', 'Airtel'], // Kenya
                '+221' => ['Orange', 'Wave'], // Sénégal
                '+243' => ['Orange', 'M-Pesa'] // RDC
            ];
            $availableOperators = $operators[$countryCode] ?? ['MTN', 'Orange']; // Par défaut Cameroun

            $operatorOptions = '';
            foreach ($availableOperators as $op) {
                $operatorOptions .= "<option value=\"{$op}\">{$op}</option>";
            }

            $content = '
                <div style="position: relative; padding: 1.5rem 0; min-height: calc(100vh - 60px); margin-top: 60px; background: linear-gradient(135deg, #f7fafc, #e6f0fa); color: #2d3748;">
                    <svg style="position: absolute; top: 10%; left: 5%; opacity: 0.1; z-index: 0;" width="100" height="100" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="1">
                        <circle cx="12" cy="12" r="10"></circle>
                    </svg>
                    <svg style="position: absolute; bottom: 10%; right: 5%; opacity: 0.1; z-index: 0;" width="150" height="150" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="1">
                        <path d="M3 3l18 18M3 21l18-18" />
                    </svg>
                    <div style="max-width: 80rem; margin-left: auto; margin-right: auto; padding-left: 0.75rem; padding-right: 0.75rem; @media (min-width: 768px) { padding-left: 1.5rem; padding-right: 1.5rem; } position: relative; z-index: 1;">
                        <div style="background: linear-gradient(135deg, #ffffff, #f0f4f8); overflow: hidden; box-shadow: 0 8px 12px rgba(0,0,0,0.15); border-radius: 0.75rem; border: 2px solid #3b82f6; padding: 1rem; @media (min-width: 768px) { padding: 1.5rem; } position: relative;">
                            <svg style="position: absolute; top: -20px; left: -20px; opacity: 0.05;" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="1">
                                <circle cx="12" cy="12" r="8"></circle>
                            </svg>
                            <h2 style="font-size: 1.5rem; font-weight: 700; color: #f59e0b; margin-bottom: 1rem; text-align: center; @media (min-width: 768px) { font-size: 1.75rem; text-align: left; } position: relative; z-index: 1;">
                                Effectuer un Retrait
                            </h2>
                            <p style="margin-bottom: 1rem; font-size: 0.875rem; color: #718096; position: relative; z-index: 1; text-align: center;">Retirez vos fonds en choisissant un opérateur. Une commission de 10% sera déduite.</p>

                            <form method="POST" action="?action=withdraw" style="margin-bottom: 1.5rem;">
                                <div style="margin-bottom: 1rem;">
                                    <label for="withdraw_phone" style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Numéro de téléphone</label>
                                    <input type="tel" name="withdraw_phone" id="withdraw_phone" value="' . htmlspecialchars($phone) . '" placeholder="Entrez votre numéro (ex: +2376...)" required style="width: 100%; padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 0.375rem; font-size: 1rem; background-color: #f0f0f0; cursor: not-allowed;" readonly>
                                </div>
                                <div style="margin-bottom: 1rem;">
                                    <label for="amount" style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Montant demandé (FCFA)</label>
                                    <input type="number" name="amount" id="amount" placeholder="Entrez le montant" required style="width: 100%; padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 0.375rem; font-size: 1rem; transition: border-color 0.3s;" oninput="updateNetAmount()">
                                </div>
                                <div style="margin-bottom: 1rem;">
                                    <label for="operator" style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Opérateur :</label>
                                    <select name="operator" id="operator" style="width: 100%; padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 0.375rem; font-size: 1rem;">
                                        ' . $operatorOptions . '
                                    </select>
                                </div>
                                <div id="net-amount" style="margin-bottom: 1rem; color: #374151; font-weight: 500;">Montant net à retirer : 0 FCFA (après 10% de commission)</div>
                                <button type="submit" style="background-color: #3b82f6; color: #ffffff; padding: 0.75rem 1.5rem; border: none; border-radius: 0.375rem; font-weight: 600; width: 100%; cursor: pointer; transition: background-color 0.3s;">Retirer</button>
                            </form>

                            <div style="background: linear-gradient(135deg, #fefcbf, #fef9c3); border-radius: 0.75rem; box-shadow: 0 8px 12px rgba(0,0,0,0.15); padding: 1rem; margin-top: 1.5rem; border: 2px solid #f59e0b; position: relative;">
                                <svg style="position: absolute; top: -30px; right: -30px; opacity: 0.05;" width="100" height="100" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="1">
                                    <path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z" />
                                </svg>
                                <h3 style="font-size: 1.25rem; font-weight: 700; color: #d97706; margin-bottom: 1rem; position: relative; z-index: 1;">Instructions de Retrait</h3>
                                <p style="color: #374151; margin-bottom: 1rem;">Retirez vos fonds via l’opérateur sélectionné :</p>
                                <ul style="list-style: none; padding-left: 0;" id="withdraw-instructions">
                                    <li style="margin-bottom: 0.5rem;" data-country="+237"><strong>Cameroun (+237) :</strong> MTN : *126#, Orange : *150#, sélectionnez "Retrait", entrez le montant net et confirmez.</li>
                                    <li style="margin-bottom: 0.5rem;" data-country="+225"><strong>Côte d’Ivoire (+225) :</strong> MTN : *155#, Orange : *144#, choisissez "Retrait" chez un agent.</li>
                                    <li style="margin-bottom: 0.5rem;" data-country="+226"><strong>Burkina Faso (+226) :</strong> Orange : *144#, Moov : *155#, sélectionnez "Retrait" via un agent.</li>
                                    <li style="margin-bottom: 0.5rem;" data-country="+241"><strong>Gabon (+241) :</strong> Orange : *155#, Airtel : *150#, optez pour "Retrait".</li>
                                    <li style="margin-bottom: 0.5rem;" data-country="+229"><strong>Bénin (+229) :</strong> MTN : *126#, Orange : *165#, sélectionnez "Retrait" via un agent.</li>
                                    <li style="margin-bottom: 0.5rem;" data-country="+254"><strong>Kenya (+254) :</strong> M-Pesa : *334#, Airtel : *334#, choisissez "Retrait M-Pesa".</li>
                                    <li style="margin-bottom: 0.5rem;" data-country="+221"><strong>Sénégal (+221) :</strong> Orange : *144#, Wave : *123#, sélectionnez "Retrait" chez un agent.</li>
                                    <li style="margin-bottom: 0.5rem;" data-country="+243"><strong>RDC (+243) :</strong> Orange : *144#, M-Pesa : *150#, validez avec un agent.</li>
                                </ul>
                                <p style="color: #374151;">Le retrait via pawaPay est traité après confirmation. Vérifiez votre solde après 24h.</p>
                            </div>

                            <div style="margin-top: 1.5rem; text-align: center;">
                                <a href="?action=dashboard" style="color: #3b82f6; text-decoration: underline; font-weight: 600;">Retour au tableau de bord</a>
                            </div>
                        </div>
                    </div>
                </div>

                <style>
                    .container { margin-top: 60px; }
                    input[type="number"]:focus { border-color: #3b82f6; outline: none; }
                    input[readonly] { background-color: #f0f0f0; cursor: not-allowed; }
                    button:hover { background-color: #60a5fa; }
                    #withdraw-instructions li { display: none; }
                    #withdraw-instructions li[data-country="' . substr($phone, 0, 4) . '"] { display: block; }
                    @media (max-width: 768px) {
                        h2 { font-size: 1.25rem; }
                        h3 { font-size: 1rem; }
                        input, button { font-size: 0.9rem; padding: 0.5rem; }
                        #withdraw-instructions li { font-size: 0.85rem; }
                    }
                </style>

                <script>
                    function updateNetAmount() {
                        const amountInput = document.getElementById("amount");
                        const netAmountDiv = document.getElementById("net-amount");
                        const amount = parseFloat(amountInput.value) || 0;
                        const commission = amount * 0.10;
                        const netAmount = amount - commission;
                        netAmountDiv.textContent = `Montant net à retirer : ${netAmount.toFixed(2)} FCFA (après 10% de commission)`;
                    }

                    document.addEventListener("DOMContentLoaded", function() {
                        const phoneInput = document.getElementById("withdraw_phone");
                        const instructions = document.getElementById("withdraw-instructions");
                        const items = instructions.getElementsByTagName("li");

                        phoneInput.addEventListener("input", function() {
                            const countryCode = "+" + phoneInput.value.split("+")[1]?.slice(0, 3) || "";
                            for (let item of items) {
                                item.style.display = item.getAttribute("data-country") === countryCode ? "block" : "none";
                            }
                        });

                        // Initialiser le montant net
                        updateNetAmount();
                    });
                </script>';
        } else {
            $content = '<div style="text-align: center; padding: 1.5rem; background-color: #ffffff; border-radius: 0.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 60px;"><p style="color: #4a5568;">Connectez-vous pour effectuer un retrait.</p></div>';
        }
        require_once __DIR__ . '/../../public/views/layout.php';
    }
}