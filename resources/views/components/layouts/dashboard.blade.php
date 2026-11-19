@props(['active' => 'overview'])
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Dashboard — Rona' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-50" x-data="{ sidebarOpen: false }">
    <div class="min-h-screen lg:flex">
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 z-30 bg-ink/30 lg:hidden" aria-hidden="true"></div>
        <aside class="fixed inset-y-0 left-0 z-40 w-72 -translate-x-full border-r border-line bg-paper px-5 py-6 transition-transform lg:static lg:translate-x-0" :class="sidebarOpen && 'translate-x-0'" aria-label="Navigasi utama">
            <div class="flex items-center justify-between px-2">
                <a href="{{ url('/') }}" class="font-serif text-2xl font-semibold tracking-tight">Rona<span class="text-gold">.</span></a>
                <button @click="sidebarOpen = false" class="rounded-lg p-2 text-ink-muted hover:bg-stone-100 lg:hidden" aria-label="Tutup menu">&times;</button>
            </div>
            <p class="eyebrow mb-4 mt-12 px-2">Ruang kerja</p>
            <nav class="space-y-1">
                @foreach ([['overview', 'Ringkasan', url('/dashboard')], ['invitations', 'Undangan', route('dashboard.invitations.index')], ['guests', 'Tamu', route('dashboard.invitations.index')], ['billing', 'Paket & tagihan', route('billing.index')]] as [$key, $label, $href])
                    <a href="{{ $href }}" class="flex min-h-11 items-center gap-3 rounded-xl px-3 text-sm font-medium transition {{ $active === $key ? 'bg-stone-100 text-ink' : 'text-ink-muted hover:bg-stone-50 hover:text-ink' }}">
                        <span class="h-1.5 w-1.5 rounded-full bg-gold" aria-hidden="true"></span>{{ $label }}
                    </a>
                @endforeach
            </nav>
            <div class="absolute bottom-6 left-5 right-5 rounded-2xl bg-stone-100 p-4">
                <p class="text-sm font-semibold">Butuh inspirasi?</p>
                <p class="mt-1 text-xs leading-5 text-ink-muted">Jelajahi koleksi tema untuk menemukan gaya yang paling kamu.</p>
                <a href="#" class="mt-3 inline-flex text-xs font-semibold text-gold hover:text-amber-800">Lihat tema <span aria-hidden="true">→</span></a>
            </div>
        </aside>
        <main class="min-w-0 flex-1">
            <header class="flex h-20 items-center justify-between border-b border-line bg-paper px-5 sm:px-8">
                <button @click="sidebarOpen = true" class="rounded-xl p-2 text-ink-muted hover:bg-stone-100 lg:hidden" aria-label="Buka menu"><span class="block h-4 w-5 border-y-2 border-current" aria-hidden="true"></span></button>
                <div class="hidden lg:block"></div>
                <div class="flex items-center gap-3">
                    <button class="rounded-xl p-2 text-ink-muted hover:bg-stone-100" aria-label="Notifikasi"><span class="block h-2.5 w-2.5 rounded-full border-2 border-current" aria-hidden="true"></span></button>
                    <div class="flex items-center gap-3 border-l border-line pl-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-stone-200 font-serif text-sm">AR</span>
                        <div class="hidden sm:block"><p class="text-sm font-semibold">{{ auth()->user()->name }}</p><a href="{{ route('profile.edit') }}" class="text-xs text-ink-muted hover:text-ink">Profil akun</a></div>
                        <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">@csrf @method('POST')<button type="submit" class="text-xs font-semibold text-gold hover:text-amber-800">Keluar</button></form>
                    </div>
                </div>
            </header>
            <div class="mx-auto max-w-7xl p-5 sm:p-8">{{ $slot }}</div>
        </main>
    </div>
</body>
</html>