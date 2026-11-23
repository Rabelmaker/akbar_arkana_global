# Testing

Test menggunakan Pest 4 dan SQLite in-memory.

```bash
php artisan test --compact
npm run build
vendor/bin/pint --dirty
```

Cakupan foundation mencakup autentikasi Breeze, registrasi, pembuatan account owner,
assignment role, larangan user biasa ke admin dashboard, akses super admin, profile soft
delete, dan penolakan API tanpa token.

Cakupan account management mencakup:

- owner dan account-admin dapat memperbarui account;
- account-member tidak dapat mengubah account settings;
- user hanya dapat berpindah ke account aktif di dalam membership-nya;
- account suspended dan account asing ditolak;
- update active account tidak mengubah account tenant lain;
- perubahan profil dan perpindahan account menghasilkan audit log.

Cakupan subscription mencakup:

- registrasi memprovisi trial default;
- hanya super admin dapat mengelola plan dan subscription;
- perubahan plan menghasilkan audit log;
- subscription aktif baru membatalkan entitlement aktif sebelumnya;
- plan yang sudah digunakan tidak dapat dihapus;
- active subscription mengizinkan route mutasi;
- expired, suspended, cancelled, atau tidak memiliki subscription membatasi mutasi;
- account expired tetap dapat membuka dashboard read-only;
- command expiry mengubah status dan menghasilkan audit log.

Cakupan financial foundation mencakup:

- provisioning rekening dan kategori default bersifat idempotent;
- list rekening/kategori hanya memuat data tenant aktif;
- saldo pembuka rekening baru sama dengan saldo berjalan;
- perubahan saldo pembuka menjaga perubahan saldo berjalan berdasarkan selisih;
- rekening dan kategori tenant lain ditolak oleh policy;
- parent kategori lintas tenant dan hierarki berputar ditolak;
- kategori default tidak dapat dihapus;
- subscription tidak aktif tetap mengizinkan read-only dan memblokir mutasi;
- dashboard menampilkan total saldo rekening aktif yang disertakan;
- create/update/delete menghasilkan audit log.

Cakupan transaksi mencakup:

- draft tidak mengubah saldo dan dapat diposting kemudian;
- income, expense, transfer, dan biaya menghasilkan saldo yang tepat;
- cancel dan reversal mengembalikan saldo dengan jejak yang dapat diaudit;
- idempotency mencegah perubahan saldo ganda;
- lampiran disimpan pada disk privat;
- tenant crossover dan mutasi tanpa subscription ditolak;
- posted transaction tidak dapat diedit atau dihapus;
- list tenant menggunakan pagination server dan dashboard memakai nilai transaksi nyata.

Cakupan budget mencakup:

- CRUD dan activity log budget tenant;
- realisasi hanya menghitung expense posted yang cocok dengan kategori, rekening, dan periode;
- status safe, warning, serta exceeded mengikuti ambang alert;
- cancellation transaksi langsung mengembalikan nilai realisasi budget;
- kategori income dan periode aktif bertumpang tindih ditolak;
- budget tenant lain ditolak oleh policy;
- mode read-only subscription tetap dapat melihat budget tetapi tidak dapat memutasi;
- widget dashboard menampilkan alokasi, penggunaan, dan sisa aktual.

Cakupan laporan mencakup:

- agregasi pemasukan, pengeluaran, arus kas bersih, biaya, dan jumlah transaksi;
- isolasi tenant pada ringkasan, rincian, serta seluruh filter relasional;
- filter periode, jenis, status, rekening, kategori, sumber input, user pembuat, dan nominal;
- perbandingan dengan periode sebelumnya yang berdurasi sama;
- ekspor CSV, sumber data XLSX, PDF, dan print view;
- entitlement `report_export` dari paket subscription;
- halaman laporan tetap read-only ketika entitlement ekspor tidak tersedia.

Cakupan API v1 mencakup:

- login/logout Sanctum dan entitlement `api_access`;
- pembuatan, scoped abilities, one-time reveal, serta pencabutan personal token;
- envelope error, rate middleware, ability enforcement, dan tenant isolation;
- CRUD rekening, kategori, budget, serta lifecycle transaksi melalui API;
- source transaksi API dan efek saldo yang benar;
- endpoint laporan dengan scope terpisah;
- ingestion integration yang idempotent;
- sanitasi header/payload operasional dan penyimpanan hasil ekstraksi AI;
- response `needs_review` tanpa membuat master rekening atau kategori otomatis.

Baseline Phase 8: 102 test, 584 assertion, Pint, dan production build Vite lulus.
