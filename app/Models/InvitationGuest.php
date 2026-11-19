<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class InvitationGuest extends Model
{
    use HasFactory;

    public const RSVP_PENDING = 'pending';
    public const RSVP_ATTENDING = 'attending';
    public const RSVP_NOT_ATTENDING = 'not_attending';

    protected $fillable = [
        'name', 'slug', 'phone', 'email', 'category', 'max_guests', 'invited_at',
        'rsvp_status', 'attending_count', 'rsvp_at', 'checked_in_at',
    ];

    protected $hidden = ['rsvp_token', 'qr_token'];

    protected $attributes = ['max_guests' => 1, 'rsvp_status' => 'pending', 'attending_count' => 0];

    protected function casts(): array
    {
        return [
            'invited_at' => 'datetime', 'opened_at' => 'datetime', 'rsvp_at' => 'datetime',
            'checked_in_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (InvitationGuest $guest) {
            $guest->slug ??= Str::slug($guest->name).'-'.Str::lower(Str::random(8));
            $guest->rsvp_token ??= Str::random(64);
            $guest->qr_token ??= Str::random(64);
            $guest->invited_at ??= now();
        });
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    public function qrImage(): string
    {
        return (new \chillerlan\QRCode\QRCode([
            'outputInterface' => \chillerlan\QRCode\Output\QRMarkupSVG::class,
            'outputBase64' => false,
        ]))->render(route('dashboard.invitations.guests.verify', [$this->invitation_id, $this->qr_token]));
    }
}