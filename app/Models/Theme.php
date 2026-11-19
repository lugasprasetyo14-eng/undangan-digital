<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Theme extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'preview_image', 'preview_url', 'config',
        'is_premium', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['config' => 'array', 'is_premium' => 'boolean', 'is_active' => 'boolean'];
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }
}