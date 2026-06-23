<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\Analytics\ReportFilter;
use App\Services\Analytics\ReportService;
use Illuminate\Http\Request;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    /**
     * Export the (filtered) appointments table as CSV or Excel (.xlsx).
     * Reuses the exact same ReportFilter as the dashboard, so the export always
     * matches what the user is looking at.
     */
    public function appointments(Request $request, string $format)
    {
        abort_unless(in_array($format, ['csv', 'xlsx'], true), 404);

        $filter = ReportFilter::fromRequest($request);
        $service = new ReportService($filter);

        $header = ['Date', 'Patient', 'Dentist', 'Service', 'Category', 'Status', 'Type', 'Charged', 'Collected', 'Balance'];

        $rows = $service->tableQuery()->with('payments')->get()->map(fn (Appointment $a) => [
            $a->scheduled_at?->format('Y-m-d H:i'),
            $a->patient?->fullName() ?? '—',
            $a->dentist?->name ?? '—',
            $a->service?->name ?? '—',
            $a->service?->category?->label() ?? '—',
            $a->status->label(),
            $a->is_walk_in ? 'Walk-in' : 'Booked',
            number_format((float) $a->total_amount, 2, '.', ''),
            number_format($a->amountPaid(), 2, '.', ''),
            number_format($a->balance(), 2, '.', ''),
        ]);

        $filename = 'appointments_'.$filter->from->toDateString().'_to_'.$filter->to->toDateString();

        return $format === 'xlsx'
            ? $this->xlsx($header, $rows, $filename)
            : $this->csv($header, $rows, $filename);
    }

    private function csv(array $header, $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename.'.csv', ['Content-Type' => 'text/csv']);
    }

    private function xlsx(array $header, $rows, string $filename)
    {
        $tmp = tempnam(sys_get_temp_dir(), 'rpt').'.xlsx';

        $writer = new XlsxWriter;
        $writer->openToFile($tmp);
        $writer->addRow(Row::fromValues($header));
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        return response()->download($tmp, $filename.'.xlsx')->deleteFileAfterSend(true);
    }
}
