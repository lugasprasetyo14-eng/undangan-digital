@php($publicUrl = route('invitations.public', $invitation->slug))
<div class="mt-8 flex flex-wrap justify-center gap-3" aria-label="Aksi undangan">
    @if(! $preview)
        <a href="https://wa.me/?text={{ urlencode($publicUrl) }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center justify-center rounded-full bg-ink px-4 text-sm font-semibold text-white hover:bg-stone-700">Bagikan WhatsApp</a>
    @endif
    @if($upcomingEvent)
        <a href="{{ \App\Support\InvitationSchedule::calendarUrl($upcomingEvent) }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center justify-center rounded-full border border-current px-4 text-sm font-semibold hover:bg-black/5">Simpan ke kalender</a>
    @endif
</div>