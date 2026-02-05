<?php

namespace App\Models;

use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return PaymentFactory::new();
    }

    protected $fillable = [
        'payment_id',
        'order_id',
        'status',
        'payment_method',
        'amount',
        'gateway_response',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
