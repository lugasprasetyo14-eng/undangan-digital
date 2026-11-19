# Phase QA Standards

Setiap kali satu phase dinyatakan selesai, lakukan QA ringan sebelum lanjut ke phase berikutnya.

Wajib dilakukan:
1. Jalankan migration dan seeder yang relevan.
2. Jalankan seluruh test.
3. Jalankan build frontend.
4. Periksa route yang baru dibuat.
5. Periksa validasi form.
6. Periksa authorization dan akses resource milik user lain.
7. Periksa error handling.
8. Periksa responsive layout minimal 375px, 768px, dan 1440px.
9. Periksa accessibility dasar.
10. Perbaiki bug yang ditemukan.
11. Jangan menambah fitur baru di luar phase aktif.
12. Jangan melakukan deployment production.
13. Jangan menganggap QA selesai hanya karena kode berhasil dibuat; harus ada bukti test atau verifikasi.

Laporkan hasil QA secara singkat sebelum melanjutkan.