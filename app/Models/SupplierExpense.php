<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierExpense extends Model
{
    protected $fillable = [
        'supplier_id',
        'title',
        'category',
        'amount',
        'expense_date',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];
}
