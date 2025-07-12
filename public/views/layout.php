<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EagleCash - <?php echo isset($title) ? $title : 'Accueil'; ?></title>
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php
    // Supprimons require_once __DIR__ . '/../vendor/autoload.php'; car il est déjà inclus dans index.php
    // Utilisons $sessionManager passé depuis index.php si nécessaire
    global $sessionManager;
    ?>

    <?php
    if (isset($_SESSION['success'])) {
        echo '<div class="alert success">' . htmlspecialchars($_SESSION['success']) . '</div>';
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        echo '<div class="alert error">' . htmlspecialchars($_SESSION['error']) . '</div>';
        unset($_SESSION['error']);
    }
    ?>


<!-- padding: 15px; min-height: calc(100vh - 60px); margin-top: 60px; -->
    <main style="padding: 10px; min-height: calc(100vh - 60px); margin-top: 60px;">
        <?php echo $content; ?>
    </main>

    <?php if (isset($sessionManager) && isPaymentVerified($sessionManager)): ?>
    <header style="background-color: #2a4365; color: white; text-align: center; padding: 20px; position: fixed; top: 0; width: 100%; z-index: 50; display: flex; align-items: center; justify-content: center;">
        <img id="logo" src="/mes-projets/eaglecash-poo/public/img/logo.jpg" alt="EagleCash Logo" style="width: 80px; height: auto; margin-right: 15px; border-radius: 8px; transition: transform 0.5s;">
        <h1 id="animated-text" style="margin: 0; font-size: 28px;"></h1>
    </header>

    <script>
        const textElement = document.getElementById('animated-text');
        const logoElement = document.getElementById('logo');
        const text = "EagleCash";

        // Function to animate letter colors
        function animateText() {
            textElement.innerHTML = ''; // Clear the text

            text.split('').forEach((letter, index) => {
                const span = document.createElement('span');
                span.innerText = letter;
                span.style.transition = 'color 0.5s';
                span.style.display = 'inline-block';
                textElement.appendChild(span);

                // Change color after a delay
                setTimeout(() => {
                    span.style.color = getRandomColor();
                }, index * 300); // Change color for each letter with a delay
            });
        }

        // Function to get a random color
        function getRandomColor() {
            const letters = '0123456789ABCDEF';
            let color = '#';
            for (let i = 0; i < 6; i++) {
                color += letters[Math.floor(Math.random() * 16)];
            }
            return color;
        }

        // Function to animate the logo
        function animateLogo() {
            logoElement.style.transform = 'scale(1.1)';
            setTimeout(() => {
                logoElement.style.transform = 'scale(1)';
            }, 500);
        }

        // Loop the animations
        function loopAnimations() {
            animateText();
            animateLogo();
            setInterval(() => {
                animateText();
                animateLogo();
            }, 3000); // Change every 3 seconds
        }

        // Start the animations
        loopAnimations();
    </script>
    <nav style="position: fixed; bottom: 0; width: 100%; background-color: #2a4365; display: flex; justify-content: space-around; padding: 8px 0; border-top: 1px solid #4a5568; z-index: 50;">
        <?php $current = $_GET['action'] ?? 'dashboard-unique'; ?>
        <a href="?action=dashboard-unique" class="nav-item <?php echo $current === 'dashboard-unique' ? 'active' : ''; ?>" style="color: white; text-decoration: none; text-align: center; width: 20%; padding: 5px;">
            <span style="display: block; font-size: 24px; margin-bottom: 3px;">🏠</span><span style="display: block; font-size: 12px;">Dashboard</span>
        </a>
        <a href="?action=affiliate-unique" class="nav-item <?php echo $current === 'affiliate-unique' ? 'active' : ''; ?>" style="color: white; text-decoration: none; text-align: center; width: 20%; padding: 5px;">
            <span style="display: block; font-size: 24px; margin-bottom: 3px;">👥</span><span style="display: block; font-size: 12px;">Affiliation</span>
        </a>
        <a href="?action=formation-unique" class="nav-item <?php echo $current === 'formation-unique' ? 'active' : ''; ?>" style="color: white; text-decoration: none; text-align: center; width: 20%; padding: 5px;">
            <span style="display: block; font-size: 24px; margin-bottom: 3px;">🎓</span><span style="display: block; font-size: 12px;">Formation</span>
        </a>
        <a href="?action=marketplace-unique" class="nav-item <?php echo $current === 'marketplace-unique' ? 'active' : ''; ?>" style="color: white; text-decoration: none; text-align: center; width: 20%; padding: 5px;">
            <span style="display: block; font-size: 24px; margin-bottom: 3px;">🛍️</span><span style="display: block; font-size: 12px;">Marketplace</span>
        </a>
        <a href="?action=account-unique" class="nav-item <?php echo $current === 'account-unique' ? 'active' : ''; ?>" style="color: white; text-decoration: none; text-align: center; width: 20%; padding: 5px;">
            <span style="display: block; font-size: 24px; margin-bottom: 3px;">👤</span><span style="display: block; font-size: 12px;">Compte</span>
        </a>
    </nav>
    <?php endif; ?>

    <script>
        setTimeout(function() {
            var alerts = document.getElementsByClassName('alert');
            for (var i = 0; i < alerts.length; i++) {
                alerts[i].style.display = 'none';
            }
        }, 4000);
    </script>

    <style>
        .alert {
            position: fixed;
            top: 70px;
            left: 50%;
            transform: translateX(-50%);
            padding: 10px 20px;
            border-radius: 5px;
            z-index: 60;
            text-align: center;
            width: 90%;
            max-width: 400px;
        }
        .alert.success {
            background-color: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }
        .alert.error {
            background-color: #fed7d7;
            color: #742a2a;
            border: 1px solid #feb2b2;
        }
        .nav-item.active {
            background-color: #ecc94b;
            border-radius: 5px;
            opacity: 0.7;
        }
        .nav-item:hover {
            color: #9ae6b4;
        }
        @media (min-width: 768px) {
            nav {
                display: none;
            }
            main {
                margin-bottom: 0;
            }
        }
    </style>
</body>
</html>