<?php
namespace App\Controllers;

use Config\Database;

class AffiliationController {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function affiliationGuide() {
        global $content, $title;
        $title = "Guide Affiliation";
        $phone = $_SESSION['user_phone'] ?? null;

        if ($phone) {
            $content = '
                <div style="padding: 1.5rem 0; min-height: calc(100vh - 60px); margin-top: 60px; background: linear-gradient(135deg, #f7fafc, #e6f0fa); color: #2d3748;">
                    <div style="max-width: 80rem; margin-left: auto; margin-right: auto; padding-left: 0.75rem; padding-right: 0.75rem; @media (min-width: 768px) { padding-left: 1.5rem; padding-right: 1.5rem; }}">
                        <div style="background: linear-gradient(135deg, #ffffff, #f0f4f8); overflow: hidden; box-shadow: 0 8px 12px rgba(0,0,0,0.15); border-radius: 0.75rem; border: 2px solid #f59e0b; padding: 1rem; @media (min-width: 768px) { padding: 1.5rem; }">
                            <h2 style="font-size: 1.5rem; font-weight: 700; color: #f59e0b; margin-bottom: 1rem; text-align: center; @media (min-width: 768px) { font-size: 1.75rem; text-align: left; animation: fadeIn 0.5s; }}">
                                Guide Affiliation EagleCash
                            </h2>

                            <div style="margin-top: 1.5rem; background: linear-gradient(135deg, #edf2f7, #e6f0fa); padding: 1.5rem; border-radius: 0.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); animation: fadeIn 0.5s;">
                                <h3 style="font-size: 1.25rem; font-weight: 600; color: #2d3748; margin-bottom: 0.75rem;">Comment ça marche ?</h3>
                                <p style="font-size: 0.875rem; color: #718096; line-height: 1.5;">L\'affiliation EagleCash vous permet de gagner des revenus en invitant de nouveaux utilisateurs. Voici les étapes simples :</p>
                                <ol style="font-size: 0.875rem; color: #718096; padding-left: 1.5rem; margin-top: 0.5rem; line-height: 1.5;">
                                    <li style="margin-bottom: 0.5rem;">Connectez-vous à votre profil pour récupérer votre <strong>lien unique d\'affiliation</strong>.</li>
                                    <li style="margin-bottom: 0.5rem;">Partagez ce lien avec vos amis via email, WhatsApp, ou réseaux sociaux.</li>
                                    <li style="margin-bottom: 0.5rem;">Lorsque quelqu\'un s\'inscrit via votre lien et effectue son premier dépôt, vous gagnez des commissions !</li>
                                </ol>
                            </div>

                            <div style="margin-top: 1.5rem; background: linear-gradient(135deg, #fefcbf, #fef9c3); padding: 1.5rem; border-radius: 0.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); animation: fadeIn 0.5s;">
                                <h3 style="font-size: 1.25rem; font-weight: 600; color: #d97706; margin-bottom: 0.75rem;">Structure des Commissions</h3>
                                <ul style="font-size: 0.875rem; color: #744210; padding-left: 1.5rem; line-height: 1.5;">
                                    <li style="margin-bottom: 0.5rem;"><strong>Niveau 1 :</strong> 10% du premier dépôt de chaque parrain direct.</li>
                                    <li style="margin-bottom: 0.5rem;"><strong>Niveau 2 :</strong> 5% du premier dépôt des parrainés par vos filleuls directs.</li>
                                    <li><strong>Niveau 3 :</strong> 2% du premier dépôt des parrainés au niveau suivant.</li>
                                </ul>
                                <p style="font-size: 0.875rem; color: #744210; margin-top: 0.5rem;">Limite : Jusqu\'à 3 niveaux de parrainage. Vos gains sont crédités automatiquement sur votre solde EagleCash.</p>
                            </div>

                            <div style="margin-top: 1.5rem; background: linear-gradient(135deg, #edf2f7, #e6f0fa); padding: 1.5rem; border-radius: 0.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); animation: fadeIn 0.5s;">
                                <h3 style="font-size: 1.25rem; font-weight: 600; color: #2d3748; margin-bottom: 0.75rem;">Conseils pour Réussir</h3>
                                <ul style="font-size: 0.875rem; color: #718096; padding-left: 1.5rem; line-height: 1.5;">
                                    <li style="margin-bottom: 0.5rem;">Personnalisez votre lien avec un message attractif.</li>
                                    <li style="margin-bottom: 0.5rem;">Ciblez vos contacts les plus actifs sur les réseaux sociaux.</li>
                                    <li>Suivez vos gains dans votre tableau de bord.</li>
                                </ul>
                            </div>

                            <div style="margin-top: 1.5rem; text-align: center; animation: fadeIn 0.5s;">
                                <a href="?action=account-unique" style="display: inline-block; margin-top: 1rem; background: linear-gradient(90deg, #3b82f6, #2563eb); color: #ffffff; border-radius: 0.5rem; box-shadow: 0 4px 8px rgba(59,130,246,0.3); padding: 0.75rem 1.5rem; text-decoration: none; font-weight: 600; transition: transform 0.3s; @media (max-width: 768px) { font-size: 0.875rem; padding: 0.5rem 1rem; }">
                                    Retour à Mon Profil
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <style>
                    @keyframes fadeIn {
                        from { opacity: 0; transform: translateY(10px); }
                        to { opacity: 1; transform: translateY(0); }
                    }
                    a:hover {
                        transform: scale(1.05);
                        box-shadow: 0 6px 12px rgba(59,130,246,0.4);
                    }
                    @media (max-width: 768px) {
                        h2 { font-size: 1.25rem; }
                        h3 { font-size: 1rem; }
                    }
                </style>';
        } else {
            $content = '<div style="text-align: center; padding: 1.5rem; background-color: #ffffff; border-radius: 0.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 60px;"><p style="color: #4a5568;">Connectez-vous pour accéder au guide.</p></div>';
        }
    }
}