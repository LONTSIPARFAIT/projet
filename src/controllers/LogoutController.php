<?php
namespace App\Controllers;

class LogoutController {
    public function logout() {
        session_start();
        session_destroy();
        header("Location: ?action=login");
        exit;
    }
}