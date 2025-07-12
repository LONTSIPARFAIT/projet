<?php $title = "Connexion"; ?>
<?php
// Supprime session_start() si déjà dans index.php
if (isset($_SESSION['user_phone'])) {
    header("Location: ?action=dashboard");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['country_code'] . $_POST['phone']; // Combine code pays et numéro
    $password = $_POST['password'];

    require_once __DIR__ . '/../../src/controllers/AuthController.php';
    $auth = new AuthController();
    // Logique dans AuthController
}

$content = '
    <div style="position: relative; min-height: calc(100vh - 60px); margin-top: 60px; background: linear-gradient(135deg, #f7fafc, #e6f0fa); overflow: hidden;">
        <div id="bubble-container" style="position: absolute; width: 100%; height: 100%; top: 0; left: 0; z-index: 0;"></div>
        <svg style="position: absolute; top: 10%; left: 5%; opacity: 0.1; z-index: 1;" width="100" height="100" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="1">
            <circle cx="12" cy="12" r="10"></circle>
        </svg>
        <svg style="position: absolute; bottom: 10%; right: 5%; opacity: 0.1; z-index: 1;" width="150" height="150" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="1">
            <path d="M3 3l18 18M3 21l18-18" />
        </svg>
        <div style="max-width: 400px; margin: 0 auto; padding: 2rem; background: rgba(255, 255, 255, 0.95); border-radius: 1rem; box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1); position: relative; z-index: 2; text-align: center;">
            <img src="/mes-projets/eaglecash-poo/public/img/logo.jpg" alt="Logo de la plateforme" style="max-width: 100px; margin-bottom: 1rem;">
            <h2 style="color: #f59e0b; font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem;">Connexion</h2>
            <form method="POST" action="?action=login" style="display: flex; flex-direction: column; gap: 1rem;">
                <div style="text-align: left;">
                    <label style="display: block; color: #374151; font-weight: 600; margin-bottom: 0.5rem;">Téléphone</label>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <select name="country_code" id="country_code" onchange="updatePhonePlaceholder()" required style="flex: 1; max-width: 100px; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 1rem; appearance: none; background: url(\'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="%23374151" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>\') no-repeat right 0.75rem center/12px; cursor: pointer; height: 100%; box-sizing: border-box;">
                            <option value="">Pays</option>
                 Z           <option value="+237">🇨🇲 +237</option>
                            <option value="+225">🇨🇮 +225</option>
                            <option value="+226">🇧🇫 +226</option>
                            <option value="+241">🇬🇦 +241</option>
                            <option value="+229">🇧🇯 +229</option>
                            <option value="+254">🇰🇪 +254</option>
                            <option value="+221">🇸🇳 +221</option>
                            <option value="+243">🇨🇩 +243</option>
                        </select>
                        <input type="tel" name="phone" id="phone" placeholder="Ex: 6XXXXXXXX" required style="flex: 2; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 1rem; height: 100%; box-sizing: border-box;">
                    </div>
                </div>
                <div style="text-align: left;">
                    <label style="display: block; color: #374151; font-weight: 600; margin-bottom: 0.5rem;">Mot de passe</label>
                    <input type="password" name="password" required style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 1rem;">
                </div>
                <button type="submit" style="background-color: #3b82f6; color: white; padding: 0.75rem; border: none; border-radius: 0.5rem; cursor: pointer; transition: background-color 0.3s;">Se connecter</button>
            </form>
            <p style="margin-top: 1rem; color: #4a5568;">Pas encore inscrit ? <a href="?action=register" style="color: #3b82f6; text-decoration: none; font-weight: 600;">Inscrivez-vous ici</a></p>
        </div>
    </div>
';
require_once __DIR__ . '/layout.php';
?>

<script>
    // Animation des bulles
    function createBubble() {
        const bubble = document.createElement('div');
        const size = Math.random() * 30 + 10 + 'px';
        bubble.style.width = size;
        bubble.style.height = size;
        bubble.style.background = `rgba(${Math.random() * 255}, ${Math.random() * 255}, ${Math.random() * 255}, 0.2)`;
        bubble.style.borderRadius = '50%';
        bubble.style.position = 'absolute';
        bubble.style.left = Math.random() * 100 + '%';
        bubble.style.bottom = '-50px';
        bubble.style.animation = `float ${Math.random() * 5 + 3}s linear infinite`;
        document.getElementById('bubble-container').appendChild(bubble);

        bubble.addEventListener('animationend', () => bubble.remove());
    }

    setInterval(createBubble, 400);

    const styleSheet = document.createElement('style');
    styleSheet.textContent = `
        @keyframes float {
            0% { transform: translateY(0); }
            100% { transform: translateY(100vh); }
        }
        @media (max-width: 480px) {
            .bubble {
                animation-duration: ${Math.random() * 3 + 2}s !important;
                width: ${Math.random() * 20 + 5}px !important;
                height: ${Math.random() * 20 + 5}px !important;
            }
            [name="country_code"], [name="phone"] {
                font-size: 0.9rem;
                padding: 0.5rem;
            }
        }
    `;
    document.head.appendChild(styleSheet);

    // Mettre à jour le placeholder du numéro de téléphone
    function updatePhonePlaceholder() {
        const countrySelect = document.getElementById('country_code');
        const phoneInput = document.getElementById('phone');
        const countryCode = countrySelect.value;
        const formats = {
            '+237': 'Ex: 6XXXXXXXX',
            '+225': 'Ex: 0XXXXXXXX',
            '+226': 'Ex: 7XXXXXXXX',
            '+241': 'Ex: 0XXXXXXXX',
            '+229': 'Ex: 9XXXXXXXX',
            '+254': 'Ex: 7XXXXXXXX',
            '+221': 'Ex: 7XXXXXXXX',
            '+243': 'Ex: 8XXXXXXXX'
        };
        phoneInput.placeholder = formats[countryCode] || 'Ex: XXXXXXXXX';
    }
</script>