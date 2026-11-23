# Subscription Rules

Status subscription:

- `trial`, `active`, dan `grace_period` memberikan akses jika tanggal mulai sudah berlaku
  dan tanggal berakhir belum lewat;
- `expired`, `suspended`, dan `cancelled` tidak memberikan akses mutasi;
- `end_date = null` diperbolehkan untuk plan lifetime.

Aturan lifecycle:

- account baru mendapat plan `trial-14` jika plan default tersedia dan belum memiliki
  subscription;
- pemberian subscription aktif baru membatalkan entitlement valid sebelumnya dalam satu
  database transaction;
- histori subscription tidak dihapus ketika plan berubah atau diperpanjang;
- plan yang sudah digunakan tidak dapat dihapus, tetapi dapat dinonaktifkan;
- harga disalin ke subscription agar histori harga tidak berubah saat harga plan diedit;
- command `subscriptions:expire` menandai subscription lewat tanggal sebagai expired dan
  dijadwalkan setiap hari pukul 00:10;
- middleware `active.subscription` tetap memeriksa tanggal secara langsung.

Account expired tetap dapat login dan membaca dashboard. Route create/update finansial akan
dibatasi dengan middleware `active.subscription` mulai Phase 4.
