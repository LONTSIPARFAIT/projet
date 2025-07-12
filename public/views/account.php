<?php $title = "Mon Compte"; ?>
<?php
$phone = $_SESSION['user_phone'] ?? null;
if ($phone) {
    require_once __DIR__ . '/../../src/controllers/AccountController.php';
    $controller = new AccountController();
    $controller->index();
}
$content = '
    <div class="container">
        <h2>Mon Compte</h2>
        <div class="account-details">
            <p>Nom : [Nom de l\'utilisateur]</p>
            <p>Téléphone : ' . htmlspecialchars($phone) . '</p>
            <p>Solde : 0 FCFA</p>
            <p>Lien d\'affiliation : <a href="https://eaglecash.com/ref/xyz123-' . htmlspecialchars($phone) . '" target="_blank">Voir</a></p>
        </div>
        <p><a href="?action=logout" class="logout">Déconnexion</a></p>
    </div>
';
require_once __DIR__ . '/layout.php';