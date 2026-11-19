<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = ['code', 'name', 'price', 'is_active'];
    protected $casts = ['price' => 'integer', 'is_active' => 'boolean'];
}