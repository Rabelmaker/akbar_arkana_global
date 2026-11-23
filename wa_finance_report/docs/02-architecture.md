# Architecture

Aplikasi menggunakan modular monolith Laravel. Web dashboard menggunakan Inertia response dan Vue pages. Integrasi eksternal akan menggunakan `/api/v1` dengan Sanctum.

Boundary awal:

- `app/Actions/Accounts`: pembuatan user + account, perubahan profil account, dan pergantian active account.
- `app/Enums`: status domain.
- `app/Http/Controllers/Admin` dan `User`: web boundary terpisah.
- `app/Http/Middleware/EnsureActiveAccount`: isolasi konteks account.
- `app/Policies/AccountPolicy`: otorisasi berdasarkan permission global dan membership account.
- `app/Actions/Subscriptions`: perubahan plan/subscription dan provisioning trial.
- `app/Http/Middleware/EnsureActiveSubscription`: entitlement guard untuk route mutasi.
- `app/Console/Commands/ExpireSubscriptions`: sinkronisasi status expiry harian.
- `resources/js/Layouts`: `AdminLayout`, `UserLayout`, dan base Materialize layout.

Semua tabel finansial pada phase berikutnya wajib memiliki `account_id` dan query harus dibatasi ke account aktif.

Account settings tidak menerima ID tenant dari form. Target update selalu diambil dari
`activeAccount` yang sudah diverifikasi middleware. Pergantian account memakai route model
binding, policy membership, dan pemeriksaan status aktif.

Subscription disimpan sebagai histori append-friendly. Pemberian entitlement aktif baru
membatalkan entitlement aktif sebelumnya di dalam database transaction; baris lama tidak
dihapus. Pemeriksaan akses tetap memakai status dan rentang tanggal agar aman walaupun
scheduler expiry terlambat.
