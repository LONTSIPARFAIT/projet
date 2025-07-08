<?php
namespace App\Models;

class Course {
    private $id;
    private $name;
    private $price;

    public function __construct($name, $price) {
        $this->name = $name;
        $this->price = $price;
    }
}