<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

if (!isset($_ENV['PAWAPAY_SANDBOX_API_TOKEN'])) {
    die('Erreur : Jeton API non trouvé dans .env');
}