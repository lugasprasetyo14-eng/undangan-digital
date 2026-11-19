# Guest, RSVP dan QR

- `invitation_guests` adalah satu sumber kebenaran RSVP. Tidak ada tabel RSVP duplikat.
- `max_guests` mencakup tamu utama. Tidak hadir selalu menyimpan jumlah 0; kirim ulang memperbarui record yang sama.
- RSVP dan perubahan kapasitas memakai transaction/row lock pada record tamu yang sama.
- Token RSVP dan QR berbeda, masing-masing acak 64 karakter. Link personal adalah bearer credential: siapa pun yang memegangnya bisa memperbarui RSVP entri tersebut. Jangan bagikan ke publik.
- `opened_at` hanya waktu kunjungan valid pertama, bukan bukti undangan dibaca atau bukti kehadiran.
- QR berisi URL verifikasi dashboard dengan token QR, tanpa nama atau kontak. Pemilik harus login dan menekan konfirmasi POST; GET tidak melakukan check-in.
- Check-in memakai conditional update `checked_in_at IS NULL`. Permintaan berulang tidak menulis ulang timestamp dan tidak mengubah RSVP.
- Satu QR menandai satu entri tamu, bukan menghitung jumlah anggota rombongan yang benar-benar datang. Tidak ada scanner JavaScript atau role usher.
- CSV tidak menyertakan token; nilai berpotensi formula diawali apostrof.
- Halaman personal memakai no-store dan no-referrer. Metadata/canonical/share umum selalu memakai URL tanpa token.
- Untuk ponsel, konfigurasi APP_URL ke origin HTTPS yang benar dan dapat dijangkau; QR localhost tidak dapat dibuka dari perangkat lain.