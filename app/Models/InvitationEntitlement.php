<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvitationEntitlement extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['activated_at' => 'datetime'];
}