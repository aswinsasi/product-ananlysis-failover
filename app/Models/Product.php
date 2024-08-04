<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    public $name;
    public $rating;
    public $reviewCount;
    public $price;
    public $features;
    public $competitors = [];

    public function __construct($name, $rating, $reviewCount, $price, $features)
    {
        $this->name = $name;
        $this->rating = $rating;
        $this->reviewCount = $reviewCount;
        $this->price = $price;
        $this->features = $features;
    }

    public function addCompetitor($competitor)
    {
        $this->competitors[] = $competitor;
    }
}
