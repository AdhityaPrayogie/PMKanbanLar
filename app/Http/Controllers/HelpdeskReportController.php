<?php

namespace App\Http\Controllers;

use App\Support\Helpdesk as H;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// helpdesk/stats & helpdesk/export — 'helpdesk' Gate (TD only) in routes/api.php
class HelpdeskReportController extends Controller
{
    const MONTH = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];

    // GET /api/helpdesk/stats?month=9&year=2026 (default: month of the latest ticket up to today, else current month)
    public function stats(Request $request)
    {
        $sp = $request->query();
        $now = new \DateTime();
        if (! self::truthy($sp['month'] ?? null) && ($d = DB::selectOne('SELECT MAX(tanggal) AS d FROM hd_tickets WHERE tanggal <= CURDATE()')->d)) $now = new \DateTime($d);
        $q = fn ($k) => ($v = H::num($sp[$k] ?? null)) && ! is_nan($v) ? (int) $v : 0;
        $month = min(max($q('month') ?: (int) $now->format('n'), 1), 12);
        $year = min(max($q('year') ?: (int) $now->format('Y'), 2000), 2100);
        $start = sprintf('%04d-%02d-01', $year, $month);
        $range = 't.tanggal >= ? AND t.tanggal < DATE_ADD(?, INTERVAL 1 MONTH)';
        $trendRange = 't.tanggal >= DATE_SUB(?, INTERVAL 11 MONTH) AND t.tanggal < DATE_ADD(?, INTERVAL 1 MONTH)';
        $s2 = [$start, $start];

        $cards = DB::selectOne("SELECT COUNT(*) AS total, SUM(t.tanggal = CURDATE()) AS hari_ini,
           SUM(t.status = 'Proses') AS proses, SUM(t.status = 'Selesai') AS selesai, SUM(t.status = 'Batal') AS batal,
           COALESCE(SUM(t.total_menit), 0) AS total_menit,
           (SELECT COALESCE(SUM(b.qty * b.harga), 0) FROM hd_ticket_biaya b JOIN hd_tickets t ON t.id = b.ticket_id WHERE $range) AS total_biaya
         FROM hd_tickets t WHERE $range", [$start, $start, $start, $start]);
        // The queue is not month-bound: an old Open request is still waiting
        $open = DB::selectOne("SELECT COUNT(*) AS open FROM hd_tickets WHERE status = 'Open'")->open;
        $perKategori = DB::select("SELECT k.nama, COUNT(*) AS tiket FROM hd_tickets t JOIN hd_kategori k ON k.id = t.kategori_id
         WHERE $range GROUP BY k.id, k.nama ORDER BY tiket DESC", $s2);
        $recent = DB::select(H::TICKET_SELECT.' ORDER BY t.updated_at DESC, t.id DESC LIMIT 10');
        $perHari = DB::select("SELECT DAY(t.tanggal) AS hari, COUNT(*) AS tiket, COALESCE(SUM(t.total_menit), 0) AS menit
         FROM hd_tickets t WHERE $range GROUP BY hari", $s2);
        $trend = DB::select("SELECT DATE_FORMAT(t.tanggal, '%Y-%m') AS ym, COUNT(*) AS tiket, COALESCE(SUM(t.total_menit), 0) AS menit,
           ROUND(AVG(t.total_menit)) AS rata_menit, SUM(t.status = 'Selesai') AS selesai,
           COALESCE(SUM((SELECT SUM(b.qty * b.harga) FROM hd_ticket_biaya b WHERE b.ticket_id = t.id)), 0) AS biaya
         FROM hd_tickets t WHERE $trendRange GROUP BY ym", $s2);
        $trendKategori = DB::select("SELECT DATE_FORMAT(t.tanggal, '%Y-%m') AS ym, k.nama, COUNT(*) AS tiket
         FROM hd_tickets t JOIN hd_kategori k ON k.id = t.kategori_id WHERE $trendRange GROUP BY ym, k.id, k.nama", $s2);

        $byYm = array_column($trend, null, 'ym');
        $n = fn ($v) => $v === null ? 0 : $v + 0;
        $months = [];
        for ($i = 0; $i < 12; $i++) $months[] = (new \DateTime($start))->modify(($i - 11).' month')->format('Y-m');
        return response()->json([
            'month' => $month, 'year' => $year,
            'cards' => ['total' => $n($cards->total), 'hari_ini' => $n($cards->hari_ini), 'open' => $n($open), 'proses' => $n($cards->proses),
                'selesai' => $n($cards->selesai), 'batal' => $n($cards->batal), 'total_menit' => $n($cards->total_menit), 'total_biaya' => $n($cards->total_biaya)],
            'trend' => array_map(function ($ym) use ($byYm, $n) {
                $r = $byYm[$ym] ?? null;
                return ['ym' => $ym, 'tiket' => $n($r?->tiket), 'menit' => $n($r?->menit), 'selesai' => $n($r?->selesai),
                    'rata_menit' => $r?->rata_menit === null ? null : $n($r->rata_menit), 'biaya' => $n($r?->biaya)];
            }, $months),
            'trendKategori' => array_map(fn ($r) => ['ym' => $r->ym, 'nama' => $r->nama, 'tiket' => $n($r->tiket)], $trendKategori),
            'perKategori' => array_map(fn ($k) => ['nama' => $k->nama, 'tiket' => $n($k->tiket)], $perKategori),
            'recent' => array_map([H::class, 'toTicket'], $recent),
            'perHari' => array_map(fn ($d) => ['hari' => $n($d->hari), 'tiket' => $n($d->tiket), 'menit' => $n($d->menit)], $perHari),
        ]);
    }

    // Same layout as it-logbook "Laporan_Logbook": dark-blue header, frozen row 1, TOTAL MENIT footer (A:F merged)
    public function export(Request $request)
    {
        $sp = $request->query();
        $start = is_string($sp['start'] ?? null) ? $sp['start'] : '';
        $end = is_string($sp['end'] ?? null) ? $sp['end'] : '';
        if (! H::isDate($start) || ! H::isDate($end) || $start > $end) return self::err('Parameter start dan end (YYYY-MM-DD) wajib diisi', 400);
        [$where, $p] = H::ticketFilter($sp);
        $rows = DB::select(H::TICKET_SELECT." $where ORDER BY t.tanggal ASC, t.id ASC", $p);

        [$y1, $m1] = array_map('intval', explode('-', $start));
        [$y2, $m2] = array_map('intval', explode('-', $end));
        $name = self::MONTH[$m1 - 1].' '.substr((string) $y1, -2);
        if ($y1 !== $y2 || $m1 !== $m2) $name .= '-'.self::MONTH[$m2 - 1].' '.substr((string) $y2, -2);

        $wb = new Spreadsheet();
        $ws = $wb->getActiveSheet()->setTitle($name);
        $cols = [['No', 5], ['Tgl', 12], ['Nama Kegiatan', 53], ['Jenis', 12], ['waktu mulai pengerjaan', 21], ['waktu selesai pengerjaan', 22],
            ['waktu selesai (Menit)', 19], ['PIC', 26], ['Customer', 40], ['Kategori', 15], ['Barang', 40], ['Biaya (Rp)', 14]];
        foreach ($cols as $i => [$h, $w]) {
            $c = chr(65 + $i);
            $ws->setCellValueExplicit("{$c}1", $h, 's');
            $ws->getColumnDimension($c)->setWidth($w);
        }
        $ws->getStyle('A1:L1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'name' => 'Calibri', 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF1F4E78']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ]);
        $ws->freezePane('A2');

        $totalMenit = $totalRp = 0;
        foreach ($rows as $i => $raw) {
            $r = H::toTicket($raw);
            $menit = (int) ($r['total_menit'] ?? 0);
            $totalMenit += $menit;
            $totalRp += $r['total_biaya'];
            [$y, $m, $d] = explode('-', $r['tanggal']);
            $vals = [$i + 1, "$d/$m/$y", H::safeCell($r['isi_helpdesk']), H::safeCell($r['kategori']),
                $r['jam_mulai'] ? "{$r['jam_mulai']}:00" : '', $r['jam_selesai'] ? "{$r['jam_selesai']}:00" : '', $menit,
                H::safeCell($r['staff_names'] ?? ''), H::safeCell($r['pic_nama'] ?? $r['requester_name'] ?? ''), $r['jenis_pelayanan'],
                H::safeCell(H::biayaText($r['biaya'])), $r['total_biaya'] ?: ''];
            $row = $i + 2;
            foreach ($vals as $j => $v) {
                $c = chr(65 + $j).$row;
                is_int($v) ? $ws->setCellValue($c, $v) : $ws->setCellValueExplicit($c, (string) $v, 's');
                $ws->getStyle($c)->applyFromArray(['font' => ['size' => 11, 'name' => 'Calibri'],
                    'alignment' => ['horizontal' => in_array($j + 1, [3, 8, 9, 11], true) ? 'left' : ($j + 1 === 12 ? 'right' : 'center'), 'vertical' => 'center']]);
            }
            $ws->getStyle("L$row")->getNumberFormat()->setFormatCode('#,##0');
            $ws->getStyle("J$row")->getFont()->setBold(true)->setSize(10)->setName('Arial')->getColor()->setARGB('FF0A53A8');
        }

        $f = count($rows) + 2;
        $ws->mergeCells("A$f:F$f");
        $ws->getStyle("L$f")->getNumberFormat()->setFormatCode('#,##0');
        foreach (["A$f" => 'TOTAL MENIT', "G$f" => $totalMenit, "K$f" => 'TOTAL BIAYA', "L$f" => $totalRp] as $ref => $v) {
            $ws->setCellValue($ref, $v);
            $ws->getStyle($ref)->applyFromArray(['font' => ['bold' => true, 'size' => 11, 'name' => 'Calibri'], 'alignment' => ['horizontal' => 'center', 'vertical' => 'center']]);
        }

        return response()->streamDownload(fn () => (new Xlsx($wb))->save('php://output'), "Laporan_Logbook_{$start}_sd_{$end}.xlsx",
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
