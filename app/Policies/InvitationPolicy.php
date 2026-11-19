<?php

namespace App\Policies;

use App\Models\Invitation;
use App\Models\User;

class InvitationPolicy
{
    public function view(User $user, Invitation $invitation): bool
    {
        return $user->id === $invitation->user_id;
    }

    public function update(User $user, Invitation $invitation): bool
    {
        return $this->view($user, $invitation);
    }

    public function delete(User $user, Invitation $invitation): bool
    {
        return $this->view($user, $invitation);
    }

    public function preview(User $user, Invitation $invitation): bool
    {
        return $this->view($user, $invitation);
    }

    public function publish(User $user, Invitation $invitation): bool
    {
        return $this->view($user, $invitation);
    }

    public function unpublish(User $user, Invitation $invitation): bool
    {
        return $this->view($user, $invitation);
    }
}