<?php

namespace App\Policies;

use App\Models\InvitationGuest;
use App\Models\User;

class InvitationGuestPolicy
{
    public function viewAny(User $user, InvitationGuest $guest): bool { return $user->id === $guest->invitation->user_id; }
    public function view(User $user, InvitationGuest $guest): bool { return $this->viewAny($user, $guest); }
    public function update(User $user, InvitationGuest $guest): bool { return $this->viewAny($user, $guest); }
    public function delete(User $user, InvitationGuest $guest): bool { return $this->viewAny($user, $guest); }
}