@if($guest)
<section class="mt-10 rounded-2xl border border-current/10 bg-black/[0.03] p-5 text-left" aria-labelledby="rsvp-heading">
    <p class="text-xs font-semibold uppercase tracking-[0.18em] opacity-70">Untuk {{ $guest->name }}</p>
    <h2 id="rsvp-heading" class="mt-2 font-serif text-2xl">Konfirmasi kehadiran</h2>
    @if($errors->any())<div class="mt-3 rounded-xl bg-red-50 p-3 text-sm text-red-800" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @if(session('status') === 'rsvp-saved')<p class="mt-3 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800" role="status">Konfirmasi kamu sudah tersimpan.</p>@endif
    <form method="POST" action="{{ route('invitations.rsvp', $invitation->slug) }}" class="mt-5 space-y-4">
        @csrf
        <input type="hidden" name="to" value="{{ $guest->rsvp_token }}">
        <fieldset><legend class="text-sm font-semibold">Kehadiran</legend><div class="mt-2 grid gap-2 sm:grid-cols-2"><label class="flex min-h-11 items-center gap-2 rounded-xl border border-current/10 px-3 text-sm"><input type="radio" name="status" value="attending" @checked($guest->rsvp_status === 'attending')> Hadir</label><label class="flex min-h-11 items-center gap-2 rounded-xl border border-current/10 px-3 text-sm"><input type="radio" name="status" value="not_attending" @checked($guest->rsvp_status === 'not_attending')> Tidak hadir</label></div></fieldset>
        <label class="block text-sm font-semibold" for="attending_count">Jumlah tamu <span class="font-normal opacity-70">(maks. {{ $guest->max_guests }})</span><input id="attending_count" name="attending_count" type="number" min="0" max="{{ $guest->max_guests }}" value="{{ $guest->attending_count }}" class="mt-2 min-h-11 w-full rounded-xl border border-current/15 bg-transparent px-3 text-sm"></label>
        <button class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-ink px-4 text-sm font-semibold text-white hover:bg-stone-700">Simpan konfirmasi</button>
    </form>
    <figure class="mt-6 border-t border-current/10 pt-5">
        <img src="data:image/svg+xml;base64,{{ base64_encode($guest->qrImage()) }}" width="240" height="240" class="mx-auto max-w-full bg-white" alt="QR check-in untuk ditunjukkan kepada pemilik undangan">
        <figcaption class="mt-3 text-sm">Tunjukkan QR ini saat tiba. Pemindaian saja tidak mencatat check-in.</figcaption>
    </figure>
</section>
@endif