<?php

namespace App\Support;

use App\Models\Invitation;

class InvitationThemeRenderer
{
    public static function viewFor(Invitation $invitation): string
    {
        return match ($invitation->theme?->slug) {
            'modern-minimal' => 'invitations.themes.modern-minimal',
            'romantic-blush' => 'invitations.themes.romantic-blush',
            default => 'invitations.themes.elegant-ivory',
        };
    }
}