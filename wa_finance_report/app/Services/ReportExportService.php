<?php

namespace App\Services;

use App\Models\Account;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    public function __construct(private FinancialReportService $reports) {}

    /** @param array<string, mixed> $filters */
    public function csv(Account $account, array $filters): StreamedResponse
    {
        $rows = $this->reports->exportRows($account, $filters);
        $filename = "laporan-keuangan-{$filters['date_from']}-{$filters['date_to']}.csv";

        return response()->streamDownload(function () use ($rows): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Nomor', 'Tanggal', 'Jenis', 'Deskripsi', 'Rekening', 'Rekening Tujuan', 'Kategori', 'Nominal', 'Biaya', 'Total', 'Status', 'Sumber', 'Dibuat Oleh']);
            foreach ($rows as $row) {
                fputcsv($stream, [
                    $row['transaction_number'], $row['date'], $row['type_label'], $row['description'],
                    $row['financial_account'], $row['destination_account'], $row['category'],
                    $row['amount'], $row['fee'], $row['total'], $row['status_label'],
                    $row['source'], $row['creator'],
                ]);
            }
            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @param array<string, mixed> $filters */
    public function pdf(Account $account, array $filters): Response
    {
        $report = $this->reports->build($account, $filters);
        $rows = $this->reports->exportRows($account, $filters);
        $html = view('reports.document', compact('account', 'filters', 'report', 'rows'))->render();
        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"laporan-keuangan-{$filters['date_from']}-{$filters['date_to']}.pdf\"",
        ]);
    }
}
