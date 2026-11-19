<!doctype html>
<html lang="id" class="bg-[#fff7f5]">
@include('invitations.partials.head')
<body class="min-h-screen bg-[#fff7f5] font-sans text-[#4a3032]">
<main class="mx-auto max-w-3xl px-5 py-8 sm:px-8 sm:py-16">
    <div class="relative overflow-hidden rounded-[2.5rem] bg-[#fffdfb] px-6 py-12 text-center shadow-[0_24px_70px_rgba(183,110,121,.14)] sm:px-14 sm:py-16">
        <div class="pointer-events-none absolute -right-12 -top-16 h-48 w-48 rounded-full border border-[#d9a2a7]/60" aria-hidden="true"></div><div class="pointer-events-none absolute -bottom-20 -left-12 h-48 w-48 rounded-full border border-[#d9a2a7]/60" aria-hidden="true"></div>
        <p class="relative text-xs font-semibold uppercase tracking-[0.24em] text-[#a95d69]">{{ $preview ? 'Preview pribadi' : 'Sebuah cerita untuk dirayakan' }}</p>
        <h1 class="relative mt-8 font-serif text-5xl leading-[1.1] text-[#7f4852] sm:text-7xl"><span>{{ $invitation->groom_name }}</span><br><span class="text-3xl text-[#c47b85]">&amp;</span><br><span>{{ $invitation->bride_name }}</span></h1>
        <p class="relative mx-auto mt-8 max-w-md text-sm leading-7 text-[#765c5e]">{{ $invitation->title }}</p>
        @if($invitation->story)<p class="relative mx-auto mt-10 max-w-xl text-sm leading-7 text-[#765c5e]">{{ $invitation->story }}</p>@endif
        <div class="relative mx-auto mt-12 max-w-xl rounded-[2rem] bg-[#fff1ee] p-5 text-left sm:p-8">@include('invitations.partials.events')</div>
        @include('invitations.partials.actions')
        @include('invitations.partials.rsvp')
        @include('invitations.partials.content')
        <p class="relative mt-12 font-serif text-xl italic text-[#a95d69]">Dengan hangat, {{ $invitation->groom_name }} &amp; {{ $invitation->bride_name }}</p>
    </div>
</main>
</body>
</html>