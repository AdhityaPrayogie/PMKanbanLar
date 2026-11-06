{{-- Port of components/helpdesk/PengajuanView.tsx. JS: pengajuanView in resources/js/alpine/helpdesk.js --}}
@php($c = require resource_path('views/partials/helpdesk/cls.php'))
<div x-data="pengajuanView" class="max-w-5xl mx-auto space-y-5">
  <div class="flex items-center gap-2">
    <x-icon name="life-buoy" class="w-5 h-5 text-[#034694] dark:text-blue-400" />
    <h1 class="text-xl font-extrabold">Pengajuan Helpdesk IT</h1>
  </div>

  <form @submit.prevent="submit()" class="{{ $c['card'] }} grid gap-3 sm:grid-cols-2">
    <div>
      <label for="pj-kategori" class="{{ $c['label'] }}">Kategori masalah</label>
      <select id="pj-kategori" required class="{{ $c['input'] }}" x-model="form.kategori_id">
        <option value="">Pilih kategori…</option>
        <template x-for="k in kategori" :key="k.id"><option :value="k.id" x-text="k.nama"></option></template>
      </select>
    </div>
    <div>
      <label for="pj-lokasi" class="{{ $c['label'] }}">Lokasi / unit kerja</label>
      <input id="pj-lokasi" required list="pj-lokasi-list" autocomplete="off" placeholder="Ketik untuk mencari…" class="{{ $c['input'] }}" x-model="form.unit_kerja">
      <datalist id="pj-lokasi-list"><template x-for="u in unitKerja" :key="u.id"><option :value="u.nama"></option></template></datalist>
    </div>
    <div class="sm:col-span-2">
      <label for="pj-isi" class="{{ $c['label'] }}">Keluhan / permintaan</label>
      <textarea id="pj-isi" required rows="3" maxlength="5000" placeholder="Contoh: printer di ruang poli 3 tidak bisa mencetak"
        class="{{ $c['input'] }}" x-model="form.isi_helpdesk"></textarea>
    </div>
    <div class="sm:col-span-2 flex items-center justify-between gap-3">
      <div>
        <p aria-live="polite" class="{{ $c['error'] }}" x-text="error"></p>
        <p x-show="notice" aria-live="polite" class="text-sm font-semibold text-emerald-600 dark:text-emerald-400" x-text="notice"></p>
      </div>
      <button type="submit" :disabled="saving" class="{{ $c['primary'] }}">
        <x-icon name="send" class="w-3.5 h-3.5" /> <span x-text="saving ? 'Mengirim…' : 'Kirim Pengajuan'"></span>
      </button>
    </div>
  </form>

  <section class="{{ $c['card'] }}">
    <h2 class="text-base font-bold mb-3">Pengajuan Saya</h2>
    <p x-show="tickets === null" class="text-sm text-[#64748b]">Memuat…</p>
    <p x-show="tickets && tickets.length === 0" class="text-sm text-[#64748b]">Belum ada pengajuan.</p>
    <div x-show="tickets && tickets.length" class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="text-left text-[#64748b] border-b border-[#e2e8f0] dark:border-[#1e2a3f]">
          <tr>
            <th class="py-2 pr-3">#</th><th class="pr-3">Tanggal</th><th class="pr-3">Kategori</th>
            <th class="pr-3">Keluhan</th><th class="pr-3">Status</th><th class="pr-3">Ditangani</th>
            <th class="pr-3">Tindakan</th><th><span class="sr-only">Aksi</span></th>
          </tr>
        </thead>
        <tbody>
          <template x-for="t in tickets ?? []" :key="t.id">
            <tr class="border-b border-[#f1f5f9] dark:border-[#162032] align-top">
              <td class="py-2 pr-3 font-bold" x-text="t.id"></td>
              <td class="pr-3 whitespace-nowrap" x-text="fmtDate(t.tanggal)"></td>
              <td class="pr-3" x-text="t.kategori"></td>
              <td class="pr-3 max-w-xs whitespace-pre-line leading-relaxed" x-text="t.isi_helpdesk"></td>
              <td class="pr-3"><span class="{{ $c['badge'] }}" :class="statusCls(t.status)" x-text="t.status"></span></td>
              <td class="pr-3" x-text="t.staff_names || '-'"></td>
              <td class="pr-3 max-w-xs whitespace-pre-line leading-relaxed" x-text="t.tindakan || '-'"></td>
              <td>
                <button x-show="t.status === 'Open'" type="button" @click="remove(t)" class="{{ $c['ghost'] }}" :aria-label="'Hapus pengajuan #' + t.id">
                  <x-icon name="trash-2" class="w-3.5 h-3.5 text-rose-500" />
                </button>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
  </section>
</div>
