<!doctype html>
<html lang="id" class="bg-[#f5f7f4]">
@include('invitations.partials.head')
<body class="min-h-screen bg-[#f5f7f4] font-sans text-[#202623]">
<main class="mx-auto max-w-6xl px-5 py-6 sm:px-8 sm:py-12">
    <div class="grid overflow-hidden border border-[#cbd5ce] bg-white lg:grid-cols-[.9fr_1.1fr]">
        <section class="flex min-h-[520px] flex-col justify-between bg-[#dfe8e1] p-7 sm:p-12">
            <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-[0.2em] text-[#50635a]"><span>Rona</span><span aria-hidden="true">/</span></div>
            <div class="py-14 lg:py-0"><p class="max-w-xs text-sm leading-6 text-[#50635a]">{{ $invitation->title }}</p><h1 class="mt-6 max-w-lg text-5xl font-semibold leading-[.95] tracking-[-.06em] sm:text-7xl">{{ $invitation->groom_name }}<br><span class="text-[#50635a]">&amp;</span><br>{{ $invitation->bride_name }}</h1></div>
            <p class="text-xs uppercase tracking-[0.18em] text-[#50635a]">{{ $preview ? 'Mode preview' : 'Undangan digital' }}</p>
        </section>
        <section class="p-7 sm:p-12 lg:p-16">
            @if($invitation->story)<div class="max-w-xl border-l-2 border-[#91aa98] pl-5 text-sm leading-7 text-[#50635a]">{{ $invitation->story }}</div>@endif
            <div class="mt-12 max-w-xl">@include('invitations.partials.events')</div>
            @include('invitations.partials.actions')
            @include('invitations.partials.rsvp')
            @include('invitations.partials.content')
            <p class="mt-16 border-t border-[#d7dfd9] pt-6 text-sm text-[#50635a]">Terima kasih telah menjadi bagian dari cerita kami.</p>
        </section>
    </div>
</main>
</body>
</html>