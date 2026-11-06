{{-- Port of TicketTable.tsx + HistoryModal. Nested in helpdeskView (kategori/unitKerja/pic/version/bump). --}}
<div x-data="hdTicketTable" class="space-y-4">
  <div class="{{ $c['card'] }} grid gap-2 grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 items-end">
    <div><label for="f-start" class="{{ $c['label'] }}">Dari</label><input id="f-start" type="date" class="{{ $c['input'] }}" x-model="filter.start"></div>
    <div><label for="f-end" class="{{ $c['label'] }}">Sampai</label><input id="f-end" type="date" class="{{ $c['input'] }}" x-model="filter.end"></div>
    <div>
      <label for="f-status" class="{{ $c['label'] }}">Status</label>
      <select id="f-status" class="{{ $c['input'] }}" x-model="filter.status">
        <option value="">Semua</option>@foreach (['Open', 'Proses', 'Selesai', 'Batal'] as $s)<option>{{ $s }}</option>@endforeach
      </select>
    </div>
    <div>
      <label for="f-kat" class="{{ $c['label'] }}">Kategori</label>
      <select id="f-kat" class="{{ $c['input'] }}" x-model="filter.kategori_id">
        <option value="">Semua</option><template x-for="k in kategori" :key="k.id"><option :value="k.id" x-text="k.nama"></option></template>
      </select>
    </div>
    <div>
      <label for="f-staff" class="{{ $c['label'] }}">Staf</label>
      <select id="f-staff" class="{{ $c['input'] }}" x-model="filter.staff_id">
        <option value="">Semua</option><template x-for="u in tdStaff" :key="u.id"><option :value="u.id" x-text="u.name"></option></template>
      </select>
    </div>
    <div>
      <label for="f-sumber" class="{{ $c['label'] }}">Sumber</label>
      <select id="f-sumber" class="{{ $c['input'] }}" x-model="filter.sumber">
        <option value="">Semua</option><option value="pengajuan">Pengajuan</option><option value="langsung">Langsung</option>
      </select>
    </div>
    <div class="col-span-2 sm:col-span-1">
      <label for="f-q" class="{{ $c['label'] }}">Cari</label>
      <div class="relative">
        <x-icon name="search" class="w-3.5 h-3.5 absolute left-2 top-2.5 text-[#94a3b8]" aria-hidden="true" />
        <input id="f-q" type="search" class="{{ $c['input'] }} pl-7" x-model="qIn">
      </div>
    </div>
    <button type="button" @click="exportXlsx()" class="{{ $c['ghost'] }} justify-center py-2">
      <x-icon name="download" class="w-3.5 h-3.5" /> Excel
    </button>
  </div>

  <div class="{{ $c['card'] }} overflow-x-auto">
    <p x-show="error" class="text-sm text-rose-600" x-text="error"></p>
    <table class="w-full text-sm">
      <thead class="text-left text-[#64748b] border-b border-[#e2e8f0] dark:border-[#1e2a3f]">
        <tr>
          <th class="py-2 pr-2">#</th><th class="pr-2">Tanggal</th><th class="pr-2">Jam</th><th class="pr-2 text-right whitespace-nowrap">Total Menit</th><th class="pr-2">Peminta</th>
          <th class="pr-2">Kategori</th><th class="pr-2">Isi Helpdesk</th><th class="pr-2">Staf</th>
          <th class="pr-2 text-right">Biaya</th><th class="pr-2">Status</th><th class="pr-2">Sumber</th><th><span class="sr-only">Aksi</span></th>
        </tr>
      </thead>
      <tbody>
        <template x-for="t in data?.rows ?? []" :key="t.id">
          <tr class="border-b border-[#f1f5f9] dark:border-[#162032] align-top">
            <td class="py-2 pr-2 font-bold" x-text="t.id"></td>
            <td class="pr-2 whitespace-nowrap" x-text="fmtDate(t.tanggal)"></td>
            <td class="pr-2 whitespace-nowrap" x-text="(t.jam_mulai ?? '--') + '–' + (t.jam_selesai ?? '--')"></td>
            <td class="pr-2 text-right tabular-nums font-semibold" x-text="t.total_menit ?? '-'"></td>
            <td class="pr-2">
              <span x-text="t.pic_nama ?? t.requester_name"></span>
              <div class="text-[length:calc(11px+var(--fs-up))] text-[#64748b]" x-text="t.unit_kerja ?? ''"></div>
            </td>
            <td class="pr-2" x-text="t.kategori"></td>
            <td class="pr-2 max-w-xs">
              <div class="line-clamp-2 leading-relaxed" x-text="t.isi_helpdesk"></div>
              <div x-show="t.tindakan" class="text-[length:calc(11px+var(--fs-up))] text-[#64748b] line-clamp-1" x-text="'↳ ' + t.tindakan"></div>
            </td>
            <td class="pr-2" x-text="t.staff_names || '-'"></td>
            <td class="pr-2 text-right whitespace-nowrap tabular-nums">
              <span x-text="t.total_biaya ? rupiah(t.total_biaya) : '-'"></span>
              <div x-show="t.biaya.length > 0" class="text-[length:calc(11px+var(--fs-up))] text-[#64748b] max-w-40 truncate ml-auto" :title="biayaTitle(t)"
                x-text="t.biaya.map((b) => b.nama_barang).join(', ')"></div>
            </td>
            <td class="pr-2"><span class="{{ $c['badge'] }}" :class="statusCls(t.status)" x-text="t.status"></span></td>
            <td class="pr-2" x-text="t.requested_by ? 'Pengajuan' : 'Langsung'"></td>
            <td class="whitespace-nowrap">
              <button type="button" @click="editing = t" class="p-1 hover:text-[#034694]" :aria-label="'Edit tiket #' + t.id"><x-icon name="pencil" class="w-3.5 h-3.5" /></button>
              <button type="button" @click="historyOf = t" class="p-1 hover:text-[#034694]" :aria-label="'Riwayat tiket #' + t.id"><x-icon name="history" class="w-3.5 h-3.5" /></button>
              <button x-show="canDelete(t)" type="button" @click="remove(t)" class="p-1 text-rose-500" :aria-label="'Hapus tiket #' + t.id"><x-icon name="trash-2" class="w-3.5 h-3.5" /></button>
            </td>
          </tr>
        </template>
        <tr x-show="data && !data.rows.length"><td colspan="12" class="py-6 text-center text-[#64748b]">Tidak ada tiket.</td></tr>
      </tbody>
    </table>
    <div x-show="data && data.total > PAGE" class="flex items-center justify-end gap-2 pt-3 text-sm">
      <span class="text-[#64748b]" x-text="data && `${offset + 1}–${Math.min(offset + PAGE, data.total)} dari ${data.total}`"></span>
      <button type="button" class="{{ $c['ghost'] }}" :disabled="offset === 0" @click="offset -= PAGE">Sebelumnya</button>
      <button type="button" class="{{ $c['ghost'] }}" :disabled="!data || offset + PAGE >= data.total" @click="offset += PAGE">Berikutnya</button>
    </div>
  </div>

  <template x-if="editing">
    <x-helpdesk.modal title="'Edit Tiket #' + editing?.id" close="editing = null" wide>
      @include('partials.helpdesk.ticket-form', ['init' => 'hdTicketForm(editing, () => { editing = null; bump() })'])
    </x-helpdesk.modal>
  </template>
  <template x-if="historyOf">
    <x-helpdesk.modal x-data="hdHistory(historyOf.id)" title="'Riwayat Tiket #' + historyOf?.id" close="historyOf = null">
      <p x-show="rows === null" class="text-sm">Memuat…</p>
      <ol x-show="rows !== null" class="space-y-3">
        <template x-for="h in rows ?? []" :key="h.id">
          <li class="text-sm border-l-2 border-[#034694] pl-3">
            <div class="font-bold">
              <span x-text="(h.aksi === 'create' ? 'Dibuat' : 'Diubah') + ' oleh ' + (h.user_name ?? '-')"></span><span class="font-normal text-[#64748b]" x-text="' · ' + when(h.created_at)"></span>
            </div>
            <ul class="mt-1 space-y-0.5">
              <template x-for="(ch, i) in h.changes" :key="i">
                <li>
                  <span class="text-[#64748b]" x-text="ch.label + ':'"></span>
                  <template x-if="ch.old != null"><span><del class="text-rose-600" x-text="String(ch.old) || '(kosong)'"></del> → </span></template>
                  <span class="text-emerald-700 dark:text-emerald-400" x-text="ch.new == null || ch.new === '' ? '(kosong)' : String(ch.new)"></span>
                </li>
              </template>
            </ul>
          </li>
        </template>
      </ol>
    </x-helpdesk.modal>
  </template>
</div>
