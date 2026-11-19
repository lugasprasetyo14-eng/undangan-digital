<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        Gate::authorize('view', auth()->user());

        return view('profile.edit');
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        Gate::authorize('update', $request->user());
        $request->user()->update($request->validated());

        return to_route('profile.edit')->with('status', 'profile-updated');
    }
}