<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'buyer_id',
        'supplier_id',
        'shipping_address_id',
        'status',
        'payment_status',
        'payment_method',
        'payment_proof_path',
        'payment_submitted_at',
        'payment_verified_at',
        'payment_rejection_reason',
        'gateway_order_id',
        'gateway_transaction_id',
        'qris_action_url',
        'delivery_status',
        'courier_name',
        'tracking_number',
        'delivery_proof_path',
        'subtotal',
        'shipping_fee',
        'discount',
        'total',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'payment_submitted_at' => 'datetime',
        'payment_verified_at' => 'datetime',
    ];

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function supplier()
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function shippingAddress()
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }
}
