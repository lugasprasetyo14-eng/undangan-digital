# Phase 7: Ucapan, Media dan Kado

- Ucapan disimpan pending, dimoderasi pemilik, dan hanya status approved tampil publik.
- RSVP tetap hanya link personal; ucapan umum tetap diperbolehkan. Preview tidak menulis data.
- Media memakai disk konfigurasi private. Foto dibatasi 5 MB, dimensi maksimal 6000x6000, maksimal 30 foto, dan thumbnail maksimal 1200x900. SVG, video upload, executable, transcoding, dan queue tidak digunakan.
- Video/musik hanya HTTPS URL eksternal. Musik tidak autoplay dan memakai kontrol native play/pause.
- QRIS hadiah berbeda dari QR check-in dan hanya tujuan transfer; tidak ada bukti pembayaran atau integrasi payment gateway.
- Wishlist hanya dapat direservasi tamu dengan token valid. Unique active slot dan row lock mencegah reservasi aktif kedua; pembatalan mengosongkan slot.
- Reservasi wishlist bukan pembayaran atau bukti barang sudah dibeli.
- File publik hanya lewat controller yang mengecek invitation published dan ownership resource; cache private/no-store. File fisik dihapus saat media/gift/invitation/user dihapus.