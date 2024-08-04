<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Competitor extends Model
{
    use HasFactory;

    public $name;
    public $url;
    public $rating;
    public $reviewCount;
    public $price;
    public $features;

    public function __construct($name, $url, $rating, $reviewCount, $price, $features)
    {
        $this->name = $name;
        $this->url = $url;
        $this->rating = $rating;
        $this->reviewCount = $reviewCount;
        $this->price = $price;
        $this->features = $features;
    }
}
