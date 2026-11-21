<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Request;
use App\Services\ReportService;
use App\Services\VoucherSyncService;

class ReportController extends BaseController
{
    public function index(Request $request): void
    {
        require_auth();
        if (can_write_data()) {
            (new VoucherSyncService())->syncAll();
        }
        [$filters, $dateFrom, $dateTo] = $this->reportFilters($request);
        $hasFiltered = $this->hasFiltered($request);
        $summary = ['voucher_count' => 0, 'revenue' => 0];
        $transactions = [];
        if ($hasFiltered) {
            $service = new ReportService();
            $summary = $service->getSummary($filters);
            $transactions = $service->getTransactions($filters);
        }
        $tenants = $this->tenants();
        $routers = $this->scopedOptions('rb_routers');
        $deleteLabel = $this->monthLabel((int) $filters['year'], (int) $filters['month_number']);
        $this->view('reports/index', compact('summary', 'transactions', 'tenants', 'routers', 'filters', 'dateFrom', 'dateTo', 'hasFiltered', 'deleteLabel'));
    }

    public function exportPdf(Request $request): void
    {
        require_auth();
        if (can_write_data()) {
            (new VoucherSyncService())->syncAll();
        }
        [$filters, $dateFrom, $dateTo] = $this->reportFilters($request);
        $service = new ReportService();
        $summary = $service->getSummary($filters);
        $transactions = $service->getTransactions($filters, 3000);

        $lines = [
            'Laporan Penjualan Voucher',
            'Periode: ' . $dateFrom . ' s/d ' . $dateTo,
            'Total Voucher: ' . $summary['voucher_count'],
            'Total Pendapatan: ' . formatRupiah($summary['revenue']),
            '',
            str_pad('Tanggal', 17) . str_pad('Tenant', 18) . str_pad('Batch', 16) . str_pad('Router', 18) . str_pad('Voucher', 18) . str_pad('Profile', 18) . 'Harga',
            str_repeat('-', 120),
        ];
        foreach ($transactions as $row) {
            $lines[] = str_pad(date('d/m/Y H:i', strtotime($row['sold_at'] ?? $row['created_at'])), 17)
                . str_pad(substr((string) ($row['tenant_name'] ?? '-'), 0, 17), 18)
                . str_pad(substr((string) ($row['batch_code'] ?? '-'), 0, 15), 16)
                . str_pad(substr((string) ($row['router_name'] ?? 'Semua'), 0, 17), 18)
                . str_pad(substr((string) $row['username'], 0, 17), 18)
                . str_pad(substr((string) ($row['profile_name'] ?? '-'), 0, 17), 18)
                . formatRupiah($row['price']);
        }

        $this->pdf($lines, 'laporan-penjualan-voucher.pdf');
    }

    public function deleteMonth(Request $request): void
    {
        require_not_technician();
        Csrf::verify();
        $monthValue = (string) $request->input('delete_month');
        if (!preg_match('/^(\d{4})-(\d{2})$/', $monthValue, $matches)) {
            flash('error', 'Pilih bulan laporan yang valid.');
            redirect('/reports');
        }
        $year = (int) $matches[1];
        $month = (int) $matches[2];
        $tenantId = is_super_admin() ? ($request->input('tenant_id') ? (int) $request->input('tenant_id') : null) : $this->scope->currentTenantId();
        $deleted = (new ReportService())->deleteMonth($year, $month, $tenantId);
        audit_log($tenantId, current_user()['id'], 'delete monthly report', $monthValue . ' / ' . $deleted . ' transaksi');
        flash('success', $deleted . ' transaksi laporan bulan ' . $monthValue . ' dihapus dari laporan.');
        redirect('/reports?filtered=1&month=' . sprintf('%02d', $month) . '&year=' . $year . ($tenantId ? '&tenant_id=' . $tenantId : ''));
    }

    private function reportFilters(Request $request): array
    {
        $month = max(1, min(12, (int) $request->input('month', date('m'))));
        $year = max(2000, min(2100, (int) $request->input('year', date('Y'))));
        $dateFrom = sprintf('%04d-%02d-01', $year, $month);
        $dateTo = date('Y-m-t', strtotime($dateFrom));

        $day = $request->input('date') ? max(1, min(31, (int) $request->input('date'))) : null;
        if ($day !== null && checkdate($month, $day, $year)) {
            $dateFrom = $dateTo = sprintf('%04d-%02d-%02d', $year, $month, $day);
        }

        $filters = [
            'date' => $day ? (string) $day : '',
            'month_number' => $month,
            'month' => sprintf('%02d', $month),
            'year' => $year,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'tenant_id' => is_super_admin() ? ($request->input('tenant_id') ? (int) $request->input('tenant_id') : null) : $this->scope->currentTenantId(),
            'router_id' => $request->input('router_id') ? (int) $request->input('router_id') : null,
        ];

        if (is_technician()) {
            $routerIds = $this->scope->currentRouterIds();
            if ($filters['router_id'] && !in_array((int) $filters['router_id'], $routerIds, true)) {
                $filters['router_id'] = -1;
            }
            if (!$filters['router_id']) {
                $filters['router_ids'] = $routerIds ?: [-1];
            }
        }

        return [$filters, $dateFrom, $dateTo];
    }

    private function hasFiltered(Request $request): bool
    {
        return $request->input('filtered') !== null || $request->input('date') || $request->input('month') || $request->input('year') || $request->input('tenant_id') || $request->input('router_id');
    }

    private function monthLabel(int $year, int $month): string
    {
        $months = [1 => 'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI', 'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'];
        return ($months[$month] ?? sprintf('%02d', $month)) . ' ' . $year;
    }

    private function pdf(array $lines, string $filename): void
    {
        $pages = array_chunk($lines, 52);
        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $pageRefs = [];
        foreach ($pages as $page) {
            $contentId = count($objects) + 2;
            $pageRefs[] = (count($objects) + 1) . ' 0 R';
            $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents {$contentId} 0 R >>";
            $objects[] = $this->pdfStream($page);
        }
        $objects[1] = '<< /Type /Pages /Kids [' . implode(' ', $pageRefs) . '] /Count ' . count($pageRefs) . ' >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[$index + 1] = strlen($pdf);
            $pdf .= ($index + 1) . " 0 obj\n{$object}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        echo $pdf;
        exit;
    }

    private function pdfStream(array $lines): string
    {
        $content = "BT\n/F1 9 Tf\n50 800 Td\n";
        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $content .= "0 -14 Td\n";
            }
            $content .= '(' . $this->pdfText($line) . ") Tj\n";
        }
        $content .= 'ET';
        return "<< /Length " . strlen($content) . " >>\nstream\n{$content}\nendstream";
    }

    private function pdfText(string $text): string
    {
        $text = preg_replace('/[^\x20-\x7E]/', '?', $text) ?? $text;
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
