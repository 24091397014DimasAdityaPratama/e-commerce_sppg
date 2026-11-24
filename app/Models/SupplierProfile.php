<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'business_type',
        'contact_person',
        'province',
        'city',
        'district',
        'village',
        'postal_code',
        'address',
        'nib_number',
        'nib_document_path',
        'verification_status',
        'notes',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'qris_image_path',
        'business_logo_path',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
