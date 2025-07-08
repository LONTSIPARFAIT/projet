<?php
namespace App\Models;

class User {
    private $id;
    private $name;
    private $email;
    private $phone;
    private $balance;

    public function __construct($name, $email, $phone, $balance = 0.00) {
        $this->name = $name;
        $this->email = $email;
        $this->phone = $phone;
        $this->balance = $balance;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getEmail() { return $this->email; }
    public function getPhone() { return $this->phone; }
    public function getBalance() { return $this->balance; }

    // Setters
    public function setId($id) { $this->id = $id; }
    public function setName($name) { $this->name = $name; }
    public function setEmail($email) { $this->email = $email; }
    public function setPhone($phone) { $this->phone = $phone; }
    public function setBalance($balance) { $this->balance = $balance; }
    
    // ...
}