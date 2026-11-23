<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Keuangan {{ $account->name }}</title>
    <style>
        @page { margin: 22px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #34364a; font: 12px/1.45 "DejaVu Sans", Arial, sans-serif; background: #fff; }
        .toolbar { display: flex; justify-content: flex-end; margin: 0 0 18px; }
        .toolbar button { border: 0; border-radius: 8px; padding: 10px 18px; color: #fff; background: #7367f0; font-weight: 700; cursor: pointer; }
        .header { padding-bottom: 18px; margin-bottom: 20px; border-bottom: 2px solid #7367f0; }
        .brand { color: #7367f0; font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; }
        h1 { margin: 5px 0 3px; color: #2f3044; font-size: 24px; }
        .muted { color: #77798b; }
        .metrics { width: 100%; margin-bottom: 20px; border-spacing: 10px 0; }
        .metrics td { width: 25%; padding: 13px; border: 1px solid #e7e7ef; border-radius: 8px; }
        .metrics span { display: block; color: #77798b; font-size: 10px; }
        .metrics strong { display: block; margin-top: 4px; font-size: 16px; }
        .income { color: #28c76f; }
        .expense { color: #ea5455; }
        .net { color: #7367f0; }
        h2 { margin: 18px 0 8px; font-size: 15px; }
        table.data { width: 100%; border-collapse: collapse; font-size: 10px; }
        .data th { padding: 8px 6px; color: #595b70; background: #f4f3ff; border-bottom: 1px solid #d9d7f7; text-align: left; }
        .data td { padding: 7px 6px; border-bottom: 1px solid #ececf2; vertical-align: top; }
        .data .money { white-space: nowrap; text-align: right; }
        .data tr { page-break-inside: avoid; }
        .footer { margin-top: 16px; color: #8c8e9f; font-size: 9px; text-align: right; }
        @media print {
            .toolbar { display: none; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    @isset($printMode)
        <div class="toolbar"><button onclick="window.print()">Cetak / Simpan PDF</button></div>
    @endisset

    <div class="header">
        <div class="brand">WA Finance Report</div>
        <h1>Laporan Keuangan</h1>
        <div class="muted">{{ $account->name }} · {{ \Carbon\Carbon::parse($filters['date_from'])->translatedFormat('d M Y') }} – {{ \Carbon\Carbon::parse($filters['date_to'])->translatedFormat('d M Y') }}</div>
    </div>

    <table class="metrics">
        <tr>
            <td><span>Pemasukan</span><strong class="income">Rp {{ number_format($report['summary']['income'], 0, ',', '.') }}</strong></td>
            <td><span>Pengeluaran</span><strong class="expense">Rp {{ number_format($report['summary']['expense'], 0, ',', '.') }}</strong></td>
            <td><span>Arus Kas Bersih</span><strong class="net">Rp {{ number_format($report['summary']['net'], 0, ',', '.') }}</strong></td>
            <td><span>Total Transaksi</span><strong>{{ number_format($report['summary']['transaction_count'], 0, ',', '.') }}</strong></td>
        </tr>
    </table>

    <h2>Rincian Transaksi</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Nomor</th>
                <th>Jenis</th>
                <th>Deskripsi</th>
                <th>Rekening</th>
                <th>Kategori</th>
                <th>Status</th>
                <th class="money">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['transaction_number'] }}</td>
                    <td>{{ $row['type_label'] }}</td>
                    <td>{{ $row['description'] }}</td>
                    <td>{{ $row['financial_account'] }}@if($row['destination_account']) → {{ $row['destination_account'] }}@endif</td>
                    <td>{{ $row['category'] ?: '—' }}</td>
                    <td>{{ $row['status_label'] }}</td>
                    <td class="money">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="padding: 24px; text-align: center;">Tidak ada transaksi pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="footer">Dibuat oleh WA Finance pada {{ now()->translatedFormat('d M Y H:i') }}</div>
</body>
</html>
