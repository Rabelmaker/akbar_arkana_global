# Financial Rules

Aturan yang akan menjadi invariant domain:

- Nilai uang menggunakan `decimal(20, 2)`, bukan float.
- Hanya transaksi `posted` memengaruhi saldo.
- Transfer mengurangi sumber dan menambah tujuan dalam satu database transaction.
- Posted transaction tidak dihapus langsung; gunakan cancellation/reversal.
- Idempotency key mencegah input ganda.
- Semua query dan perubahan saldo dibatasi ke account aktif.

Aturan financial foundation yang sudah aktif:

- setiap account baru memperoleh `Kas Utama` dan enam kategori default;
- `current_balance` rekening baru harus sama dengan `opening_balance`;
- perubahan saldo pembuka hanya menggeser saldo berjalan sebesar selisihnya;
- endpoint baca tetap tersedia saat subscription berakhir, sementara mutasi menghasilkan HTTP 402;
- kategori induk harus berada pada tenant yang sama dan tidak boleh membentuk siklus;
- kategori default dapat diedit tetapi tidak dapat dihapus;
- rekening dan kategori menggunakan soft delete serta audit log berisi tenant, actor, IP, dan user agent.

Aturan transaksi yang aktif pada Phase 5:

- draft tidak memengaruhi saldo dan hanya draft yang dapat diedit atau dihapus;
- post mengunci transaksi dan rekening sebelum mengubah saldo;
- income menambah rekening sumber, expense mengurangi nominal beserta biaya;
- transfer mengurangi nominal dan biaya dari sumber serta menambah nominal ke tujuan;
- cancel mengembalikan efek saldo dan menyimpan alasan pembatalan;
- reversal membuat transaksi sistem tertaut dan mempertahankan jejak audit;
- idempotency key tenant mencegah post ganda;
- post ditolak ketika kuota transaksi bulanan paket aktif telah tercapai;
- maksimal lima lampiran privat berformat gambar/PDF dapat disimpan per permintaan;
- list mendukung pencarian, filter, dan pagination server-side;
- dashboard menghitung arus kas harian dari transaksi posted non-reversal.

Aturan budget yang aktif pada Phase 6:

- budget dapat berlaku untuk kategori tertentu, rekening tertentu, kombinasi keduanya,
  atau seluruh pengeluaran;
- realisasi hanya berasal dari expense posted dalam rentang tanggal budget;
- biaya admin expense termasuk dalam nilai realisasi;
- draft, income, transfer, cancelled, reversed, dan transaksi reversal tidak dihitung;
- health `safe`, `warning`, dan `exceeded` diturunkan dari persentase penggunaan serta
  `alert_percentage`;
- budget aktif dengan cakupan sama tidak boleh memiliki periode bertumpang tindih;
- kategori income tidak dapat digunakan sebagai cakupan budget;
- dashboard menampilkan alokasi, realisasi, sisa, dan jumlah budget yang perlu perhatian;
- rollover flag disimpan untuk kesiapan pembuatan periode lanjutan.
