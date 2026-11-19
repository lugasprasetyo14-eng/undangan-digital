<?php

namespace App\Support;

use App\Models\InvitationEvent;
use Carbon\CarbonImmutable;

class InvitationSchedule
{
    public static function localStart(InvitationEvent $event): CarbonImmutable
    {
        return self::localize($event->getRawOriginal('start_at') ?: $event->getAttributes()['start_at'], $event->timezone);
    }

    public static function localEnd(InvitationEvent $event): ?CarbonImmutable
    {
        $raw = $event->getRawOriginal('end_at') ?: $event->getAttributes()['end_at'];

        return $raw ? self::localize($raw, $event->timezone) : null;
    }

    public static function upcoming(iterable $events): ?InvitationEvent
    {
        $now = CarbonImmutable::now('UTC');
        $upcoming = collect($events)->filter(fn (InvitationEvent $event) => self::localStart($event)->utc()->greaterThanOrEqualTo($now));

        return $upcoming->sortBy(fn (InvitationEvent $event) => self::localStart($event)->utc()->timestamp)->first();
    }

    public static function calendarUrl(InvitationEvent $event): string
    {
        $start = self::localStart($event)->utc();
        $end = self::localEnd($event)?->utc() ?? $start->addHour();

        $params = http_build_query([
            'action' => 'TEMPLATE',
            'text' => $event->name,
            'dates' => $start->format('Ymd\THis\Z').'/'.$end->format('Ymd\THis\Z'),
            'details' => $event->address ?? '',
            'location' => $event->venue_name ?? '',
        ]);

        return 'https://calendar.google.com/calendar/render?'.$params;
    }

    public static function safeMapUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return in_array(strtolower((string) $scheme), ['http', 'https'], true) ? $url : null;
    }

    private static function localize(mixed $value, string $timezone): CarbonImmutable
    {
        if ($value instanceof \DateTimeInterface) {
            return CarbonImmutable::create($value->format('Y'), $value->format('m'), $value->format('d'), $value->format('H'), $value->format('i'), $value->format('s'), $timezone);
        }

        return CarbonImmutable::parse((string) $value, $timezone);
    }
}