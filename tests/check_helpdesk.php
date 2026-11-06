<?php
// Self-check for app/Support/Helpdesk.php (mirror of PMkanban scripts/check_helpdesk.mjs). Run: php tests/check_helpdesk.php
require __DIR__.'/../vendor/autoload.php';

use App\Support\Auth;
use App\Support\Helpdesk as H;

ini_set('zend.assertions', 1);
ini_set('assert.exception', 1);

$ok = [
    'tanggal' => '2026-09-10', 'jam_mulai' => '08:00', 'jam_selesai' => '08:45', 'pic_id' => 5, 'kategori_id' => 1,
    'unit_kerja_id' => '', 'isi_helpdesk' => ' Printer macet ', 'tindakan' => '', 'status' => 'Selesai',
    'jenis_pelayanan' => 'Pelayanan', 'staff_ids' => ['u1', 'u1', 'u4'],
];
[$d, $err] = H::validateTicket($ok);
assert($err === null);
assert($d['isi_helpdesk'] === 'Printer macet' && $d['unit_kerja_id'] === null && $d['tindakan'] === null);
assert($d['staff_ids'] === ['u1', 'u4']);
assert(H::validateTicket(['jam_mulai' => '09:00:00', 'jam_selesai' => '09:30:59'] + $ok)[0]['jam_selesai'] === '09:30');

$e = fn ($over) => H::validateTicket($over + $ok)[1] ?? '';
assert(str_contains($e(['jam_selesai' => '07:59']), 'Jam selesai'));
assert(str_contains($e(['jam_mulai' => '25:00']), 'Format jam'));
assert(str_contains($e(['tanggal' => '2026-13-40']), 'Tanggal'));
assert(str_contains($e(['staff_ids' => []]), 'staf IT'));
assert(H::validateTicket(['status' => 'Open', 'staff_ids' => []] + $ok)[1] === null);
assert(str_contains($e(['status' => 'Done']), 'Status'));
assert(str_contains($e(['pic_id' => 'abc']), 'PIC'));
assert(str_contains($e(['kategori_id' => 0]), 'Kategori'));
assert(str_contains($e(['isi_helpdesk' => '   ']), 'Isi'));
assert(str_contains(H::validateTicket(null)[1], 'Body'));

assert(H::diffTicket(['status' => 'Open', 'tindakan' => null], ['status' => 'Proses', 'tindakan' => null])
    === [['field' => 'status', 'label' => 'Status', 'old' => 'Open', 'new' => 'Proses']]);
assert(count(H::diffTicket(['total_menit' => 1, 'tindakan' => ''], ['total_menit' => 2, 'tindakan' => null])) === 0);

$tinta = ['nama_barang' => ' Tinta hitam ', 'qty' => 2, 'harga' => 85000];
assert(H::parseBiaya([$tinta, ['nama_barang' => 'Kabel', 'qty' => 1, 'harga' => 0]])
    === [['nama_barang' => 'Tinta hitam', 'qty' => 2, 'harga' => 85000], ['nama_barang' => 'Kabel', 'qty' => 1, 'harga' => 0]]);
assert(H::parseBiaya(null) === []);
assert(str_contains(H::parseBiaya([['qty' => 0] + $tinta]), 'Qty'));
assert(str_contains(H::parseBiaya([['harga' => -1] + $tinta]), 'Harga'));
assert(str_contains(H::parseBiaya([['harga' => 1.5] + $tinta]), 'Harga'));
assert(str_contains(H::parseBiaya([['nama_barang' => ''] + $tinta]), 'Nama barang'));
assert(str_contains(H::parseBiaya('x'), 'tidak valid'));
assert(str_contains($e(['biaya' => [['qty' => 'a'] + $tinta]]), 'Qty'));
assert(count(H::validateTicket(['biaya' => [$tinta]] + $ok)[0]['biaya']) === 1);
assert(H::diffTicket(['biaya' => []], ['biaya' => [['nama_barang' => 'Tinta', 'qty' => 2, 'harga' => 5]]])
    === [['field' => 'biaya', 'label' => 'Biaya', 'old' => null, 'new' => 'Tinta 2x5']]);
$a = ['biaya' => [['nama_barang' => 'A', 'qty' => 1, 'harga' => 1]]];
assert(count(H::diffTicket($a, $a)) === 0);

assert(H::safeCell('=HYPERLINK("x")') === "'=HYPERLINK(\"x\")");
assert(H::safeCell('-1+2') === "'-1+2");
assert(H::safeCell('Normal') === 'Normal');
assert(H::safeCell(15) === 15);

assert(Auth::canHelpdesk((object) ['unit_id' => 'unit_it', 'role' => 'user']));
assert(! Auth::canHelpdesk((object) ['unit_id' => 'unit_mfk', 'role' => 'admin']));
assert(Auth::canHelpdeskMaster((object) ['unit_id' => 'unit_it', 'role' => 'admin']));
assert(! Auth::canHelpdeskMaster((object) ['unit_id' => 'unit_it', 'role' => 'user']));
assert(! Auth::canHelpdesk(null));

// password format round-trip (salt:pbkdf2-sha512 hex)
$h = Auth::hashPassword('rahasia');
assert(Auth::verifyPassword('rahasia', $h) && ! Auth::verifyPassword('salah', $h) && ! Auth::verifyPassword('x', ''));

echo "check_helpdesk: OK\n";
