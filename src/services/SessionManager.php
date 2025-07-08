<?php
namespace App\Services;

class SessionManager {
    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public function get($key) {
        $value = $_SESSION[$key] ?? null;
        // echo "Débogage - SessionManager get($key) : " . ($value ?? 'null') . "<br>";
        return $value;
    }

    public function destroy() {
        session_destroy();
    }
}