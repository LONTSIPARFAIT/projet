<?php $title = "Trading"; ?>
<?php
$phone = $_SESSION['user_phone'] ?? null;
if ($phone) {
    require_once __DIR__ . '/../../src/controllers/TradingController.php';
    $controller = new TradingController();
    $controller->index();
}
$content = '
    <div class="container">
        <h2>Trading</h2>
        <ul class="lessons">
            <li><strong>Leçon 1 :</strong> Lire les graphiques (15 min)</li>
            <li><strong>Leçon 2 :</strong> Analyse technique (20 min)</li>
            <li><strong>Leçon 3 :</strong> Gestion des risques (10 min)</li>
        </ul>
        <p class="tip">Conseil : Commencez avec un petit capital sur Forex ou crypto.</p>
    </div>
';
require_once __DIR__ . '/layout.php';

// eyJraWQiOiIxIiwiYWxnIjoiRVMyNTYifQ.eyJ0dCI6IkFBVCIsInN1YiI6Ijg0MjMiLCJtYXYiOiIxIiwiZXhwIjoyMDY3MjU4NzE4LCJpYXQiOjE3NTE3MjU5MTgsInBtIjoiREFGLFBBRiIsImp0aSI6IjQ4ODlmZDhiLWZhMTktNDg0OC1iNDExLWQ0OGZjMDkyOWU1OCJ9.ZeXn2LvEEsHagCrCapBloueLQfCCNA0dfqkxXjk-vWV5PglHkNyu8zDLQ511ZxSsbnrXjDDkRMXr1wXD9UJKlw