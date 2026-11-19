<x-layouts.dashboard active="billing">
    <p class="eyebrow">Invoice Rona</p>
    <h1 class="mt-2 break-words font-serif text-2xl font-semibold sm:text-3xl">{{ $invoice->reference }}</h1>
    <a href="{{ route('billing.index') }}" class="mt-4 inline-flex min-h-11 items-center text-sm font-semibold text-gold underline">Kembali ke paket &amp; tagihan</a>
    @if($errors->any())
        <p role="alert" class="mt-5 rounded-xl bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</p>
    @endif
    @if(session('status'))
        <p role="status" class="mt-5 rounded-xl border border-line p-4 text-sm">{{ session('status') }}</p>
    @endif
    <x-card class="mt-6 max-w-3xl">
        <h2 class="font-serif text-2xl">{{ $invoice->package_name }}</h2>
        <p class="mt-2 text-sm text-ink-muted">{{ $invoice->invitation->title }} · Sekali bayar</p>
        <dl class="mt-6 grid gap-5 sm:grid-cols-2">
            <div><dt class="text-sm text-ink-muted">Nominal paket (IDR)</dt><dd class="mt-1 text-2xl font-semibold">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</dd></div>
            <div><dt class="text-sm text-ink-muted">Status verifikasi</dt><dd class="mt-1 font-semibold">{{ ['paid' => 'Lunas', 'failed' => 'Gagal', 'pending' => 'Menunggu pembayaran'][$invoice->status] ?? 'Belum terverifikasi' }}</dd></div>
            <div><dt class="text-sm text-ink-muted">Channel</dt><dd class="mt-1 font-semibold">{{ $invoice->channel }}</dd></div>
            <div><dt class="text-sm text-ink-muted">Tanggal invoice</dt><dd class="mt-1">{{ $invoice->created_at->format('d M Y H:i') }}</dd></div>
        </dl>
        <p class="mt-6 text-sm leading-6 text-ink-muted">Kembali dari halaman pembayaran tidak berarti lunas. Gunakan cek status untuk rekonsiliasi server. Undangan tidak otomatis dipublish setelah pembayaran. Biaya channel dapat berbeda dari nominal paket.</p>
        <div class="mt-6 flex flex-wrap gap-3">
            @if($canPay && $invoice->status === 'pending')
                <a href="{{ $invoice->pay_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center rounded-xl bg-ink px-4 text-sm font-semibold text-white">Bayar melalui Tokopay</a>
            @endif
            @if($invoice->status !== 'paid')
                <form method="POST" action="{{ route('billing.check', $invoice) }}">
                    @csrf
                    <x-button type="submit">Cek status pembayaran</x-button>
                </form>
            @else
                <p role="status" class="text-sm font-semibold text-sage">Hak paket untuk undangan ini sudah aktif.</p>
            @endif
        </div>
    </x-card>
</x-layouts.dashboard>