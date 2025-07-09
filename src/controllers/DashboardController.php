<?php
namespace App\Controllers;

use Config\Database;

class DashboardController {
    private $db;

    public function __construct() {
        try {
            $this->db = (new Database())->getConnection();
        } catch (Exception $e) {
            global $content;
            $content = '<div style="text-align: center; padding: 1.5rem; background-color: #ffffff; border-radius: 0.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 60px;"><p style="color: #4a5568;">Erreur de connexion à la base de données : ' . htmlspecialchars($e->getMessage()) . '</p></div>';
        }
    }

    public function index() {
        global $conte, $title;
        $title = "Dashboard";
        $phone = $_SESSION['user_phone'] ?? null;
        $user = null;
        $referrals = 0;

        if ($phone) {
            try {
                if (!$this->db) {
                    throw new Exception("Connexion à la base de données non disponible.");
                }

                $stmt = $this->db->prepare("SELECT * FROM users WHERE phone = ? LIMIT 1");
                $stmt->execute([$phone]);
                $user = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($user) {
                    $stmt = $this->db->prepare("SELECT COUNT(*) FROM referrals WHERE referred_by = ?");
                    $stmt->execute([$phone]);
                    $referrals = $stmt->fetchColumn() ?: 0;

                    $balance = $user['balance'] ?? 0;
                    $payment_amount = $user['payment_amount'] ?? 0;

                    $content = '
                        <div style="position: relative; padding: 1rem 0; min-height: calc(100vh - 60px); margin-top: 60px; background: linear-gradient(135deg, #f7fafc, #e6f0fa); color: #2d3748;">
                            <div style="max-width: 80rem; margin-left: auto; margin-right: auto; padding-left: 0.75rem; padding-right: 0.75rem; @media (min-width: 768px) { padding-left: 1.5rem; padding-right: 1.5rem; }}">

                                <!-- Slider d\'images (inchangé) -->
                                <div style="position: relative; width: 98%; max-height: 400px; overflow: hidden; margin: 1rem auto; border-radius: 0.5rem; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);">
                                    <div id="slider" style="display: flex; width: 300%; animation: slide 15s infinite;">
                                        <div style="width: 33.333%; flex-shrink: 0; border-radius: 0.5rem; overflow: hidden;">
                                            <img src="https://via.placeholder.com/1200x400?text=Image+1+EagleCash" alt="Slide 1" style="width: 100%; height: 400px; object-fit: cover; transition: transform 0.5s; border-radius: 0.5rem;">
                                        </div>
                                        <div style="width: 33.333%; flex-shrink: 0; border-radius: 0.5rem; overflow: hidden;">
                                            <img src="https://via.placeholder.com/1200x400?text=Image+2+EagleCash" alt="Slide 2" style="width: 100%; height: 400px; object-fit: cover; transition: transform 0.5s; border-radius: 0.5rem;">
                                        </div>
                                        <div style="width: 33.333%; flex-shrink: 0; border-radius: 0.5rem; overflow: hidden;">
                                            <img src="https://via.placeholder.com/1200x400?text=Image+3+EagleCash" alt="Slide 3" style="width: 100%; height: 400px; object-fit: cover; transition: transform 0.5s; border-radius: 0.5rem;">
                                        </div>
                                    </div>
                                    <div id="indicators" style="text-align: center; margin-top: 0.5rem;">
                                        <span class="dot" style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin: 0 5px; cursor: pointer;"></span>
                                        <span class="dot" style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin: 0 5px; cursor: pointer;"></span>
                                        <span class="dot" style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin: 0 5px; cursor: pointer;"></span>
                                    </div>
                                    <div class="navigation">
                                        <button id="prev" aria-label="Slide précédent" style="position: absolute; top: 50%; left: 1rem; transform: translateY(-50%); background: rgba(0,0,0,0.7); color: white; border: none; padding: 0.5rem; cursor: pointer; border-radius: 50%; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3); transition: background 0.3s;">
                                            ❮
                                        </button>
                                        <button id="next" aria-label="Slide suivant" style="position: absolute; top: 50%; right: 1rem; transform: translateY(-50%); background: rgba(0,0,0,0.7); color: white; border: none; padding: 0.5rem; cursor: pointer; border-radius: 50%; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3); transition: background 0.3s;">
                                            ❯
                                        </button>
                                    </div>
                                </div>

                                <style>
                                    @keyframes slide {
                                        0% { transform: translateX(0); }
                                        33.33% { transform: translateX(-33.33%); }
                                        66.66% { transform: translateX(-66.66%); }
                                        100% { transform: translateX(0); }
                                    }
                                    #slider { transition: transform 1s ease-in-out; }
                                    .navigation button { font-size: 1.5rem; }
                                    .navigation button:hover { background: rgba(0,0,0,0.9); }
                                    @media (max-width: 768px) {
                                        #slider { max-height: 200px; }
                                        img { height: 200px; }
                                    }
                                </style>

                                <script>
                                    let currentSlide = 0;
                                    const slides = document.getElementById("slider");
                                    const totalSlides = 3;
                                    const prevBtn = document.getElementById("prev");
                                    const nextBtn = document.getElementById("next");
                                    const dots = document.querySelectorAll(".dot");

                                    const colors = ["red", "blue", "green"];

                                    function goToSlide(index) {
                                        currentSlide = (index + totalSlides) % totalSlides;
                                        slides.style.transform = `translateX(-${currentSlide * 33.33}%)`;
                                        updateIndicators();
                                    }

                                    function updateIndicators() {
                                        dots.forEach((dot, index) => {
                                            dot.style.background = index === currentSlide ? colors[index] : "lightgray";
                                        });
                                    }

                                    prevBtn.addEventListener("click", () => goToSlide(currentSlide - 1));
                                    nextBtn.addEventListener("click", () => goToSlide(currentSlide + 1));

                                    dots.forEach((dot, index) => {
                                        dot.addEventListener("click", () => goToSlide(index));
                                    });

                                    setInterval(() => goToSlide(currentSlide + 1), 5000);
                                </script>

                                <div style="display: flex; justify-content: space-between; margin-bottom: 1.5rem; animation: fadeIn 0.5s;">
                                    <a href="?action=deposit" style="display: flex; align-items: center; padding: 0.5rem 1rem; background: linear-gradient(90deg, #f59e0b, #f97316); border-radius: 0.375rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-decoration: none; color: #ffffff; font-size: 0.875rem; transition: transform 0.3s;">
                                        <svg style="margin-right: 0.5rem;" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2">
                                            <path d="M12 5v14m-7-7h14" />
                                        </svg>
                                        Dépôt
                                    </a>
                                    <a href="?action=withdraw" style="display: flex; align-items: center; padding: 0.5rem 1rem; background: linear-gradient(90deg, #f59e0b, #f97316); border-radius: 0.375rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-decoration: none; color: #ffffff; font-size: 0.875rem; transition: transform 0.3s;">
                                        <svg style="margin-right: 0.5rem;" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2">
                                            <path d="M12 19V5m-7 7h14" />
                                        </svg>
                                        Retrait
                                    </a>
                                </div>

                                <div style="background: linear-gradient(135deg, #3b82f6, #60a5fa); color: #ffffff; padding: 1.5rem; border-radius: 0.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 1.5rem; position: relative; overflow: hidden; animation: fadeIn 0.5s;">
                                    <svg style="position: absolute; top: -50px; right: -50px; opacity: 0.1;" width="150" height="150" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="1">
                                        <circle cx="12" cy="12" r="10"></circle>
                                    </svg>
                                    <h2 style="font-size: 1.5rem; font-weight: 700; color: #f59e0b; margin-bottom: 1rem; position: relative; z-index: 1;">Mon Profil</h2>
                                    <div style="margin-top: 1rem; display: flex; flex-direction: column; gap: 0.5rem; position: relative; z-index: 1;">
                                        <div style="display: flex; justify-content: space-between; font-size: 0.875rem;">
                                            <span>Investissement total :</span>
                                            <span style="color: #23604CFF; font-weight: 600;">0 XAF</span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; font-size: 0.875rem;">
                                            <span>Gains accumulés :</span>
                                            <span style="color: #23604CFF; font-weight: 600;">' . htmlspecialchars($balance) . ' FCFA</span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; font-size: 0.875rem;">
                                            <span>Solde :</span>
                                            <span style="color: #23604CFF; font-weight: 600;">' . htmlspecialchars($balance) . ' FCFA</span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; font-size: 0.875rem;">
                                            <span>Montant payé :</span>
                                            <span style="color: #23604CFF; font-weight: 600;">' . htmlspecialchars($payment_amount) . ' FCFA</span>
                                        </div>
                                    </div>
                                </div>

                                <div style="display: flex; align-items: center; margin: 1.5rem 0;">
                                    <hr style="flex: 1; border-top: 1px solid #e2e8f0;">
                                    <h2 style="font-size: 1.25rem; font-weight: 700; margin: 0 1rem; color: #2d3748;">Actions Rapides</h2>
                                    <hr style="flex: 1; border-top: 1px solid #e2e8f0;">
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr; gap: 1rem; margin-bottom: 1.5rem; @media (min-width: 768px) { grid-template-columns: repeat(2, 1fr); }">
                                    <div style="background: linear-gradient(90deg, #4b5563, #6b7280); padding: 1rem; border-radius: 0.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; animation: fadeIn 0.5s;">
                                        <svg style="margin: 0 auto 0.5rem; width: 2rem; height: 2rem;" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2">
                                            <path d="M17 20.5v-15a3 3 0 0 0-3-3h-4a3 3 0 0 0-3 3v15m6-12v8m-3-5h6" />
                                        </svg>
                                        <p style="font-size: 1rem; font-weight: 600; color: #ffffff; margin: 0;">Filleuls : ' . $referrals . '</p>
                                    </div>
                                    <div style="background: linear-gradient(90deg, #4b5563, #6b7280); padding: 1rem; border-radius: 0.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; animation: fadeIn 0.5s;">
                                        <svg style="margin: 0 auto 0.5rem; width: 2rem; height: 2rem;" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2">
                                            <path d="M3 3v18h18M7 10l5-5 5 5m-5-5v12" />
                                        </svg>
                                        <p style="font-size: 1rem; font-weight: 600; color: #ffffff; margin: 0;">Performance : 0%</p>
                                    </div>
                                </div>

                                <!-- Présentation d\'EagleCash (inchangé) -->
                                <div style="background: linear-gradient(135deg, #ffffff, #edf2f7); border: 2px solid #e2e8f0; border-radius: 0.5rem; padding: 1.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 1.5rem; animation: fadeIn 0.5s;">
                                    <h2 style="font-size: 1.5rem; font-weight: 700; text-align: center; color: #2d3748; margin-bottom: 1rem;">À Propos d\'EagleCash</h2>
                                    <p style="color: #4a5568; font-size: 1rem; line-height: 1.5; margin-bottom: 1rem;">EagleCash est une plateforme innovante conçue pour vous aider à maximiser vos revenus grâce à des opportunités variées. Que ce soit par le trading en ligne, l\'importation de produits depuis la Chine, ou le programme d\'affiliation, nous offrons les outils nécessaires pour réussir.</p>
                                    <p style="color: #4a5568; font-size: 1rem; line-height: 1.5;">Rejoignez une communauté dynamique et profitez de formations exclusives, d\'un solde sécurisé, et d\'un système de parrainage qui récompense vos efforts. Avec EagleCash, votre succès financier est à portée de main !</p>
                                </div>

                                <div style="background: linear-gradient(135deg, #fefcbf, #fef9c3); border: 2px solid #f59e0b; border-radius: 0.5rem; padding: 1.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 1.5rem; animation: fadeIn 0.5s;">
                                    <h2 style="font-size: 1.125rem; font-weight: 700; text-align: center; color: #d97706; margin-bottom: 1rem;">Astuces pour Réussir</h2>
                                    <ul style="list-style-type: disc; padding-left: 1.5rem; color: #744210; line-height: 1.5;">
                                        <li style="margin-bottom: 0.5rem;">Participez aux formations Trading.</li>
                                        <li style="margin-bottom: 0.5rem;">Explorez l\'achat en Chine.</li>
                                        <li>Invitez des amis via Affiliation.</li>
                                    </ul>
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
                                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
                            }
                            @media (max-width: 768px) {
                                h2 { font-size: 1.25rem; }
                                a { font-size: 0.75rem; padding: 0.25rem 0.5rem; }
                                .actions-grid { grid-template-columns: 1fr; }
                                #slider { max-height: 200px; }
                                img { height: 200px; }
                            }
                        </style>';
                } else {
                    $content = '<div style="text-align: center; padding: 1.5rem; background-color: #ffffff; border-radius: 0.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 60px;"><p style="color: #4a5568;">Erreur : Utilisateur non trouvé.</p></div>';
                }
            } catch (Exception $e) {
                $content = '<div style="text-align: center; padding: 1.5rem; background-color: #ffffff; border-radius: 0.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 60px;"><p style="color: #4a5568;">Erreur : ' . htmlspecialchars($e->getMessage()) . '</p></div>';
            }
        } else {
            $content = '<div style="text-align: center; padding: 1.5rem; background-color: #ffffff; border-radius: 0.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 60px;"><p style="color: #4a5568;">Connectez-vous pour voir votre tableau de bord.</p></div>';
        }
        echo "Contenu défini pour dashboard: " . strlen($content) . " caractères<br>";
    }
}