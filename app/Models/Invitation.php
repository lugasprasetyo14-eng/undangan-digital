<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Invitation extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'user_id', 'theme_id', 'slug', 'title', 'event_type', 'groom_name',
        'bride_name', 'story', 'status', 'published_at',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Invitation $invitation) {
            $disk = Storage::disk(config('filesystems.default'));
            $invitation->media()->get()->each(fn ($media) => $disk->delete(array_values(array_filter([$media->path, $media->thumbnail_path]))));
            $invitation->giftOptions()->get()->each(fn ($gift) => $gift->qr_path && $disk->delete($gift->qr_path));
        });
    }

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(InvitationEvent::class);
    }

    public function guests(): HasMany
    {
        return $this->hasMany(InvitationGuest::class);
    }

    public function messages(): HasMany { return $this->hasMany(InvitationMessage::class); }
    public function media(): HasMany { return $this->hasMany(InvitationMedia::class); }
    public function giftOptions(): HasMany { return $this->hasMany(InvitationGiftOption::class); }
    public function wishlists(): HasMany { return $this->hasMany(InvitationWishlist::class); }
    public function invoices(): HasMany { return $this->hasMany(Invoice::class); }
    public function entitlements(): HasMany { return $this->hasMany(InvitationEntitlement::class); }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }
}