<?php
namespace App\Controllers;

use Config\Database;

class FormationController {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function index() {
        global $content, $title;
        $title = "Formation";
        $phone = $_SESSION['user_phone'] ?? null;

        if ($phone) {
            $content = '
                <div style="padding: 1.5rem 0; min-height: calc(100vh - 60px); margin-top: 60px; background: linear-gradient(135deg, #f7fafc, #e6f0fa); color: #2d3748;">
                    <div style="max-width: 80rem; margin-left: auto; margin-right: auto; padding-left: 0.75rem; padding-right: 0.75rem; @media (min-width: 768px) { padding-left: 1.5rem; padding-right: 1.5rem; }}">
                        <div style="background: linear-gradient(135deg, #ffffff, #f0f4f8); overflow: hidden; box-shadow: 0 8px 12px rgba(0,0,0,0.15); border-radius: 0.75rem; border: 2px solid #3b82f6; padding: 1rem; @media (min-width: 768px) { padding: 1.5rem; }}">
                            <h2 style="font-size: 1.5rem; font-weight: 700; color: #f59e0b; margin-bottom: 1rem; text-align: center; @media (min-width: 768px) { font-size: 1.75rem; text-align: left; animation: fadeIn 0.5s; }}">
                                Formation
                            </h2>

                            <!-- À propos des formations -->
                            <div style="margin-top: 1.5rem; background: linear-gradient(135deg, #edf2f7, #e6f0fa); padding: 1.5rem; border-radius: 0.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); animation: fadeIn 0.5s;">
                                <h3 style="font-size: 1.25rem; font-weight: 600; color: #2d3748; margin-bottom: 0.75rem;">À propos des Formations</h3>
                                <p style="font-size: 0.875rem; color: #718096; line-height: 1.5;">EagleCash vous propose des formations exclusives pour développer vos compétences en trading, importation depuis la Chine, et plus encore. Ces sessions sont conçues pour vous aider à maximiser vos revenus et réussir dans vos projets. Restez connecté pour découvrir les opportunités à venir !</p>
                            </div>

                            <!-- Liste des formations -->
                            <div style="overflow-x: auto; display: flex; flex-direction: column; gap: 1.25rem; margin-top: 1.5rem;">
                                <div style="background: linear-gradient(90deg, #3b82f6, #60a5fa); padding: 1.5rem; border-radius: 0.5rem; box-shadow: 0 4px 8px rgba(0,0,0,0.1); animation: fadeIn 0.5s;">
                                    <div style="height: 8rem; background: url(\'https://via.placeholder.com/300x200?text=Trading+Image\') no-repeat center; background-size: cover; border-radius: 0.375rem; margin-bottom: 1rem;"></div>
                                    <h3 style="font-size: 1.25rem; font-weight: 600; color: #ffffff;">Trading</h3>
                                    <p style="font-size: 0.875rem; color: #e6f0fa; margin-top: 0.5rem;">Section dédiée au trading en ligne pour débutants et experts.</p>
                                    <button style="padding: 0.5rem 1rem; background: #a0aec0; color: #ffffff; border: none; border-radius: 0.25rem; font-size: 0.875rem; cursor: not-allowed; opacity: 0.7;" disabled>Indisponible</button>
                                </div>
                                <div style="background: linear-gradient(90deg, #10b981, #34d399); padding: 1.5rem; border-radius: 0.5rem; box-shadow: 0 4px 8px rgba(0,0,0,0.1); animation: fadeIn 0.5s;">
                                    <div style="height: 8rem; background: url(\'https://via.placeholder.com/300x200?text=Chine+Image\') no-repeat center; background-size: cover; border-radius: 0.375rem; margin-bottom: 1rem;"></div>
                                    <h3 style="font-size: 1.25rem; font-weight: 600; color: #ffffff;">Chine</h3>
                                    <p style="font-size: 0.875rem; color: #e6f0fa; margin-top: 0.5rem;">Section dédiée à l\'importation de produits depuis la Chine.</p>
                                    <button style="padding: 0.5rem 1rem; background: #a0aec0; color: #ffffff; border: none; border-radius: 0.25rem; font-size: 0.875rem; cursor: not-allowed; opacity: 0.7;" disabled>Indisponible</button>
                                </div>
                                <div style="background: linear-gradient(90deg, #f59e0b, #f97316); padding: 1.5rem; border-radius: 0.5rem; box-shadow: 0 4px 8px rgba(0,0,0,0.1); animation: fadeIn 0.5s;">
                                    <div style="height: 8rem; background: url(\'https://via.placeholder.com/300x200?text=Affiliation+Image\') no-repeat center; background-size: cover; border-radius: 0.375rem; margin-bottom: 1rem;"></div>
                                    <h3 style="font-size: 1.25rem; font-weight: 600; color: #ffffff;">Affiliation</h3>
                                    <p style="font-size: 0.875rem; color: #e6f0fa; margin-top: 0.5rem;">Gagnez des revenus en recommandant EagleCash. Votre lien unique est disponible dans votre profil. Partagez-le avec vos amis : vous recevez 10% de commission sur le premier dépôt de niveau 1, 5% pour niveau 2, et 2% pour niveau 3, avec un maximum de 3 niveaux de parrainage.</p>
                                    <a href="?action=affiliation-guide" style="padding: 0.5rem 1rem; background: #ffffff; color: #f59e0b; border: none; border-radius: 0.25rem; font-size: 0.875rem; text-decoration: none; font-weight: 600; transition: transform 0.3s;">Voir le Guide</a>
                                </div>
                            </div>

                            <!-- Prochaines sessions -->
                            <div style="margin-top: 1.5rem; background: linear-gradient(135deg, #edf2f7, #e6f0fa); padding: 1.5rem; border-radius: 0.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); animation: fadeIn 0.5s;">
                                <h3 style="font-size: 1.25rem; font-weight: 600; color: #2d3748; margin-bottom: 0.75rem;">Prochaines Sessions</h3>
                                <ul style="font-size: 0.875rem; color: #718096; padding-left: 1.5rem; line-height: 1.5;">
                                    <li style="margin-bottom: 0.5rem;">Introduction au Trading - À venir</li>
                                    <li style="margin-bottom: 0.5rem;">Guide d\'Importation depuis la Chine - À venir</li>
                                    <li>Atelier Affiliation - Disponible maintenant</li>
                                </ul>
                            </div>

                            <!-- Appel à l\'action -->
                            <div style="margin-top: 1.5rem; text-align: center; animation: fadeIn 0.5s;">
                                <p style="font-size: 0.875rem; color: #718096;">Les formations seront bientôt disponibles. Inscrivez-vous pour recevoir des notifications !</p>
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
                    }
                    @media (max-width: 768px) {
                        h2 { font-size: 1.25rem; }
                        h3 { font-size: 1rem; }
                        div[style*="flex-direction: column"] { gap: 1rem; }
                    }
                </style>';
        } else {
            $content = '<div style="text-align: center; padding: 1.5rem; background-color: #ffffff; border-radius: 0.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 60px;"><p style="color: #4a5568;">Non connecté.</p></div>';
        }
    }
}

// pas besoin de s'inscrire pour cela lorsqu'il clique il est sur une page ou on l'explique et j'espere que tu te rapelle de notre systeme de parrainnage et les commission a chaque niveau