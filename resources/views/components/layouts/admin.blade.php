@props(['active' => 'overview'])
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Admin — Rona' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-50" x-data="{ open: false }">
    <div class="min-h-screen lg:flex">
        <aside class="w-full border-b border-line bg-ink px-5 py-5 text-white lg:min-h-screen lg:w-64 lg:border-b-0 lg:px-6 lg:py-8">
            <a href="{{ url('/') }}" class="font-serif text-2xl">Rona<span class="text-amber-400">.</span></a>
            <p class="mt-1 text-xs text-stone-400">Panel administrasi</p>
            <nav class="mt-10 hidden space-y-1 lg:block">
                @foreach ([['overview', 'Ringkasan', route('admin')], ['users', 'Pengguna', route('admin.users')], ['invitations', 'Undangan', route('admin.invitations')], ['themes', 'Tema', route('admin.themes')], ['packages', 'Paket', route('admin.packages')], ['payments', 'Invoice', route('admin.invoices')], ['audit', 'Audit log', route('admin.audit')]] as [$key, $label, $href])
                    <a href="{{ $href }}" class="block min-h-11 rounded-xl px-3 py-2.5 text-sm {{ $active === $key ? 'bg-white/10 text-white' : 'text-stone-400 hover:bg-white/5 hover:text-white' }}">{{ $label }}</a>
                @endforeach
            </nav>
        </aside>
        <main class="min-w-0 flex-1"><header class="flex items-center justify-between gap-4 border-b border-line bg-paper px-5 py-5 sm:px-8"><p class="eyebrow">Rona / Admin</p><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="inline-flex min-h-11 items-center rounded-xl border border-line px-3 text-sm font-semibold text-ink-muted hover:bg-stone-50 hover:text-ink">Keluar</button></form></header><div class="mx-auto max-w-7xl p-5 sm:p-8">{{ $slot }}</div></main>
    </div>
</body>
</html>