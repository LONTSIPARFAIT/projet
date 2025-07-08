<?php
namespace App\Controllers;

use Config\Database;

class TransactionController {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function index() {
        $content = "<h2>Transactions</h2><p>Liste des transactions ici.</p>";
        require_once __DIR__ . '/../../public/views/layout.php';
    }
}