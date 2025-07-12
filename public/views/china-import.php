<?php $title = "Achat en Chine"; ?>
<?php
$phone = $_SESSION['user_phone'] ?? null;
if ($phone) {
    require_once __DIR__ . '/../../src/controllers/ChinaImportController.php';
    $controller = new ChinaImportController();
    $controller->index();
}
$content = '
    <div class="container">
        <h2>Achat en Chine</h2>
        <ul class="guides">
            <li><strong>Guide 1 :</strong> Importer via 1688 (30 min)</li>
            <li><strong>Guide 2 :</strong> Négocier sur Alibaba (25 min)</li>
        </ul>
        <p class="resource">Ressource : Consultez nos bases de données WhatsApp pour vos prospects.</p>
    </div>
';
require_once __DIR__ . '/layout.php';