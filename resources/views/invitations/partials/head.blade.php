@php($publicUrl = route('invitations.public', $invitation->slug))
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invitation->title }} — {{ $invitation->groom_name }} &amp; {{ $invitation->bride_name }}</title>
    <meta name="description" content="Undangan {{ $invitation->title }} untuk {{ $invitation->groom_name }} dan {{ $invitation->bride_name }}.">
    <meta name="robots" content="noindex, nofollow">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $invitation->title }} — {{ $invitation->groom_name }} &amp; {{ $invitation->bride_name }}">
    <meta property="og:description" content="Undangan {{ $invitation->title }}.">
    <meta property="og:url" content="{{ $publicUrl }}">
    <link rel="canonical" href="{{ $publicUrl }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>