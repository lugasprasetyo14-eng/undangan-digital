# Rona — Staging Runbook dan Release Gates

Dokumen ini adalah persiapan staging. Tidak ada langkah di bawah yang dijalankan sebagai deployment production pada Phase 10.

## Baseline lokal

- `TOKOPAY_ENABLED=false` dipertahankan.
- Database development tidak di-reset.
- Fixture undangan QA ID 2, 3, dan 4 harus berstatus `draft` sebelum handoff.
- Akun admin QA dan audit log QA hanya boleh tetap berada di database lokal; jangan diekspor ke production.

## Konfigurasi staging

Gunakan environment terpisah dan secret manager/secret variables platform hosting.

- `APP_ENV=staging`
- `APP_URL=https://<staging-host>` — isi dengan hostname staging yang disetujui.
- `APP_DEBUG=false`
- `APP_KEY` baru untuk environment staging; jangan menyalin secret lokal.
- Database staging terpisah dari development dan production.
- `DB_CONNECTION` sesuai database staging, dengan kredensial least-privilege.
- `MAIL_MAILER` gunakan SMTP/provider sandbox staging; jangan gunakan kredensial production.
- `FILESYSTEM_DISK=local` tetap private, atau gunakan bucket private dengan signed/application-mediated access.
- Pastikan `storage/app/private` dan direktori runtime Laravel writable oleh user worker/web sesuai kebutuhan, tetapi tidak writable oleh semua user OS.
- Web server hanya menunjuk ke `public/`; jangan expose `storage/app/private` sebagai document root atau alias statis.
- Pertahankan `TOKOPAY_ENABLED=false` sampai payment gate disetujui terpisah.

Baseline `.env.example` saat ini adalah development-oriented (SQLite, `APP_DEBUG=true`, mail log, local filesystem). Ia tidak boleh dipakai langsung sebagai staging environment tanpa override yang disebutkan di atas.

## Worker dan scheduler

Queue default proyek adalah `database`, tetapi pemeriksaan source saat ini tidak menemukan scheduler terjadwal atau command produksi yang membutuhkan worker terus-menerus. Jangan menambah Redis.

Jika job database queue mulai digunakan sebelum staging, jalankan worker yang sesuai platform, pantau `failed_jobs`, dan dokumentasikan retry/timeout sebelum release. Scheduler hanya perlu ditambahkan setelah ada command terjadwal yang benar-benar digunakan.

## Urutan release staging

1. Pastikan backup database dan media sudah selesai dan dapat dibaca.
2. Deploy artifact aplikasi ke staging tanpa file `.env` lokal.
3. Install dependency production dan gunakan build asset yang tervalidasi.
4. Jalankan `php artisan migrate --force` setelah backup dan setelah review migration.
5. Jalankan cache/config/view sesuai strategi platform.
6. Pastikan permission `storage/` dan `bootstrap/cache/` benar.
7. Jalankan smoke test route publik, login/logout, dashboard, media private, dan error page.
8. Verifikasi `APP_DEBUG=false`, host database staging, mail sandbox, dan `TOKOPAY_ENABLED=false`.
9. Catat hasil dan keputusan go/no-go.

## Backup dan recovery checklist

- [ ] Backup database staging sebelum migration.
- [ ] Backup media private secara konsisten dengan database.
- [ ] Simpan metadata backup, timestamp, retention, dan checksum.
- [ ] Verifikasi restore database pada environment terpisah.
- [ ] Verifikasi restore media pada environment terpisah.
- [ ] Jalankan migration pada staging setelah backup.
- [ ] Siapkan rollback aplikasi ke artifact sebelumnya.
- [ ] Untuk migration irreversible, siapkan prosedur forward-fix; jangan mengandalkan rollback otomatis.
- [ ] Jalankan smoke test setelah release dan setelah rollback drill.

Backup/restore dan rollback drill di atas baru didokumentasikan pada Phase 10; belum dijalankan karena tidak ada deployment atau environment staging yang diotorisasi.

## Release gates

- [ ] Full test suite lulus.
- [ ] Frontend build lulus.
- [ ] View cache dan migration status tervalidasi.
- [ ] Semua route baru dan authorization diverifikasi.
- [ ] Form validation dan error handling diverifikasi.
- [ ] Responsive/accessibility smoke test 375, 768, 1024, 1440 px lulus.
- [ ] Private media tidak dapat diakses melewati document root.
- [ ] Tidak ada secret, QA account, QA fixture, atau audit log lokal dalam artifact/data production.
- [ ] Tokopay E2E nyata lulus di environment pembayaran yang disetujui, atau payment tetap disabled.
- [ ] MySQL multi-process concurrency test lulus, atau dinyatakan sebagai gap.
- [ ] Owner menyetujui go/no-go staging.

## Classification gap

- **Staging blocker:** konfigurasi environment belum diisi/diisolasi, backup/restore belum tervalidasi, debug aktif, private media exposed, atau test/build gagal.
- **Event check-in blocker:** QR verification/POST check-in owner belum diverifikasi pada fixture aman, token tidak tersedia secara aman, atau authorization/check-in idempotency gagal.
- **Payment activation blocker:** Tokopay E2E nyata, secret/merchant configuration, callback verification, dan operational monitoring belum disetujui. `TOKOPAY_ENABLED` harus tetap `false` sampai seluruh gate ini lulus.