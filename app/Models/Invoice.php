<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['pay_url', 'customer_name', 'customer_email', 'customer_phone'];
    protected $casts = ['amount' => 'integer', 'paid_at' => 'datetime'];

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }
}