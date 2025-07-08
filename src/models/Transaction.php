<?php
namespace App\Models;

class Transaction {
    private $id;
    private $userId;
    private $amount;
    private $type;

    public function __construct($userId, $amount, $type) {
        $this->userId = $userId;
        $this->amount = $amount;
        $this->type = $type;
    }
}