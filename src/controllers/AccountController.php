<?php
namespace App\Controllers;

use Config\Database;
use DateTime;

class AccountController {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function index() {
        global $content, $title;
        $title = "Mon Profil";
        $phone = $_SESSION['user_phone'] ?? null;
        $user = null;

        if ($phone) {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE phone = ? LIMIT 1");
            $stmt->execute([$phone]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Générer un lien de parrainage unique s'il n'existe pas
            $affiliateLink = $user['affiliate_link'];
            if (empty($affiliateLink)) {
                $affiliateLink = "https://eaglecash.com/refer?user=" . urlencode($user['phone']) . "-" . bin2hex(random_bytes(4));
                $stmt = $this->db->prepare("UPDATE users SET affiliate_link = ? WHERE phone = ?");
                $stmt->execute([$affiliateLink, $phone]);
            }

            $totalDeposits = 0;
            $totalWithdrawals = 0;
            $stmt = $this->db->prepare("SELECT COUNT(*) as count, SUM(amount) as total FROM deposits WHERE user_phone = ?");
            $stmt->execute([$phone]);
            $depositData = $stmt->fetch(\PDO::FETCH_ASSOC);
            $totalDeposits = $depositData['total'] ?: 0;
            $depositCount = $depositData['count'] ?: 0;

            $stmt = $this->db->prepare("SELECT COUNT(*) as count, SUM(amount) as total FROM withdrawals WHERE user_phone = ?");
            $stmt->execute([$phone]);
            $withdrawalData = $stmt->fetch(\PDO::FETCH_ASSOC);
            $totalWithdrawals = $withdrawalData['total'] ?: 0;
            $withdrawalCount = $withdrawalData['count'] ?: 0;

            // Récupérer les 5 dernières transactions
            $stmt = $this->db->prepare("SELECT type, amount, created_at FROM (SELECT 'Dépôt' as type, amount, created_at FROM deposits WHERE user_phone = ? UNION ALL SELECT 'Retrait' as type, amount, created_at FROM withdrawals WHERE user_phone = ?) AS transactions ORDER BY created_at DESC LIMIT 5");
            $stmt->execute([$phone, $phone]);
            $transactions = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Calcul du temps depuis l'inscription
            $inscriptionDate = $user['created_at'] ?? date('Y-m-d H:i:s');
            $inscriptionTime = (new DateTime())->diff(new DateTime($inscriptionDate));
            $inscriptionDuration = $inscriptionTime->days . ' jours';

            // Récupérer les revenus et le nombre de filleuls par niveau
            $stmt = $this->db->prepare("SELECT r.user_phone FROM referrals r JOIN deposits d ON r.user_phone = d.user_phone WHERE r.referred_by = ? AND r.level = 1 AND d.is_first_deposit = TRUE");
            $stmt->execute([$user['affiliate_link']]);
            $level1_referrals = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            $stmt = $this->db->prepare("SELECT r.user_phone FROM referrals r JOIN deposits d ON r.user_phone = d.user_phone WHERE r.referred_by IN (SELECT user_phone FROM referrals WHERE referred_by = ? AND level = 1) AND r.level = 2 AND d.is_first_deposit = TRUE");
            $stmt->execute([$user['affiliate_link']]);
            $level2_referrals = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            $stmt = $this->db->prepare("SELECT r.user_phone FROM referrals r JOIN deposits d ON r.user_phone = d.user_phone WHERE r.referred_by IN (SELECT user_phone FROM referrals WHERE referred_by IN (SELECT user_phone FROM referrals WHERE referred_by = ? AND level = 1) AND level = 2) AND r.level = 3 AND d.is_first_deposit = TRUE");
            $stmt->execute([$user['affiliate_link']]);
            $level3_referrals = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            $level1_bonus = count($level1_referrals) * 1500;
            $level2_bonus = count($level2_referrals) * 700;
            $level3_bonus = count($level3_referrals) * 350;
            $total_bonus = $level1_bonus + $level2_bonus + $level3_bonus;

            $stmt = $this->db->prepare("UPDATE users SET balance = balance + ? WHERE phone = ?");
            $stmt->execute([$total_bonus, $phone]);

            // Récupérer les détails des filleuls
            $level1_details = [];
            if ($level1_referrals) {
                $stmt = $this->db->prepare("SELECT phone, name, (SELECT COUNT(*) FROM deposits WHERE user_phone = users.phone AND is_first_deposit = TRUE AND amount > 0) as is_active FROM users WHERE phone IN (" . implode(',', array_fill(0, count($level1_referrals), '?')) . ")");
                $stmt->execute($level1_referrals);
                $level1_details = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }

            $level2_details = [];
            if ($level2_referrals) {
                $stmt = $this->db->prepare("SELECT phone, name, (SELECT COUNT(*) FROM deposits WHERE user_phone = users.phone AND is_first_deposit = TRUE AND amount > 0) as is_active FROM users WHERE phone IN (" . implode(',', array_fill(0, count($level2_referrals), '?')) . ")");
                $stmt->execute($level2_referrals);
                $level2_details = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }

            $level3_details = [];
            if ($level3_referrals) {
                $stmt = $this->db->prepare("SELECT phone, name, (SELECT COUNT(*) FROM deposits WHERE user_phone = users.phone AND is_first_deposit = TRUE AND amount > 0) as is_active FROM users WHERE phone IN (" . implode(',', array_fill(0, count($level3_referrals), '?')) . ")");
                $stmt->execute($level3_referrals);
                $level3_details = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }

            // Traitement de la mise à jour
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
                $newName = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
                $newPassword = $_POST['password'];
                $confirmPassword = $_POST['confirm_password'];

                if ($newPassword === $confirmPassword && !empty($newPassword)) {
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $this->db->prepare("UPDATE users SET name = ?, password = ? WHERE phone = ?");
                    $stmt->execute([$newName, $hashedPassword, $phone]);
                    $_SESSION['success'] = "Profil mis à jour avec succès.";
                } elseif (empty($newPassword) || empty($confirmPassword)) {
                    $stmt = $this->db->prepare("UPDATE users SET name = ? WHERE phone = ?");
                    $stmt->execute([$newName, $phone]);
                    $_SESSION['success'] = "Nom mis à jour avec succès.";
                } else {
                    $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
                }
                header("Location: ?action=account-unique");
                exit();
            }

            $content = '
                <div style="padding: 1.5rem 0; min-height: calc(100vh - 60px); margin-top: 60px; background: linear-gradient(135deg, #f7fafc, #e6f0fa); color: #2d3748;">
                    <div style="max-width: 80rem; margin-left: auto; margin-right: auto; padding-left: 0.75rem; padding-right: 0.75rem; @media (min-width: 768px) { padding-left: 1.5rem; padding-right: 1.5rem; }}">
                        <div style="background: linear-gradient(135deg, #ffffff, #f0f4f8); overflow: hidden; box-shadow: 0 8px 12px rgba(0,0,0,0.15); border-radius: 0.75rem; border: 2px solid #3b82f6; padding: 1rem; @media (min-width: 768px) { padding: 1.5rem; }}">
                            <h2 style="font-size: 1.5rem; font-weight: 700; color: #f59e0b; margin-bottom: 1rem; text-align: center; @media (min-width: 768px) { font-size: 1.75rem; text-align: left; }}">
                                Mon Profil
                            </h2>
                            ' . (isset($_SESSION['success']) ? '<div style="background: #10b981; color: #ffffff; padding: 0.75rem; border-radius: 0.375rem; margin-bottom: 1rem; text-align: center; animation: fadeIn 0.5s;">' . htmlspecialchars($_SESSION['success']) . '</div>' : '') . '
                            ' . (isset($_SESSION['error']) ? '<div style="background: #f56565; color: #ffffff; padding: 0.75rem; border-radius: 0.375rem; margin-bottom: 1rem; text-align: center; animation: fadeIn 0.5s;">' . htmlspecialchars($_SESSION['error']) . '</div>' : '') . '

                            <div style="display: flex; flex-direction: column; gap: 1.5rem; @media (min-width: 768px) { flex-direction: row; }">
                                <div style="flex: 1;">
                                    <h3 style="font-size: 1.125rem; font-weight: 600; color: #3b82f6; margin-bottom: 0.75rem;">Informations Personnelles</h3>
                                    <form method="POST" style="overflow-x: auto;">
                                        <table style="width: 100%; min-width: 300px; background: #edf2f6; border-radius: 0.5rem; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                                            <tbody style="font-size: 0.875rem; color: #718096;">
                                                <tr>
                                                    <td style="padding: 0.5rem 1rem; border-bottom: 1px solid #e2e8f0;">Nom</td>
                                                    <td style="padding: 0.5rem 1rem; text-align: right;"><input type="text" name="name" value="' . htmlspecialchars($user['name']) . '" style="border: 1px solid #e2e8f0; border-radius: 0.25rem; padding: 0.25rem; width: 100%; max-width: 200px;"></td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 0.5rem 1rem; border-bottom: 1px solid #e2e8f0;">Téléphone</td>
                                                    <td style="padding: 0.5rem 1rem; text-align: right; color: #f59e0b; font-weight: 600;">' . htmlspecialchars($user['phone']) . ' (Non modifiable - Utilisé pour les transactions)</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 0.5rem 1rem; border-bottom: 1px solid #e2e8f0;">Nouveau Mot de Passe</td>
                                                    <td style="padding: 0.5rem 1rem; text-align: right;"><input type="password" name="password" style="border: 1px solid #e2e8f0; border-radius: 0.25rem; padding: 0.25rem; width: 100%; max-width: 200px;"></td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 0.5rem 1rem;">Confirmer Mot de Passe</td>
                                                    <td style="padding: 0.5rem 1rem; text-align: right;"><input type="password" name="confirm_password" style="border: 1px solid #e2e8f0; border-radius: 0.25rem; padding: 0.25rem; width: 100%; max-width: 200px;"></td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2" style="padding: 0.5rem 1rem; text-align: center;"><button type="submit" name="update_profile" style="background: #3b82f6; color: #ffffff; border: none; border-radius: 0.25rem; padding: 0.5rem 1rem; cursor: pointer;">Mettre à jour</button></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </form>
                                </div>
                                <div style="flex: 1;">
                                    <h3 style="font-size: 1.125rem; font-weight: 600; color: #3b82f6; margin-bottom: 0.75rem;">Statistiques Avancées</h3>
                                    <div style="overflow-x: auto;">
                                        <table style="width: 100%; min-width: 300px; background: #edf2f6; border-radius: 0.5rem; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                                            <tbody style="font-size: 0.875rem; color: #718096;">
                                                <tr>
                                                    <td style="padding: 0.5rem 1rem; border-bottom: 1px solid #e2e8f0;">Dépôts Totaux</td>
                                                    <td style="padding: 0.5rem 1rem; text-align: right; color: #10b981; font-weight: 600;">' . $totalDeposits . ' FCFA (' . $depositCount . ' transactions)</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 0.5rem 1rem; border-bottom: 1px solid #e2e8f0;">Retraits Totaux</td>
                                                    <td style="padding: 0.5rem 1rem; text-align: right; color: #f56565; font-weight: 600;">' . $totalWithdrawals . ' FCFA (' . $withdrawalCount . ' transactions)</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 0.5rem 1rem;">Balance Nette</td>
                                                    <td style="padding: 0.5rem 1rem; text-align: right; color: #10b981; font-weight: 700; font-size: 1rem;">' . ($totalDeposits - $totalWithdrawals) . ' FCFA</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 0.5rem 1rem; border-bottom: 1px solid #e2e8f0;">Revenus Parrainage Niveau 1</td>
                                                    <td style="padding: 0.5rem 1rem; text-align: right; color: #f59e0b; font-weight: 600;">' . $level1_bonus . ' FCFA (' . count($level1_referrals) . ' filleuls)</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 0.5rem 1rem; border-bottom: 1px solid #e2e8f0;">Revenus Parrainage Niveau 2</td>
                                                    <td style="padding: 0.5rem 1rem; text-align: right; color: #f59e0b; font-weight: 600;">' . $level2_bonus . ' FCFA (' . count($level2_referrals) . ' filleuls)</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 0.5rem 1rem;">Revenus Parrainage Niveau 3</td>
                                                    <td style="padding: 0.5rem 1rem; text-align: right; color: #f59e0b; font-weight: 600;">' . $level3_bonus . ' FCFA (' . count($level3_referrals) . ' filleuls)</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 0.5rem 1rem; border-top: 1px solid #e2e8f0;">Revenus Totale Parrainage</td>
                                                    <td style="padding: 0.5rem 1rem; text-align: right; color: #10b981; font-weight: 700; font-size: 1rem;">' . $total_bonus . ' FCFA</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div style="margin-top: 1.5rem;">
                                <h3 style="font-size: 1.125rem; font-weight: 600; color: #3b82f6; margin-bottom: 0.75rem;">Historique Récent (5 dernières transactions)</h3>
                                <div style="overflow-x: auto;">
                                    <div style="background: #edf2f6; border-radius: 0.5rem; box-shadow: 0 4px 8px rgba(0,0,0,0.1); padding: 1rem; min-width: 300px;">
                                        ' . ($transactions ? implode('', array_map(function($t) {
                                            return '<div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #e2e8f0; color: #374151; font-size: 0.875rem; @media (max-width: 768px) { flex-direction: column; text-align: center; gap: 0.25rem; }">'
                                                . '<span>' . htmlspecialchars($t['type']) . '</span>'
                                                . '<span>' . htmlspecialchars($t['amount']) . ' FCFA</span>'
                                                . '<span>' . htmlspecialchars(date('d/m/Y H:i', strtotime($t['created_at']))) . '</span>'
                                                . '</div>';
                                        }, $transactions)) : '<p style="color: #718096; text-align: center; font-size: 0.875rem;">Aucune transaction récente.</p>') . '
                                    </div>
                                </div>
                            </div>

                            <div style="margin-top: 1.5rem;">
                                <h3 style="font-size: 1.125rem; font-weight: 600; color: #3b82f6; margin-bottom: 0.75rem;">Détails des Transactions</h3>
                                <div style="overflow-x: auto;">
                                    <table style="width: 100%; min-width: 300px; background: #edf2f6; border-radius: 0.5rem; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                                        <tbody style="font-size: 0.875rem; color: #718096;">
                                            <tr>
                                                <td style="padding: 0.5rem 1rem; border-bottom: 1px solid #e2e8f0;">Nombre de Dépôts</td>
                                                <td style="padding: 0.5rem 1rem; text-align: right; color: #10b981; font-weight: 600;">' . $depositCount . '</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 0.5rem 1rem; border-bottom: 1px solid #e2e8f0;">Nombre de Retraits</td>
                                                <td style="padding: 0.5rem 1rem; text-align: right; color: #f56565; font-weight: 600;">' . $withdrawalCount . '</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 0.5rem 1rem;">Dernière Activité</td>
                                                <td style="padding: 0.5rem 1rem; text-align: right; color: #f59e0b; font-weight: 600;">' . ($transactions ? htmlspecialchars(date('d/m/Y H:i', strtotime($transactions[0]['created_at']))) : 'Aucune') . '</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Boutons d\'action -->
                            <div style="margin-top: 1.5rem; display: flex; flex-wrap: wrap; gap: 1rem; justify-content: center; @media (min-width: 768px) { justify-content: flex-start; }">
                                <a href="?action=deposit" style="background: linear-gradient(90deg, #10b981, #34d399); color: #ffffff; padding: 0.75rem 1.5rem; border-radius: 0.5rem; text-decoration: none; font-weight: 600; transition: transform 0.3s;">Dépôt</a>
                                <a href="?action=withdraw" style="background: linear-gradient(90deg, #f56565, #ef4444); color: #ffffff; padding: 0.75rem 1.5rem; border-radius: 0.5rem; text-decoration: none; font-weight: 600; transition: transform 0.3s;">Retrait</a>
                                <a href="?action=logout" style="background: linear-gradient(90deg, #a0aec0, #718096); color: #ffffff; padding: 0.75rem 1.5rem; border-radius: 0.5rem; text-decoration: none; font-weight: 600; transition: transform 0.3s;">Déconnexion</a>
                            </div>
                        </div>

                        <div style="background: linear-gradient(135deg, #ffffff, #f0f4f8); border-radius: 0.75rem; box-shadow: 0 8px 12px rgba(0,0,0,0.15); padding: 1rem; margin-top: 1.5rem; border: 2px solid #3b82f6; @media (min-width: 768px) { padding: 1.5rem; }}">
                            <h3 style="font-size: 1.25rem; font-weight: 700; color: #3b82f6; margin-bottom: 1rem; text-align: center; @media (min-width: 768px) { text-align: left; }}">
                                Nous Contacter
                            </h3>
                            <div style="display: flex; flex-wrap: wrap; gap: 1rem; justify-content: center; @media (min-width: 768px) { justify-content: flex-start; }}">
                                <a href="https://wa.me/1234567890" target="_blank" style="display: flex; align-items: center; background: #25d366; color: #ffffff; padding: 0.75rem 1rem; border-radius: 0.5rem; text-decoration: none; font-weight: 600; transition: transform 0.3s;">
                                    <svg style="width: 1.25rem; height: 1.25rem; margin-right: 0.5rem;" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12.04 2c-5.47 0-9.91 4.44-9.91 9.91 0 2.36.87 4.53 2.3 6.18L2.38 21.76c-.14.14-.22.33-.22.53s.08.39.22.53l.53.53c.14.14.33.22.53.22s.39-.08.53-.22l3.66-3.66c1.65 1.43 3.82 2.3 6.18 2.3 5.47 0 9.91-4.44 9.91-9.91S17.51 2 12.04 2zm4.5 10.48c-.05-.28-.28-.48-.55-.48-.1 0-.19.03-.27.08-.07.05-1.76 1.23-4.04 1.23-1.23 0-2.47-.58-3.23-1.35-.78-.78-1.3-1.98-1.35-3.23 0-.07.01-.14.03-.2.04-.04.03-.09.02-.14-.02-.26-.24-.46-.5-.46h-.53c-.28 0-.51.23-.51.51 0 2.36 1.93 4.28 4.28 4.28.64 0 1.27-.16 1.83-.46.33-.18.7-.27 1.08-.27.31 0 .61.06.89.18.38.16.58.51.58.89v.53c0 .27-.2.49-.46.5z"/>
                                    </svg>
                                    WhatsApp
                                </a>
                                <a href="https://t.me/EagleCashSupport" target="_blank" style="display: flex; align-items: center; background: #0088cc; color: #ffffff; padding: 0.75rem 1rem; border-radius: 0.5rem; text-decoration: none; font-weight: 600; transition: transform 0.3s;">
                                    <svg style="width: 1.25rem; height: 1.25rem; margin-right: 0.5rem;" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M9.78 18.65l.28-4.23 7.68-7.68c.19-.18.19-.45 0-.63L16.95.3c-.18-.19-.45-.19-.63 0L8.39 8.04c-.09.09-.14.21-.14.33l-.3 4.24a.68.68 0 00.83.83l4.25-.3c.12 0 .24-.05.33-.14l7.71-7.71c.19-.18.19-.45 0-.63L18.33.3c-.18-.19-.45-.19-.63 0l-7.71 7.71-4.24.3c-.26 0-.51.1-.7.29-.39.39-.39 1.02 0 1.41l4.44 4.44-.3 4.24c-.02.24.06.48.23.65.17.17.41.25.65.23l4.24-.3 4.44 4.44c.39.39 1.02.39 1.41 0 .19-.19.29-.44.29-.7l.3-4.24 7.68-7.68c.18-.18.18-.45 0-.63L18.95.3c-.18-.19-.45-.19-.63 0L9.78 18.65z"/>
                                    </svg>
                                    Telegram
                                </a>
                                <a href="https://facebook.com/EagleCashOfficial" target="_blank" style="display: flex; align-items: center; background: #3b5998; color: #ffffff; padding: 0.75rem 1rem; border-radius: 0.5rem; text-decoration: none; font-weight: 600; transition: transform 0.3s;">
                                    <svg style="width: 1.25rem; height: 1.25rem; margin-right: 0.5rem;" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 2C6.48 2 2 6.48 2 12c0 4.84 3.44 8.87 8 9.8v-6.9H7.5V12h2.5V9.5c0-2.43 1.47-3.75 3.72-3.75 1.05 0 2.15.19 2.15.19v2.36h-1.21c-1.19 0-1.56.74-1.56 1.5V12h2.65l-.43 2.9H13v6.9c4.56-.93 8-4.96 8-9.8 0-5.52-4.48-10-10-10z"/>
                                    </svg>
                                    Facebook
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <style>
                    @keyframes fadeIn {
                        from { opacity: 0; }
                        to { opacity: 1; }
                    }
                    @keyframes pulse {
                        0% { transform: scale(1); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
                        50% { transform: scale(1.05); box-shadow: 0 6px 12px rgba(0,0,0,0.2); }
                        100% { transform: scale(1); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
                    }
                    a:hover {
                        transform: scale(1.05) !important;
                        box-shadow: 0 6px 12px rgba(0,0,0,0.2) !important;
                    }
                    @media (max-width: 768px) {
                        table td { font-size: 0.75rem; padding: 0.25rem 0.5rem; }
                        h3 { font-size: 1rem; }
                        .history-item { flex-direction: column; text-align: center; gap: 0.25rem; }
                        a { font-size: 0.875rem; padding: 0.5rem 0.75rem; }
                    }
                </style>';
        } else {
            $content = '<div style="text-align: center; padding: 1.5rem; background-color: #ffffff; border-radius: 0.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 60px;"><p style="color: #4a5568;">Non connecté.</p></div>';
        }
        unset($_SESSION['success'], $_SESSION['error']); // Nettoyer les messages après affichage
    }
}