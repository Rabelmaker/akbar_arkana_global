# Database Schema

Schema foundation:

- `users`: profile, status, active account, login metadata, soft delete.
- `accounts`: identitas tenant, locale, timezone, currency, status, creator, soft delete.
- `account_user`: membership, account role, owner flag, join date.
- Spatie Permission tables.
- Sanctum `personal_access_tokens`.
- Spatie `activity_log` dengan tambahan account, IP, dan user agent context.
- `subscription_plans`: harga, periode, durasi, kuota, feature flags, status, dan urutan.
- `subscriptions`: account, plan, periode, status, harga snapshot, pembayaran, renewal,
  notes, dan actor pembuat.
- `financial_accounts`: sumber dana per tenant, jenis rekening, identitas institusi,
  saldo pembuka, saldo berjalan, status, opsi inclusion pada total, dan soft delete.
- `transaction_categories`: kategori pemasukan/pengeluaran per tenant dengan parent
  opsional, slug tenant, visual identity, urutan, status default/aktif, dan soft delete.
- `transactions`: ledger tenant dengan UUID, nomor transaksi, tipe, tanggal, rekening
  sumber/tujuan, kategori, nominal, biaya, status, sumber input, idempotency key, actor,
  approval, hubungan reversal, serta soft delete untuk draft.
- `transaction_attachments`: metadata bukti transaksi privat, termasuk nama asli, path,
  MIME type, ukuran, sumber unggahan, metadata, dan actor pengunggah.
- `budgets`: rencana pengeluaran tenant dengan cakupan kategori dan/atau rekening opsional,
  jenis periode, rentang tanggal, alokasi, ambang alert, rollover flag, status, actor,
  catatan, dan soft delete.

Nilai default tenant: timezone `Asia/Jakarta`, currency `IDR`, locale `id`.

Perubahan profil account dan pergantian active account dicatat ke `activity_log`, termasuk
`account_id`, actor, event, nilai lama/baru atau account asal/tujuan, alamat IP, dan user agent.

Nilai uang plan dan subscription menggunakan `decimal(20, 2)`. Index subscription mencakup
account/status/end date dan plan/status untuk query entitlement serta dashboard admin.

Saldo rekening juga menggunakan `decimal(20, 2)`. Index rekening mencakup tenant/status
dan tenant/type. Index kategori mencakup tenant/type/status dan tenant/parent, sedangkan
slug kategori unik di dalam tenant.

Index transaksi mencakup tenant/tanggal, tenant/status/tipe, dan tenant/rekening. Nomor
transaksi serta UUID unik secara global, sedangkan idempotency key unik di dalam tenant.

Index budget mencakup tenant/status/rentang tanggal serta tenant/kategori/rekening. Nilai
realisasi tidak disimpan sebagai snapshot: nilainya dihitung dari ledger transaksi posted
agar pembatalan dan reversal langsung tercermin.
