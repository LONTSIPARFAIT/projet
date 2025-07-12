<?php $title = "Dashboard"; ?>
<?php
$phone = $_SESSION['user_phone'] ?? null;
if ($phone) {
    require_once __DIR__ . '/../../src/controllers/DashboardController.php';
    $controller = new DashboardController();
    $controller->index();
}
$content = '
    <div class="container">
        <h2>Dashboard</h2>
        <div class="stats">
            <div class="stat-box">Solde : <span class="highlight">0 FCFA</span></div>
            <div class="stat-box">Parrainages : <span class="highlight">0</span></div>
            <div class="stat-box">Cours terminés : <span class="highlight">0</span></div>
        </div>
        <p class="intro">Gérez vos revenus, suivez vos parrainages et accédez à vos formations ici.</p>
    </div>
';
require_once __DIR__ . '/layout.php';