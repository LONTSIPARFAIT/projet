<?php
namespace App\Controllers;

use Config\Database;

class CourseController {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function index() {
        $content = "<h2>Courses</h2><p>Liste des courses ici.</p>";
        require_once __DIR__ . '/../../public/views/layout.php';
    }
}