<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    public function category()
    {
        return $this->belongsTo(Category::class, 'category', 'id');
    }

    public function guage()
    {
        return $this->belongsTo(Guage::class, 'guage', 'id');
    }

    public function added_by()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
