<?php
namespace Config;

class Database {
    private $pdo;

    public function __construct() {
        try {
            $this->pdo = new \PDO("mysql:host=127.0.0.1;dbname=eaglecash_poo", "root", "");
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            throw new \Exception("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->pdo;
    }
}