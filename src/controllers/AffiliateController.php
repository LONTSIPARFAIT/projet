<?php
namespace App\Controllers;

use Config\Database;

class AffiliateController {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function index() {
        global $content, $title;
        $title = "Affiliation";
        $phone = $_SESSION['user_phone'] ?? null;
        $user = null;
        $level1_bonus = 0;
        $level2_bonus = 0;
        $level3_bonus = 0;
        $affiliate_link = '';

        if ($phone) {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE phone = ? LIMIT 1");
            $stmt->execute([$phone]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($user && $user['affiliate_link']) {
                $affiliate_link = "http://localhost/mes-projets/eaglecash-poo/public/?action=register" . $user['phone'];
            } elseif ($user) {
                $affiliate_link = "http://localhost/mes-projets/eaglecash-poo/public/?action=register" . $user['phone'];
                $stmt = $this->db->prepare("UPDATE users SET affiliate_link = ? WHERE phone = ?");
                $stmt->execute([$affiliate_link, $phone]);
            }

            $stmt = $this->db->prepare("SELECT COUNT(DISTINCT r.user_phone) FROM referrals r JOIN deposits d ON r.user_phone = d.user_phone WHERE r.referred_by = ? AND r.level = 1 AND d.amount >= 3000 AND d.is_pending = 0");
            $stmt->execute([$phone]);
            $level1_count = $stmt->fetchColumn();
            $level1_bonus = $level1_count * 1500;

            $stmt = $this->db->prepare("SELECT COUNT(DISTINCT r.user_phone) FROM referrals r JOIN deposits d ON r.user_phone = d.user_phone WHERE r.referred_by IN (SELECT user_phone FROM referrals WHERE referred_by = ? AND level = 1) AND r.level = 2 AND d.amount >= 3000 AND d.is_pending = 0");
            $stmt->execute([$phone]);
            $level2_count = $stmt->fetchColumn();
            $level2_bonus = $level2_count * 700;

            $stmt = $this->db->prepare("SELECT COUNT(DISTINCT r.user_phone) FROM referrals r JOIN deposits d ON r.user_phone = d.user_phone WHERE r.referred_by IN (SELECT user_phone FROM referrals WHERE referred_by IN (SELECT user_phone FROM referrals WHERE referred_by = ? AND level = 1) AND level = 2) AND r.level = 3 AND d.amount >= 3000 AND d.is_pending = 0");
            $stmt->execute([$phone]);
            $level3_count = $stmt->fetchColumn();
            $level3_bonus = $level3_count * 350;

            $total_bonus = $level1_bonus + $level2_bonus + $level3_bonus;

            $stmt = $this->db->prepare("UPDATE users SET balance = balance + ? WHERE phone = ?");
            $stmt->execute([$total_bonus - ($user['balance'] ?? 0), $phone]);

            // Vérification et attribution de commission pour les nouveaux filleuls
            $stmt = $this->db->prepare("SELECT r.user_phone, d.amount FROM referrals r LEFT JOIN deposits d ON r.user_phone = d.user_phone WHERE r.referred_by = ? AND r.level = 1 AND d.is_pending = 0");
            $stmt->execute([$phone]);
            $referrals = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($referrals as $referral) {
                if ($referral['amount'] >= 3000 && !$this->hasCommission($referral['user_phone'], $phone)) {
                    $this->addCommission($phone, 1500);
                }
            }

            $level1_referrals = [];
            if ($level1_count > 0) {
                $stmt = $this->db->prepare("SELECT phone, name, (SELECT COUNT(*) FROM deposits WHERE user_phone = users.phone AND amount >= 3000 AND is_pending = 0) as is_active FROM users WHERE phone IN (SELECT user_phone FROM referrals WHERE referred_by = ? AND level = 1)");
                $stmt->execute([$phone]);
                $level1_referrals = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }

            $level2_referrals = [];
            if ($level2_count > 0) {
                $stmt = $this->db->prepare("SELECT phone, name, (SELECT COUNT(*) FROM deposits WHERE user_phone = users.phone AND amount >= 3000 AND is_pending = 0) as is_active FROM users WHERE phone IN (SELECT user_phone FROM referrals WHERE referred_by IN (SELECT user_phone FROM referrals WHERE referred_by = ? AND level = 1) AND level = 2)");
                $stmt->execute([$phone]);
                $level2_referrals = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }

            $level3_referrals = [];
            if ($level3_count > 0) {
                $stmt = $this->db->prepare("SELECT phone, name, (SELECT COUNT(*) FROM deposits WHERE user_phone = users.phone AND amount >= 3000 AND is_pending = 0) as is_active FROM users WHERE phone IN (SELECT user_phone FROM referrals WHERE referred_by IN (SELECT user_phone FROM referrals WHERE referred_by IN (SELECT user_phone FROM referrals WHERE referred_by = ? AND level = 1) AND level = 2) AND level = 3)");
                $stmt->execute([$phone]);
                $level3_referrals = $stmt->fetchAll(\PDO::FETCH_ASSOC);
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
                                Mon Équi
                            </h2>
                            <p style="margin-bottom: 1rem; font-size: 0.875rem; color: #718096; position: relative; z-index: 1;">Suivez vos gains et vos filleuls par niveau de parrainage.</p>
                            ' . (isset($_SESSION['success']) ? '<div style="background: #10b981; color: #ffffff; padding: 0.75rem; border-radius: 0.375rem; margin-bottom: 1rem; text-align: center; animation: fadeIn 0.5s; position: relative; z-index: 1;">' . htmlspecialchars($_SESSION['success']) . '</div>' : '') . '
                            ' . (isset($_SESSION['error']) ? '<div style="background: #f56565; color: #ffffff; padding: 0.75rem; border-radius: 0.375rem; margin-bottom: 1rem; text-align: center; animation: fadeIn 0.5s; position: relative; z-index: 1;">' . htmlspecialchars($_SESSION['error']) . '</div>' : '') . '

                            <div style="margin-bottom: 1.5rem;">
                                <div style="background: #edf2f6; padding: 1rem; border-radius: 0.5rem; box-shadow: 0 4px 8px rgba(0,0,0,0.1); margin-bottom: 1rem;">
                                    <p style="color: #374151; font-weight: 600;">Votre lien de parrainage :</p>
                                    <p style="word-break: break-all; color: #3b82f6; font-weight: 500;"><a href="' . htmlspecialchars($affiliate_link) . '" target="_blank" style="color: #3b82f6; text-decoration: underline;">' . htmlspecialchars($affiliate_link) . '</a></p>
                                    <button onclick="copyToClipboard(\'' . htmlspecialchars($affiliate_link) . '\')" style="background-color: #3b82f6; color: white; padding: 0.5rem 1rem; border: none; border-radius: 0.375rem; cursor: pointer; margin-top: 0.5rem; transition: background-color 0.3s;">Copier le lien</button>
                                </div>
                                <ul style="display: flex; border-bottom: 2px solid #3b82f6; overflow-x: auto; white-space: nowrap; padding-bottom: 0.5rem; list-style: none;" id="tab-nav">
                                    <li style="margin-right: 1rem;"><a class="tab-link" data-tab="1" style="display: inline-block; padding: 0.5rem 1rem; font-weight: 600; color: #3b82f6; border-bottom: 2px solid #3b82f6;" href="#" onclick="showTab(1); return false;"><svg style="margin-right: 0.5rem; width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2"><path d="M12 5v14m-7-7h14" /></svg>Niveau 1 (' . $level1_count . ')</a></li>
                                    <li style="margin-right: 1rem;"><a class="tab-link" data-tab="2" style="display: inline-block; padding: 0.5rem 1rem; font-weight: 600; color: #6b7280; transition: color 0.3s;" href="#" onclick="showTab(2); return false;"><svg style="margin-right: 0.5rem; width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2"><path d="M12 9v10m-5-5h10" /></svg>Niveau 2 (' . $level2_count . ')</a></li>
                                    <li><a class="tab-link" data-tab="3" style="display: inline-block; padding: 0.5rem 1rem; font-weight: 600; color: #6b7280; transition: color 0.3s;" href="#" onclick="showTab(3); return false;"><svg style="margin-right: 0.5rem; width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2"><path d="M12 13v8m-3-4h6" /></svg>Niveau 3 (' . $level3_count . ')</a></li>
                                </ul>
                            </div>

                            <div id="content">
                                <div id="niveau1" class="tab-content" style="margin-bottom: 1.5rem; background-color: #e6f0fa; display: block;">
                                    <div style="background: #edf2f6; padding: 1rem; border-radius: 0.5rem; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                                        <p style="color: #374151; font-weight: 600;">Investissement : <span style="color: #f59e0b;">' . ($level1_bonus / 1500 * 3000) . ' XAF</span></p>
                                        <p style="color: #374151; font-weight: 600;">Gain : <span style="color: #f59e0b;">' . $level1_bonus . ' XAF</span></p>
                                        <div style="margin-top: 1rem; overflow-x: auto;">
                                            <table style="width: 100%; min-width: 300px; background: #ffffff; border-radius: 0.375rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                                <thead style="background: #3b82f6; color: #ffffff;">
                                                    <tr><th style="padding: 0.5rem 1rem;">Nom</th><th style="padding: 0.5rem 1rem;">Téléphone</th><th style="padding: 0.5rem 1rem;">Statut</th></tr>
                                                </thead>
                                                <tbody style="font-size: 0.875rem; color: #374151;">
                                                    ' . (count($level1_referrals) > 0 ? implode('', array_map(function($d) {
                                                        $status = $d['is_active'] > 0 ? 'Actif (Dépôt reçu)' : 'Inactif';
                                                        return '<tr><td style="padding: 0.5rem 1rem; border-bottom: 1px solid #e2e8f0;"><svg style="margin-right: 0.5rem; width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><path d="M5 13l4 4L19 7" /></svg>' . htmlspecialchars($d['name'] ?? 'Inconnu') . '</td><td style="padding: 0.5rem 1rem; border-bottom: 1px solid #e2e8f0;"><a href="#" onclick="showDetails(\'' . htmlspecialchars($d['phone']) . '\')" style="color: #3b82f6; text-decoration: underline;">' . htmlspecialchars($d['phone']) . '</a></td><td style="padding: 0.5rem 1rem; border-bottom: 1px solid #e2e8f0; color: ' . ($d['is_active'] > 0 ? '#10b981' : '#f56565') . '">' . $status . '</td></tr>';
                                                    }, $level1_referrals)) : '<tr><td colspan="3" style="padding: 0.5rem 1rem; text-align: center; color: #718096;">Aucun filleul</td></tr>') . '
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div id="niveau2" class="tab-content hidden" style="margin-bottom: 1.5rem;">
                                    <div style="background: #edf2f6; padding: 1rem; border-radius: 0.5rem; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                                        <p style="color: #374151; font-weight: 600;">Investissement : <span style="color: #f59e0b;">' . ($level2_bonus / 700 * 3000) . ' XAF</span></p>
                                        <p style="color: #374151; font-weight: 600;">Gain : <span style="color: #f59e0b;">' . $level2_bonus . ' XAF</span></p>
                                        <div style="margin-top: 1rem; overflow-x: auto;">
                                            <table style="width: 100%; min-width: 300px; background: #ffffff; border-radius: 0.375rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                                <thead style="background: #3b82f6; color: #ffffff;">
                                                    <tr><th style="padding: 0.5rem 1rem;">Nom</th><th style="padding: 0.5rem 1rem;">Téléphone</th><th style="padding: 0.5rem 1rem;">Statut</th></tr>
                                                </thead>
                                                <tbody style="font-size: 0.875rem; color: #374151;">
                                                    ' . (count($level2_referrals) > 0 ? implode('', array_map(function($d) {
                                                        $status = $d['is_active'] > 0 ? 'Actif (Dépôt reçu)' : 'Inactif';
                                                        return '<tr><td style="padding: 0.5rem 1rem; border-bottom: 1px solid #e2e8f0;"><svg style="margin-right: 0.5rem; width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><path d="M5 13l4 4L19 7" /></svg>' . htmlspecialchars($d['name'] ?? 'Inconnu') . '</td><td style="padding: 0.5rem 1rem; border-bottom: 1px solid #e2e8f0;"><a href="#" onclick="showDetails(\'' . htmlspecialchars($d['phone']) . '\')" style="color: #3b82f6; text-decoration: underline;">' . htmlspecialchars($d['phone']) . '</a></td><td style="padding: 0.5rem 1rem; border-bottom: 1px solid #e2e8f0; color: ' . ($d['is_active'] > 0 ? '#10b981' : '#f56565') . '">' . $status . '</td></tr>';
                                                    }, $level2_referrals)) : '<tr><td colspan="3" style="padding: 0.5rem 1rem; text-align: center; color: #718096;">Aucun filleul</td></tr>') . '
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div id="niveau3" class="tab-content hidden" style="margin-bottom: 1.5rem;">
                                    <div style="background: #edf2f6; padding: 1rem; border-radius: 0.5rem; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                                        <p style="color: #374151; font-weight: 600;">Investissement : <span style="color: #f59e0b;">' . ($level3_bonus / 350 * 3000) . ' XAF</span></p>
                                        <p style="color: #374151; font-weight: 600;">Gain : <span style="color: #f59e0b;">' . $level3_bonus . ' XAF</span></p>
                                        <div style="margin-top: 1rem; overflow-x: auto;">
                                            <table style="width: 100%; min-width: 300px; background: #ffffff; border-radius: 0.375rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                                <thead style="background: #3b82f6; color: #ffffff;">
                                                    <tr><th style="padding: 0.5rem 1rem;">Nom</th><th style="padding: 0.5rem 1rem;">Téléphone</th><th style="padding: 0.5rem 1rem;">Statut</th></tr>
                                                </thead>
                                                <tbody style="font-size: 0.875rem; color: #374151;">
                                                    ' . (count($level3_referrals) > 0 ? implode('', array_map(function($d) {
                                                        $status = $d['is_active'] > 0 ? 'Actif (Dépôt reçu)' : 'Inactif';
                                                        return '<tr><td style="padding: 0.5rem 1rem; border-bottom: 1px solid #e2e8f0;"><svg style="margin-right: 0.5rem; width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><path d="M5 13l4 4L19 7" /></svg>' . htmlspecialchars($d['name'] ?? 'Inconnu') . '</td><td style="padding: 0.5rem 1rem; border-bottom: 1px solid #e2e8f0;"><a href="#" onclick="showDetails(\'' . htmlspecialchars($d['phone']) . '\')" style="color: #3b82f6; text-decoration: underline;">' . htmlspecialchars($d['phone']) . '</a></td><td style="padding: 0.5rem 1rem; border-bottom: 1px solid #e2e8f0; color: ' . ($d['is_active'] > 0 ? '#10b981' : '#f56565') . '">' . $status . '</td></tr>';
                                                    }, $level3_referrals)) : '<tr><td colspan="3" style="padding: 0.5rem 1rem; text-align: center; color: #718096;">Aucun filleul</td></tr>') . '
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div style="background: linear-gradient(135deg, #fefcbf, #fef9c3); border-radius: 0.75rem; box-shadow: 0 8px 12px rgba(0,0,0,0.15); padding: 1rem; margin-top: 1.5rem; border: 2px solid #f59e0b; position: relative;">
                                <svg style="position: absolute; top: -30px; right: -30px; opacity: 0.05;" width="100" height="100" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="1">
                                    <path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z" />
                                </svg>
                                <h2 style="font-size: 1.25rem; font-weight: 700; color: #d97706; margin-bottom: 1rem; text-align: center; @media (min-width: 768px) { text-align: left; } position: relative; z-index: 1;">
                                    Modes de Paiement & Dépôts
                                </h2>
                                <div style="margin-top: 1rem; display: flex; flex-direction: column; gap: 0.75rem; position: relative; z-index: 1;">
                                    <p style="color: #374151;">Choisissez parmi ces options populaires dans votre pays :</p>
                                    <ul style="list-style: none; padding-left: 0;">
                                        <li style="margin-bottom: 0.5rem;"><strong>Cameroun :</strong> Orange Money, MTN Mobile Money, Visa, dépôts via kiosques Orange/MTN.</li>
                                        <li style="margin-bottom: 0.5rem;"><strong>Côte d’Ivoire :</strong> Orange Money, MTN Mobile Money, Visa, dépôts en espèces dans les agences Orange.</li>
                                        <li style="margin-bottom: 0.5rem;"><strong>Burkina Faso :</strong> Orange Money, Moov Money, Visa, dépôts via plus de 30 000 points Orange.</li>
                                        <li style="margin-bottom: 0.5rem;"><strong>Gabon :</strong> Orange Money, Airtel Money, Visa, dépôts dans les agences Orange.</li>
                                        <li style="margin-bottom: 0.5rem;"><strong>Bénin :</strong> Orange Money, MTN Mobile Money, Visa, dépôts en espèces chez les agents.</li>
                                        <li style="margin-bottom: 0.5rem;"><strong>Kenya :</strong> M-Pesa, Airtel Money, Visa, dépôts via agents M-Pesa/Airtel.</li>
                                        <li style="margin-bottom: 0.5rem;"><strong>Sénégal :</strong> Orange Money, Wave, Visa, dépôts dans les boutiques Orange.</li>
                                        <li style="margin-bottom: 0.5rem;"><strong>RDC :</strong> Orange Money, M-Pesa, Airtel Money, dépôts via kiosques Orange/M-Pesa.</li>
                                    </ul>
                                    <p style="color: #374151;">Dépôts rapides et sécurisés disponibles 24/7 via les applications ou agents locaux.</p>
                                </div>
                            </div>
                        </div>

                        <div style="background: linear-gradient(135deg, #3b82f6, #60a5fa); color: #ffffff; border-radius: 0.75rem; box-shadow: 0 8px 12px rgba(0,0,0,0.15); padding: 1rem; margin-top: 1.5rem; @media (min-width: 768px) { padding: 1.5rem; } position: relative;">
                            <svg style="position: absolute; bottom: -40px; left: -40px; opacity: 0.05;" width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="1">
                                <path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zM5 12h14" />
                            </svg>
                            <h2 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem; position: relative; z-index: 1;">Astuces pour Maximiser vos Gains</h2>
                            <div style="margin-top: 1rem; padding-left: 0; color: #e5e7eb; font-size: 0.875rem; line-height: 1.5; position: relative; z-index: 1;">
                                <div>Invitez vos amis pour booster votre équipe.</div>
                                <div>Suivez les performances de vos filleuls.</div>
                                <div>Encouragez les dépôts pour débloquer des bonus.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <style>
                    .hidden { display: none; }
                    .active-tab { background-color: #d1fae5; border: 2px solid #10b981; }
                    .active-link { background-color: #d1fae5; border-bottom: 2px solid #10b981; color: #10b981 !important; }
                    .modal {
                        display: none;
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background-color: rgba(0, 0, 0, 0.5);
                        z-index: 1000;
                    }
                    .modal-content {
                        background-color: #fff;
                        margin: 15% auto;
                        padding: 1rem;
                        border-radius: 0.5rem;
                        width: 90%;
                        max-width: 400px;
                        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
                        color: #374151;
                    }
                    .close {
                        float: right;
                        font-size: 1.5rem;
                        font-weight: bold;
                        cursor: pointer;
                    }
                    @keyframes fadeIn {
                        from { opacity: 0; }
                        to { opacity: 1; }
                    }
                    @keyframes pulse {
                        0% { transform: scale(1); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
                        50% { transform: scale(1.05); box-shadow: 0 6px 12px rgba(0,0,0,0.2); }
                        100% { transform: scale(1); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
                    }
                    a:hover { color: #60a5fa; }
                    button:hover { background-color: #d1d5db; }
                    @media (max-width: 768px) {
                        h2 { font-size: 1.25rem; }
                        h3 { font-size: 1rem; }
                        table td { font-size: 0.75rem; padding: 0.25rem 0.5rem; }
                        ul { padding-left: 1rem; }
                    }
                </style>

                <script>
                    function showTab(niveau) {
                        const tabs = document.getElementsByClassName(\'tab-content\');
                        for (let tab of tabs) {
                            tab.style.display = \'none\';
                            tab.classList.remove(\'active-tab\');
                        }
                        const links = document.getElementsByClassName(\'tab-link\');
                        for (let link of links) {
                            link.classList.remove(\'active-link\');
                        }
                        document.getElementById(\'niveau\' + niveau).style.display = \'block\';
                        document.getElementById(\'niveau\' + niveau).classList.add(\'active-tab\');
                        document.querySelector(\'[data-tab="\' + niveau + \'"]\').classList.add(\'active-link\');
                    }
                    document.addEventListener(\'DOMContentLoaded\', () => showTab(1));
                    function copyToClipboard(text) {
                        navigator.clipboard.writeText(text);
                        alert(\'Copié dans le presse-papiers !\');
                    }
                    function showDetails(phone) {
                        const details = {
                            \'phone\': phone,
                            \'name\': \'Inconnu\',
                            \'deposits\': [\'1000 XAF\', \'500 XAF\'],
                            \'status\': \'Actif\',
                            \'lastLogin\': \'2025-06-25\'
                        };
                        const modal = document.createElement(\'div\');
                        modal.className = \'modal\';
                        modal.innerHTML = `
                            <div class="modal-content">
                                <span class="close" onclick="this.parentElement.parentElement.remove()">×</span>
                                <h3>Détails du filleul</h3>
                                <p><strong>Nom :</strong> ${details.name}</p>
                                <p><strong>Téléphone :</strong> ${details.phone}</p>
                                <p><strong>Dépôts :</strong> ${details.deposits.join(\', \') || \'Aucun\'}</p>
                                <p><strong>Statut :</strong> ${details.status}</p>
                                <p><strong>Dernière connexion :</strong> ${details.lastLogin}</p>
                            </div>
                        `;
                        document.body.appendChild(modal);
                    }
                </script>';
        } else {
            $content = '<div style="text-align: center; padding: 1.5rem; background-color: #ffffff; border-radius: 0.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 60px;"><p style="color: #4a5568;">Connectez-vous pour voir vos affiliations.</p></div>';
        }
    }

    private function hasCommission($user_phone, $referred_by) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM commissions WHERE user_phone = ? AND referred_by = ?");
        $stmt->execute([$user_phone, $referred_by]);
        return $stmt->fetchColumn() > 0;
    }

    private function addCommission($referred_by, $amount) {
        $stmt = $this->db->prepare("INSERT INTO commissions (user_phone, referred_by, amount, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$referred_by, $referred_by, $amount]);
        $stmt = $this->db->prepare("UPDATE users SET balance = balance + ? WHERE phone = ?");
        $stmt->execute([$amount, $referred_by]);
    }
}