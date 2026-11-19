@if(! $preview && $guest)
    @if(session('status') === 'wishlist-reserved')
        <p role="status" class="mt-4 rounded-xl border border-current/20 p-4 text-sm font-semibold">Reservasi berhasil. Hadiah ini sudah kamu pesan.</p>
    @endif
    @if($errors->has('wishlist'))
        <p role="alert" class="mt-4 rounded-xl border border-current/20 p-4 text-sm font-semibold">{{ $errors->first('wishlist') }}</p>
    @endif
@endif