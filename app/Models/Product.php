<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    public function guage()
    {
        return $this->belongsTo(Guage::class, 'guage', 'id');
    }
}
