<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'registration_platform',
        'state_id',
        'lga_id',
        'address',
    ];

    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function lga()
    {
        return $this->belongsTo(LocalGovernmentArea::class, 'lga_id');
    }
}
