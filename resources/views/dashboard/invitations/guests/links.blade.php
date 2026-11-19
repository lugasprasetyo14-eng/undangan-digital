<div class="mt-3 space-y-2">
    <label for="link-{{ $guest->id }}" class="block text-xs font-semibold">Link personal {{ $guest->name }}</label>
    <input id="link-{{ $guest->id }}" readonly value="{{ route('invitations.public', ['slug' => $invitation->slug, 'to' => $guest->rsvp_token]) }}" class="min-h-11 w-full rounded-xl border border-line px-3 text-xs">
    <button type="button" data-copy-link="link-{{ $guest->id }}" class="min-h-11 rounded-xl border border-line px-4 text-sm font-semibold">Salin link personal</button>
    <span role="status" class="text-sm text-ink-muted"></span>
</div>