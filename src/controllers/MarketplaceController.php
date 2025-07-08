<?php
namespace App\Controllers;

use Config\Database;

class MarketplaceController {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function index() {
        global $content, $title;
        $title = "Marketplace";
        $phone = $_SESSION['user_phone'] ?? null;

        if ($phone) {
            $content = '
                <div style="padding: 1.5rem 0; min-height: calc(100vh - 60px); margin-top: 60px; background: linear-gradient(135deg, #f7fafc, #e6f0fa); color: #2d3748;">
                    <div style="max-width: 80rem; margin-left: auto; margin-right: auto; padding-left: 0.75rem; padding-right: 0.75rem; @media (min-width: 768px) { padding-left: 1.5rem; padding-right: 1.5rem; }">
                        <div style="background: linear-gradient(135deg, #ffffff, #f0f4f8); overflow: hidden; box-shadow: 0 8px 12px rgba(0,0,0,0.15); border-radius: 0.75rem; border: 2px solid #3b82f6; padding: 1rem; @media (min-width: 768px) { padding: 1.5rem; }">
                            <h2 style="font-size: 1.5rem; font-weight: 700; color: #f59e0b; margin-bottom: 1rem; text-align: center; @media (min-width: 768px) { font-size: 1.75rem; text-align: left; }">
                                Marketplace
                            </h2>
                            <p style="margin-top: 1rem; color: #718096;">Bienvenue dans la section Marketplace d\'EagleCash ! Cette plateforme est en cours de développement et sera bientôt votre hub pour explorer une variété de produits et services. Restez à l\'affût pour des opportunités uniques !</p>

                            <div style="margin-top: 1.5rem; display: flex; flex-direction: column; gap: 1.5rem; @media (min-width: 768px) { flex-direction: row; }">
                                <div style="flex: 1; background: #edf2f6; padding: 1rem; border-radius: 0.5rem; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                                    <div style="height: 8rem; background: #e2e8f0; border-radius: 0.375rem; display: flex; align-items: center; justify-content: center; color: #a0aec0;">Icône Produits</div>
                                    <h3 style="font-size: 1.125rem; font-weight: 600; color: #3b82f6; margin-top: 0.75rem;">Produits Importés</h3>
                                    <p style="margin-top: 0.5rem; font-size: 0.875rem; color: #718096;">Découvrez une sélection de produits importés directement depuis la Chine et d\'autres marchés mondiaux.</p>
                                </div>
                                <div style="flex: 1; background: #edf2f6; padding: 1rem; border-radius: 0.5rem; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                                    <div style="height: 8rem; background: #e2e8f0; border-radius: 0.375rem; display: flex; align-items: center; justify-content: center; color: #a0aec0;">Icône Services</div>
                                    <h3 style="font-size: 1.125rem; font-weight: 600; color: #3b82f6; margin-top: 0.75rem;">Services d\'Affiliation</h3>
                                    <p style="margin-top: 0.5rem; font-size: 0.875rem; color: #718096;">Participez à notre programme d\'affiliation et gagnez des commissions sur les recommandations.</p>
                                </div>
                                <div style="flex: 1; background: #edf2f6; padding: 1rem; border-radius: 0.5rem; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                                    <div style="height: 8rem; background: #e2e8f0; border-radius: 0.375rem; display: flex; align-items: center; justify-content: center; color: #a0aec0;">Icône Trading</div>
                                    <h3 style="font-size: 1.125rem; font-weight: 600; color: #3b82f6; margin-top: 0.75rem;">Outils de Trading</h3>
                                    <p style="margin-top: 0.5rem; font-size: 0.875rem; color: #718096;">Accédez à des outils et ressources pour améliorer vos compétences en trading en ligne.</p>
                                </div>
                            </div>

                            <div style="margin-top: 1.5rem;">
                                <h3 style="font-size: 1.125rem; font-weight: 600; color: #3b82f6; margin-bottom: 0.75rem;">Fonctionnalités à Venir</h3>
                                <ul style="margin-top: 0.5rem; padding-left: 1.5rem; font-size: 0.875rem; color: #718096;">
                                    <li style="margin-bottom: 0.25rem;">Recherche avancée de produits et services</li>
                                    <li style="margin-bottom: 0.25rem;">Offres spéciales et promotions exclusives</li>
                                    <li style="margin-bottom: 0.25rem;">Transactions sécurisées avec votre solde EagleCash</li>
                                    <li style="margin-bottom: 0.25rem;">Système de notation et avis des utilisateurs</li>
                                </ul>
                            </div>

                            <div style="margin-top: 1.5rem; text-align: center;">
                                <p style="font-size: 0.875rem; color: #718096;">Cette section est en construction. Restez connecté pour les dernières mises à jour ou partagez vos idées avec notre équipe !</p>
                                <a href="?action=account-unique" style="display: inline-block; margin-top: 1rem; background: linear-gradient(90deg, #3b82f6, #2563eb); color: #ffffff; border-radius: 0.5rem; box-shadow: 0 4px 8px rgba(59,130,246,0.3); padding: 0.75rem 1.5rem; text-decoration: none; font-weight: 600; transition: transform 0.3s; @media (max-width: 768px) { font-size: 0.875rem; padding: 0.5rem 1rem; }">
                                    Retour à Mon Profil
                                </a>
                            </div>
                        </div>
                    </div>
                </div>';
        } else {
            $content = '<div style="text-align: center; padding: 1.5rem; background-color: #ffffff; border-radius: 0.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 60px;"><p style="color: #4a5568;">Non connecté.</p></div>';
        }
    }
}