{{-- Port of components/helpdesk/HelpdeskView.tsx (+ Dashboard, DayModal, Trend, Queue). JS: resources/js/alpine/helpdesk.js --}}
@php
  $c = require resource_path('views/partials/helpdesk/cls.php');
  $isMasterAdmin = \App\Support\Auth::canHelpdeskMaster($user);
  $muted = 'text-sm text-[#64748b]';
@endphp
<div x-data="helpdeskView" class="max-w-7xl mx-auto space-y-4">
  <div class="flex items-center gap-2">
    <x-icon name="headset" class="w-5 h-5 text-[#034694] dark:text-blue-400" />
    <h1 class="text-xl font-extrabold">Helpdesk IT</h1>
  </div>

  <div role="tablist" aria-label="Menu helpdesk" class="flex flex-wrap gap-1.5">
    <template x-for="[k, label] in tabs" :key="k">
      <button role="tab" type="button" :aria-selected="tab === k" @click="tab = k"
        :class="tab === k ? @js($c['primary']) : @js($c['ghost'])" x-text="label"></button>
    </template>
  </div>

  <template x-if="tab === 'dashboard' || tab === 'trend'">
    <div class="flex items-end gap-2">
      <div>
        <label for="hd-period" class="{{ $c['label'] }}">Periode</label>
        <input id="hd-period" type="month" class="{{ $c['input'] }}" :value="period" @input="$event.target.value && (period = $event.target.value)">
      </div>
    </div>
  </template>

  {{-- Dashboard --}}
  <template x-if="tab === 'dashboard' && !stats"><p class="{{ $muted }}">Memuat…</p></template>
  <template x-if="tab === 'dashboard' && stats">
    <div class="space-y-4">
      <div class="grid gap-3 grid-cols-2 sm:grid-cols-4 lg:grid-cols-8">
        <template x-for="[label, n, color] in cards" :key="label">
          <div class="{{ $c['card'] }} flex flex-col justify-between gap-2">
            <div class="text-xs font-bold text-[#64748b] leading-snug" x-text="label"></div>
            <div :class="[typeof n === 'string' ? 'text-xl font-extrabold tabular-nums' : 'text-3xl font-extrabold', color]" x-text="n"></div>
          </div>
        </template>
      </div>
      <p x-show="stats.cards.total === 0" class="{{ $muted }}">Belum ada tiket di periode ini. Ganti Periode di atas untuk melihat bulan lain.</p>
      <section class="{{ $c['card'] }}">
        <h2 class="text-base font-bold">Tiket per Hari</h2>
        <p class="text-xs text-[#64748b] mb-3" x-text="`${stats.cards.total} tiket · ${stats.cards.total_menit} menit bulan ini`"></p>
        @include('partials.helpdesk.columns', ['cols' => 'dayCols', 'aria' => '`Tiket per hari, ${stats.perHari.length} hari dengan tiket`', 'pick' => 'day = c.key', 'tick' => 5])
      </section>
      <template x-if="day">
        <x-helpdesk.modal x-data="hdDay(day)" title="'Helpdesk ' + fmtDate(day)" close="day = null" wide>
          <p aria-live="polite" class="{{ $c['error'] }}" x-text="error"></p>
          <p x-show="rows === null" class="text-sm">Memuat…</p>
          <p x-show="rows && !rows.length" class="{{ $muted }}">Tidak ada tiket.</p>
          <ul x-show="rows && rows.length" class="divide-y divide-[#f1f5f9] dark:divide-[#162032]">
            <template x-for="t in rows ?? []" :key="t.id">
              <li class="py-2.5 text-sm space-y-1">
                <div class="flex items-start justify-between gap-3">
                  <div class="font-bold">
                    <span x-text="`#${t.id} · ${t.jam_mulai ?? '--'}–${t.jam_selesai ?? '--'}`"></span><span x-show="t.total_menit !== null" class="font-normal text-[#64748b]" x-text="` (${t.total_menit} menit)`"></span><span class="font-normal" x-text="` · ${t.kategori} · ${t.pic_nama ?? t.requester_name ?? '-'}${t.unit_kerja ? ` (${t.unit_kerja})` : ''}`"></span>
                  </div>
                  <span class="{{ $c['badge'] }}" :class="statusCls(t.status)" x-text="t.status"></span>
                </div>
                <p class="whitespace-pre-line leading-relaxed" x-text="t.isi_helpdesk"></p>
                <p x-show="t.tindakan" class="whitespace-pre-line leading-relaxed text-[#475569] dark:text-[#94a3b8]" x-text="'↳ ' + t.tindakan"></p>
                <ul x-show="t.biaya.length > 0" class="text-xs rounded-lg bg-[#f8fafc] dark:bg-[#0b1220] px-3 py-1.5">
                  <template x-for="(b, i) in t.biaya" :key="i">
                    <li class="flex justify-between gap-3 tabular-nums">
                      <span x-text="`${b.nama_barang} × ${b.qty} @ ${rupiah(b.harga)}`"></span><span x-text="rupiah(b.qty * b.harga)"></span>
                    </li>
                  </template>
                  <li class="flex justify-between font-bold border-t border-[#e2e8f0] dark:border-[#1e2a3f] mt-1 pt-1"><span>Total biaya</span><span x-text="rupiah(t.total_biaya)"></span></li>
                </ul>
                <p class="text-[length:calc(11px+var(--fs-up))] text-[#64748b]" x-text="`Staf: ${t.staff_names || '-'} · ${t.jenis_pelayanan}`"></p>
              </li>
            </template>
          </ul>
        </x-helpdesk.modal>
      </template>
      <button x-show="stats.cards.open > 0" type="button" @click="tab = 'antrian'" class="{{ $c['primary'] }}" x-text="`Lihat ${stats.cards.open} antrian pengajuan`"></button>
      <div class="grid gap-4 lg:grid-cols-3">
        <section class="{{ $c['card'] }} lg:col-span-2">
          <h2 class="text-base font-bold mb-2">Aktivitas Terbaru</h2>
          <ul class="divide-y divide-[#f1f5f9] dark:divide-[#162032]">
            <template x-for="t in stats.recent" :key="t.id">
              <li class="py-2.5 text-sm flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <span class="font-bold" x-text="'#' + t.id"></span><span x-text="` · ${fmtDate(t.tanggal)} · ${t.kategori}`"></span>
                  <div class="truncate text-[#475569] dark:text-[#94a3b8]" x-text="t.isi_helpdesk"></div>
                </div>
                <span class="{{ $c['badge'] }}" :class="statusCls(t.status)" x-text="t.status"></span>
              </li>
            </template>
          </ul>
        </section>
        <section class="{{ $c['card'] }}">
          <h2 class="text-base font-bold mb-2">Per Kategori</h2>
          <p x-show="!katBars.length" class="{{ $muted }}">Belum ada data.</p>
          <ul x-show="katBars.length" class="space-y-3">
            <template x-for="r in katBars" :key="r.label">
              <li class="text-sm">
                <div class="flex justify-between"><span x-text="r.label"></span><span class="font-bold" x-text="r.n"></span></div>
                <div class="h-1.5 mt-1 rounded bg-[#e2e8f0] dark:bg-[#1e2a3f]">
                  <div class="h-1.5 rounded bg-[#034694] dark:bg-blue-500" :style="{ width: r.pct + '%' }"></div>
                </div>
              </li>
            </template>
          </ul>
        </section>
      </div>
    </div>
  </template>

  {{-- Antrian --}}
  <template x-if="tab === 'antrian'">
    <div>
      <div x-show="!queue.length" class="{{ $c['card'] }} {{ $muted }}">Tidak ada antrian. Semua pengajuan sudah ditangani.</div>
      <div x-show="queue.length" class="space-y-2">
        <template x-for="t in queue" :key="t.id">
          <div class="{{ $c['card'] }} flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="text-sm min-w-0">
              <div class="font-bold">
                <span x-text="`#${t.id} · ${t.kategori} · ${t.unit_kerja ?? '-'}`"></span><span class="font-normal text-[#64748b]" x-text="` · ${fmtDate(t.tanggal)} · ${t.requester_name ?? t.pic_nama} (${t.requester_unit ?? '-'})`"></span>
              </div>
              <p class="whitespace-pre-line leading-relaxed mt-1" x-text="t.isi_helpdesk"></p>
            </div>
            <div class="flex gap-2 shrink-0">
              <button type="button" :disabled="busy === t.id" @click="ambil(t)" class="{{ $c['primary'] }}">
                <x-icon name="hand" class="w-3.5 h-3.5" /> Ambil
              </button>
              <button type="button" @click="assigning = t" class="{{ $c['ghost'] }}">Tugaskan</button>
            </div>
          </div>
        </template>
      </div>
      <template x-if="assigning">
        <x-helpdesk.modal title="'Tugaskan Tiket #' + assigning?.id" close="assigning = null" wide>
          @include('partials.helpdesk.ticket-form', ['init' => "hdTicketForm({ ...assigning, status: 'Proses', staff_ids: [] }, () => { assigning = null; bump() })"])
        </x-helpdesk.modal>
      </template>
    </div>
  </template>

  {{-- Input: re-created on every version bump (React key={version}) --}}
  <template x-if="tab === 'input'">
    <div class="{{ $c['card'] }}">
      <template x-for="v in [version]" :key="v">
        <div>
          @include('partials.helpdesk.ticket-form', ['init' => 'hdTicketForm(null, (t) => { saved = `Tiket #${t.id} tersimpan.`; bump() })'])
        </div>
      </template>
      <p x-show="saved" aria-live="polite" class="text-sm font-semibold text-emerald-600 mt-2" x-text="saved"></p>
    </div>
  </template>

  <template x-if="tab === 'data'">
    @include('partials.helpdesk.table')
  </template>

  {{-- Trend --}}
  <template x-if="tab === 'trend' && !stats"><p class="{{ $muted }}">Memuat…</p></template>
  <template x-if="tab === 'trend' && stats">
    @php($th = 'py-2 px-2 text-right font-semibold whitespace-nowrap')
    @php($td = 'py-1.5 px-2 text-right tabular-nums')
    <div class="space-y-4">
      <section class="{{ $c['card'] }}">
        <h2 class="text-base font-bold">Trend Tiket 12 Bulan</h2>
        <p class="text-xs text-[#64748b] mb-3"
          x-text="`${ymLabel(stats.trend[0].ym)} – ${ymLabel(stats.trend[stats.trend.length - 1].ym)} · ${sum('tiket')} tiket · ${sum('menit')} menit`"></p>
        @include('partials.helpdesk.columns', ['cols' => 'trendCols', 'aria' => "'Jumlah tiket per bulan, 12 bulan'", 'pick' => "period = c.key; tab = 'dashboard'"])
      </section>
      <section class="{{ $c['card'] }} overflow-x-auto">
        <h2 class="text-base font-bold mb-2">Rekap per Bulan</h2>
        <table class="w-full text-sm">
          <thead class="text-[#64748b] border-b border-[#e2e8f0] dark:border-[#1e2a3f]">
            <tr>
              <th class="py-2 pr-2 text-left font-semibold">Bulan</th>
              <th class="{{ $th }}">Tiket</th><th class="{{ $th }}">Selesai</th><th class="{{ $th }}">Total Menit</th><th class="{{ $th }}">Rata-rata Menit</th><th class="{{ $th }}">Biaya</th>
              <template x-for="k in kat" :key="k"><th class="{{ $th }}" x-text="k"></th></template>
            </tr>
          </thead>
          <tbody>
            <template x-for="t in [...stats.trend].reverse()" :key="t.ym">
              <tr class="border-b border-[#f1f5f9] dark:border-[#162032]">
                <td class="py-1.5 pr-2 font-semibold" x-text="ymLabel(t.ym)"></td>
                <td class="{{ $td }}" x-text="t.tiket"></td><td class="{{ $td }}" x-text="t.selesai"></td>
                <td class="{{ $td }}" x-text="t.menit"></td><td class="{{ $td }}" x-text="t.rata_menit ?? '-'"></td>
                <td class="{{ $td }} whitespace-nowrap" x-text="t.biaya ? rupiah(t.biaya) : '-'"></td>
                <template x-for="k in kat" :key="k"><td class="{{ $td }}" x-text="cell(t.ym, k) || '-'"></td></template>
              </tr>
            </template>
          </tbody>
          <tfoot class="font-bold border-t border-[#cbd5e1] dark:border-[#24334a]">
            <tr>
              <td class="py-2 pr-2">Total</td>
              <td class="{{ $td }}" x-text="sum('tiket')"></td><td class="{{ $td }}" x-text="sum('selesai')"></td>
              <td class="{{ $td }}" x-text="sum('menit')"></td><td class="{{ $td }}"></td>
              <td class="{{ $td }} whitespace-nowrap" x-text="rupiah(sum('biaya'))"></td>
              <template x-for="k in kat" :key="k"><td class="{{ $td }}" x-text="katSum(k)"></td></template>
            </tr>
          </tfoot>
        </table>
      </section>
    </div>
  </template>

  {{-- Master (admin TD only; also guarded server-side) --}}
  @if ($isMasterAdmin)
  <template x-if="tab === 'master'">
    <div x-data="hdMaster" class="{{ $c['card'] }} space-y-3">
      <div role="tablist" aria-label="Jenis master" class="flex gap-2">
        <template x-for="[k, label] in TYPES" :key="k">
          <button role="tab" :aria-selected="type === k" type="button" @click="type = k"
            :class="type === k ? @js($c['primary']) : @js($c['ghost'])" x-text="label"></button>
        </template>
      </div>
      <form @submit.prevent="add()" class="flex gap-2">
        <label for="m-nama" class="sr-only">Nama baru</label>
        <input id="m-nama" required class="{{ $c['input'] }}" placeholder="Tambah nama baru…" x-model="nama">
        <button type="submit" class="{{ $c['primary'] }}"><x-icon name="plus" class="w-3.5 h-3.5" /> Tambah</button>
      </form>
      <p aria-live="polite" class="{{ $c['error'] }}" x-text="error"></p>
      <label for="m-filter" class="sr-only">Saring</label>
      <input id="m-filter" type="search" class="{{ $c['input'] }}" :placeholder="`Saring ${rows.length} data…`" x-model="q">
      <ul class="divide-y divide-[#f1f5f9] dark:divide-[#162032] max-h-[50vh] overflow-y-auto">
        <template x-for="r in shown" :key="r.id">
          <li class="flex items-center justify-between gap-2 py-1.5 text-sm">
            <span :class="r.aktif ? '' : 'line-through text-[#94a3b8]'" x-text="r.nama"></span>
            <span class="flex gap-1.5 shrink-0">
              <button type="button" class="{{ $c['ghost'] }}" @click="rename(r)">Ubah</button>
              <button type="button" class="{{ $c['ghost'] }}" @click="toggle(r)" x-text="r.aktif ? 'Nonaktifkan' : 'Aktifkan'"></button>
            </span>
          </li>
        </template>
      </ul>
    </div>
  </template>
  @endif
</div>
