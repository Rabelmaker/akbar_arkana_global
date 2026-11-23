# Financial Reports

Modul laporan tersedia pada `/reports` untuk user tenant yang memiliki permission
`view reports`. Semua query selalu dibatasi oleh `activeAccount`.

## Data dan aturan agregasi

- periode default adalah awal sampai akhir bulan berjalan;
- status default adalah `posted`;
- transaksi reversal turunan (`reversed_transaction_id` terisi) tidak dihitung;
- pemasukan memakai `amount`;
- pengeluaran memakai `total_amount` agar biaya transaksi ikut tercatat;
- arus kas bersih adalah pemasukan dikurangi pengeluaran;
- saldo rekening adalah saldo berjalan saat ini, bukan saldo historis akhir periode;
- realisasi budget mengikuti periode asli masing-masing budget.

Perbandingan periode menggunakan rentang tepat sebelum periode aktif dengan jumlah hari
yang sama. Rentang laporan dibatasi maksimal dua tahun.

## Filter

- tanggal mulai dan selesai;
- jenis serta status transaksi;
- rekening/sumber dana;
- kategori;
- sumber input;
- user pembuat;
- nominal minimum dan maksimum.

ID rekening, kategori, dan user divalidasi terhadap tenant aktif sehingga tidak dapat
digunakan untuk membaca data tenant lain.

## Output

| Route | Output |
| --- | --- |
| `GET /reports` | Dashboard Inertia dan rincian berhalaman |
| `GET /reports/print` | Print view HTML |
| `GET /reports/export/csv` | CSV UTF-8 dengan BOM |
| `GET /reports/export/xlsx` | Sumber data JSON untuk workbook ExcelJS |
| `GET /reports/export/pdf` | PDF A4 landscape melalui Dompdf |

CSV, XLSX, dan PDF membutuhkan permission `export reports`, subscription aktif, serta
paket dengan entitlement `report_export`. ExcelJS dimuat secara dinamis hanya ketika user
memilih XLSX agar halaman laporan utama tetap ringan.

## Arsitektur

- `ReportFilterRequest` menangani otorisasi dan validasi tenant;
- `FinancialReportService` menjadi satu sumber query untuk website dan ekspor;
- `ReportExportService` menangani representasi CSV dan PDF;
- `ReportController` hanya mengorkestrasi request, service, dan response;
- `User/Reports/Index.vue` menangani visualisasi serta pembuatan workbook XLSX.
