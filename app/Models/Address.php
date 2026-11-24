<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'full_name',
        'phone',
        'province',
        'city',
        'district',
        'village',
        'postal_code',
        'street',
        'is_primary',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
