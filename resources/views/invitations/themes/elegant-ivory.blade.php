<!doctype html>
<html lang="id" class="bg-[#f7f2e9]">
@include('invitations.partials.head')
<body class="min-h-screen bg-[#f7f2e9] font-sans text-[#352d27]">
<main class="mx-auto max-w-3xl px-5 py-8 sm:px-8 sm:py-16">
    <div class="relative overflow-hidden border border-[#c9a96a]/50 bg-[#fffdf8] px-6 py-12 text-center shadow-[0_24px_70px_rgba(88,67,38,.12)] sm:px-16 sm:py-20">
        <div class="pointer-events-none absolute inset-4 border border-[#c9a96a]/40" aria-hidden="true"></div>
        <p class="relative text-xs font-semibold uppercase tracking-[0.3em] text-[#9a6f2d]">{{ $preview ? 'Preview pribadi' : 'Dengan penuh sukacita' }}</p>
        <p class="relative mt-8 font-serif text-5xl leading-none text-[#9a6f2d] sm:text-7xl">{{ $invitation->groom_name }}<br><span class="text-3xl">&amp;</span><br>{{ $invitation->bride_name }}</p>
        <p class="relative mx-auto mt-8 max-w-md text-sm leading-7 text-[#655b52]">{{ $invitation->title }}</p>
        @if($invitation->story)<p class="relative mx-auto mt-10 max-w-xl border-y border-[#c9a96a]/40 py-8 text-sm leading-7 text-[#655b52]">{{ $invitation->story }}</p>@endif
        <div class="relative mt-12 text-left"><div class="mx-auto max-w-xl">@include('invitations.partials.events')</div></div>
        @include('invitations.partials.actions')
        @include('invitations.partials.rsvp')
        @include('invitations.partials.content')
        <p class="relative mt-12 text-xs uppercase tracking-[0.24em] text-[#9a6f2d]">Sampai jumpa di hari bahagia</p>
    </div>
</main>
</body>
</html>