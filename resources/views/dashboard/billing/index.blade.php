<x-layouts.dashboard active="billing">
    <p class="eyebrow">Paket &amp; tagihan</p>
    <h1 class="mt-2 font-serif text-3xl font-semibold">Satu paket, untuk cerita kalian</h1>
    <p class="mt-3 max-w-2xl text-sm leading-6 text-ink-muted">Pembelian sekali bayar terikat ke satu undangan. Pembayaran tidak otomatis menerbitkan undangan.</p>
    @if($errors->any())
        <div role="alert" class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <p class="font-semibold">Periksa kembali data pembayaran.</p>
            <ul class="mt-2 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    @if(! config('tokopay.enabled'))
        <p role="status" class="mt-6 rounded-xl border border-line bg-paper p-4 text-sm">Pembayaran provider belum diaktifkan. Tidak ada tagihan live yang akan dibuat.</p>
    @endif
    <div class="mt-8 grid gap-4 md:grid-cols-2">
        @forelse($packages as $package)
            <x-card>
                <p class="eyebrow">Sekali bayar · IDR</p>
                <h2 class="mt-3 font-serif text-2xl">{{ $package->name }}</h2>
                <p class="mt-4 text-3xl font-semibold">Rp {{ number_format($package->price, 0, ',', '.') }}</p>
                <p class="mt-3 text-sm text-ink-muted">Hak paket dicatat setelah pembayaran diverifikasi server.</p>
            </x-card>
        @empty
            <p class="text-sm text-ink-muted">Belum ada paket aktif.</p>
        @endforelse
    </div>
    <x-card class="mt-8">
        <h2 class="font-serif text-2xl">Pilih undangan dan pembayaran</h2>
        <p class="mt-2 text-sm text-ink-muted">Lengkapi kontak asli untuk tagihan provider. Biaya channel dapat ditambahkan oleh Tokopay.</p>
        @forelse($invitations as $invitation)
            <form method="POST" action="{{ route('billing.checkout', $invitation) }}" class="mt-6 space-y-4 border-t border-line pt-6">
                @csrf
                <h3 class="font-serif text-xl">{{ $invitation->title }}</h3>
                @if($invitation->entitlements->isNotEmpty())
                    <p class="text-sm font-semibold text-sage">Hak paket sudah tercatat untuk undangan ini.</p>
                @endif
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <label for="package-{{ $invitation->id }}" class="block text-sm font-semibold">Paket</label>
                        <select id="package-{{ $invitation->id }}" name="package_id" required class="min-h-11 w-full rounded-xl border border-line bg-paper px-3">
                            @foreach($packages as $package)<option value="{{ $package->id }}">{{ $package->name }} — Rp {{ number_format($package->price, 0, ',', '.') }}</option>@endforeach
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label for="channel-{{ $invitation->id }}" class="block text-sm font-semibold">Channel Tokopay</label>
                        <select id="channel-{{ $invitation->id }}" name="channel" required class="min-h-11 w-full rounded-xl border border-line bg-paper px-3">
                            <option value="">Pilih channel yang diizinkan</option>
                            @foreach(config('tokopay.channels') as $channel)<option value="{{ $channel }}">{{ $channel }}</option>@endforeach
                        </select>
                    </div>
                    <x-input id="customer-name-{{ $invitation->id }}" name="customer_name" label="Nama pelanggan" value="{{ old('customer_name', auth()->user()->name) }}" autocomplete="name" required />
                    <x-input id="customer-email-{{ $invitation->id }}" name="customer_email" label="Email pelanggan" type="email" value="{{ old('customer_email', auth()->user()->email) }}" autocomplete="email" required />
                    <x-input id="customer-phone-{{ $invitation->id }}" name="customer_phone" label="Nomor telepon pelanggan" type="tel" value="{{ old('customer_phone', auth()->user()->phone) }}" autocomplete="tel" required />
                </div>
                <x-button type="submit" :disabled="! config('tokopay.enabled') || $packages->isEmpty() || ! config('tokopay.channels')">Buat tagihan</x-button>
            </form>
        @empty
            <p class="mt-4 text-sm">Buat undangan terlebih dahulu untuk membeli paket.</p>
        @endforelse
    </x-card>
    <h2 class="mt-10 font-serif text-2xl">Riwayat invoice</h2>
    <div class="mt-4 space-y-3">
        @forelse($invoices as $invoice)
            <x-card>
                <a href="{{ route('billing.show', $invoice) }}" class="block min-h-11 break-words font-semibold underline decoration-line underline-offset-4">{{ $invoice->reference }}</a>
                <p class="mt-2 text-sm">{{ $invoice->invitation->title }} · {{ $invoice->package_name }}</p>
                <p class="mt-2 text-sm text-ink-muted">Rp {{ number_format($invoice->amount, 0, ',', '.') }} · {{ $invoice->status }}</p>
            </x-card>
        @empty
            <p class="text-sm text-ink-muted">Belum ada invoice.</p>
        @endforelse
    </div>
    <div class="mt-5">{{ $invoices->links() }}</div>
</x-layouts.dashboard>