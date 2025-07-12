<?php $title = "Affiliation"; ?>
<?php
$phone = $_SESSION['user_phone'] ?? null;
if ($phone) {
    require_once __DIR__ . '/../../src/controllers/AffiliateController.php';
    $controller = new AffiliateController();
    $controller->index();
}
$content = '
    <div class="container">
        <h2>Affiliation</h2>
        <p class="intro">Gagnez 500 FCFA par parrainage ! Partagez votre lien unique :</p>
        <div class="affiliate-link">
            <input type="text" value="https://eaglecash.com/ref/xyz123-123456789" readonly>
            <button onclick="navigator.clipboard.writeText(this.previousElementSibling.value);alert(\'Lien copié !\')">Copier</button>
        </div>
        <div class="earnings">
            <p>Parrainages : 0</p>
            <p>Gains totaux : 0 FCFA</p>
        </div>
    </div>
';
require_once __DIR__ . '/layout.php';