<section aria-labelledby="event-heading">
    <p class="text-xs font-semibold uppercase tracking-[0.2em] opacity-70">Rangkaian acara</p>
    <h2 id="event-heading" class="sr-only">Jadwal acara</h2>
    @if($upcomingEvent)
        <div class="mt-5 rounded-2xl border border-current/10 bg-black/[0.03] p-5" data-countdown data-target="{{ \App\Support\InvitationSchedule::localStart($upcomingEvent)->utc()->toIso8601String() }}">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] opacity-70">Menuju acara terdekat</p>
            <p class="mt-2 font-serif text-3xl" data-countdown-value>—</p>
            <noscript><p class="mt-2 text-sm">{{ \App\Support\InvitationSchedule::localStart($upcomingEvent)->format('d M Y, H:i') }} waktu setempat</p></noscript>
        </div>
    @endif
    <div class="mt-6 space-y-4">
        @forelse($invitation->events as $event)
            @php($start = \App\Support\InvitationSchedule::localStart($event))
            @php($end = \App\Support\InvitationSchedule::localEnd($event))
            <article class="rounded-2xl border border-current/10 p-5">
                <h3 class="font-serif text-xl">{{ $event->name }}</h3>
                <p class="mt-2 text-sm font-semibold">{{ $start->format('d M Y, H:i') }}@if($end) — {{ $end->format('H:i') }}@endif</p>
                <p class="mt-1 text-xs uppercase tracking-[0.14em] opacity-70">Waktu setempat · {{ $event->timezone }}</p>
                @if($event->venue_name || $event->address)<p class="mt-4 text-sm leading-6">{{ $event->venue_name }}@if($event->venue_name && $event->address), @endif{{ $event->address }}</p>@endif
                <div class="mt-4 flex flex-wrap gap-2">
                    @if(\App\Support\InvitationSchedule::safeMapUrl($event->maps_url))<a href="{{ \App\Support\InvitationSchedule::safeMapUrl($event->maps_url) }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center rounded-full border border-current/20 px-3 text-xs font-semibold hover:bg-black/5">Buka peta</a>@endif
                    @if($start)<a href="{{ \App\Support\InvitationSchedule::calendarUrl($event) }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center rounded-full border border-current/20 px-3 text-xs font-semibold hover:bg-black/5">Google Calendar</a>@endif
                </div>
            </article>
        @empty
            <p class="text-sm opacity-70">Jadwal acara akan dibagikan segera.</p>
        @endforelse
    </div>
</section>
@if($upcomingEvent)<script>
document.querySelectorAll('[data-countdown]').forEach((element) => {
    const target = new Date(element.dataset.target).getTime();
    const output = element.querySelector('[data-countdown-value]');
    const update = () => {
        const seconds = Math.max(0, Math.floor((target - Date.now()) / 1000));
        const days = Math.floor(seconds / 86400);
        const hours = Math.floor((seconds % 86400) / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        output.textContent = `${days} hari ${hours} jam ${minutes} menit`;
    };
    update();
    window.setInterval(update, 60000);
});
</script>@endif