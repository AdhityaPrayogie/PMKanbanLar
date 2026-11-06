{{-- Port of TicketForm.tsx. $init: Alpine expression "hdTicketForm(ticket|null, onSaved)". Needs kategori/unitKerja/pic in scope. --}}
<form x-data="{{ $init }}" @submit.prevent="submit()" class="grid gap-3 sm:grid-cols-3">
  <template x-if="fromPengajuan">
    <p class="sm:col-span-3 text-sm rounded-lg bg-[#e0edfb] dark:bg-[#1e293b] text-[#034694] dark:text-blue-300 px-3 py-2">
      Pengajuan dari <strong x-text="ticket.requester_name"></strong> (<span x-text="ticket.requester_unit || '-'"></span>)
    </p>
  </template>
  <div>
    <label :for="uid + '-tgl'" class="{{ $c['label'] }}">Tanggal</label>
    <input :id="uid + '-tgl'" type="date" required class="{{ $c['input'] }}" x-model="f.tanggal">
  </div>
  <div>
    <label :for="uid + '-mulai'" class="{{ $c['label'] }}">Jam mulai</label>
    <input :id="uid + '-mulai'" type="time" class="{{ $c['input'] }}" x-model="f.jam_mulai">
  </div>
  <div>
    <label :for="uid + '-selesai'" class="{{ $c['label'] }}">Jam selesai</label>
    <input :id="uid + '-selesai'" type="time" :min="f.jam_mulai || null" class="{{ $c['input'] }}" x-model="f.jam_selesai">
    <output :for="uid + '-mulai ' + uid + '-selesai'" aria-live="polite"
      class="block text-xs font-semibold mt-1" :class="totalMenit !== null && totalMenit < 0 ? 'text-rose-600' : 'text-[#475569] dark:text-[#94a3b8]'"
      x-text="'Total: ' + (totalMenit === null ? '-' : totalMenit < 0 ? 'jam selesai sebelum jam mulai' : totalMenit + ' menit')"></output>
  </div>
  <div>
    <label :for="uid + '-pic'" class="{{ $c['label'] }}" x-text="'PIC / peminta ' + (fromPengajuan ? '(opsional)' : '')"></label>
    <input :id="uid + '-pic'" :list="uid + '-pic-list'" autocomplete="off" :required="!fromPengajuan" class="{{ $c['input'] }}" x-model="f.pic">
    <datalist :id="uid + '-pic-list'"><template x-for="p in pic" :key="p.id"><option :value="p.nama"></option></template></datalist>
    <p x-show="!picKnown" class="text-[length:calc(11px+var(--fs-up))] text-amber-600 mt-0.5">PIC baru, akan ditambahkan ke master</p>
  </div>
  <div>
    <label :for="uid + '-kat'" class="{{ $c['label'] }}">Kategori</label>
    <select :id="uid + '-kat'" required class="{{ $c['input'] }}" x-model="f.kategori_id">
      <option value="">Pilih…</option>
      <template x-for="k in kategori" :key="k.id"><option :value="k.id" x-text="k.nama"></option></template>
    </select>
  </div>
  <div>
    <label :for="uid + '-uk'" class="{{ $c['label'] }}">Unit kerja / lokasi</label>
    <input :id="uid + '-uk'" :list="uid + '-uk-list'" autocomplete="off" class="{{ $c['input'] }}" x-model="f.unit_kerja">
    <datalist :id="uid + '-uk-list'"><template x-for="u in unitKerja" :key="u.id"><option :value="u.nama"></option></template></datalist>
  </div>
  <div class="sm:col-span-3">
    <label :for="uid + '-isi'" class="{{ $c['label'] }}">Isi helpdesk</label>
    <textarea :id="uid + '-isi'" required rows="2" maxlength="5000" class="{{ $c['input'] }}" x-model="f.isi_helpdesk"></textarea>
  </div>
  <div class="sm:col-span-3">
    <label :for="uid + '-tindakan'" class="{{ $c['label'] }}">Tindakan</label>
    <textarea :id="uid + '-tindakan'" rows="2" maxlength="5000" class="{{ $c['input'] }}" x-model="f.tindakan"></textarea>
  </div>
  <div>
    <label :for="uid + '-status'" class="{{ $c['label'] }}">Status</label>
    <select :id="uid + '-status'" class="{{ $c['input'] }}" x-model="f.status">
      @foreach (['Open', 'Proses', 'Selesai', 'Batal'] as $s)<option>{{ $s }}</option>@endforeach
    </select>
  </div>
  <div>
    <label :for="uid + '-jenis'" class="{{ $c['label'] }}">Jenis pelayanan</label>
    <select :id="uid + '-jenis'" class="{{ $c['input'] }}" x-model="f.jenis_pelayanan">
      @foreach (['Pelayanan', 'Non Pelayanan'] as $j)<option>{{ $j }}</option>@endforeach
    </select>
  </div>
  <fieldset class="sm:col-span-3">
    <legend class="{{ $c['label'] }}">Staf IT yang menangani</legend>
    <div class="flex flex-wrap gap-2">
      <template x-for="u in tdStaff" :key="u.id">
        <label class="inline-flex items-center gap-1.5 text-sm px-2 py-1 rounded-lg border border-[#cbd5e1] dark:border-[#24334a] cursor-pointer">
          <input type="checkbox" :value="u.id" x-model="f.staff_ids">
          <span class="w-1.5 h-1.5 rounded-full" :class="isOnline(u) ? 'bg-emerald-500' : 'bg-slate-400'" aria-hidden="true"></span>
          <span x-text="u.name"></span>
        </label>
      </template>
    </div>
  </fieldset>
  <fieldset class="sm:col-span-3">
    <legend class="{{ $c['label'] }}">Biaya / barang keluar (opsional)</legend>
    <div x-show="biaya.length > 0" class="space-y-2 mb-2">
      <template x-for="(b, i) in biaya" :key="i">
        <div class="grid grid-cols-[1fr_5rem_8rem_auto] gap-2 items-center">
          <input :aria-label="'Nama barang ' + (i + 1)" placeholder="Nama barang, mis. Tinta hitam" required maxlength="150"
            class="{{ $c['input'] }}" x-model="b.nama_barang">
          <input :aria-label="'Qty barang ' + (i + 1)" type="number" min="1" max="9999" step="1" required
            class="{{ $c['input'] }}" x-model="b.qty">
          <input :aria-label="'Harga satuan barang ' + (i + 1) + ' (Rp)'" type="number" min="0" step="1" required placeholder="Harga (Rp)"
            class="{{ $c['input'] }}" x-model="b.harga">
          <button type="button" @click="biaya.splice(i, 1)" class="p-1 text-rose-500" :aria-label="'Hapus barang ' + (i + 1)"><x-icon name="trash-2" class="w-4 h-4" /></button>
        </div>
      </template>
    </div>
    <div class="flex items-center justify-between gap-3">
      <button type="button" class="{{ $c['ghost'] }}" @click="biaya.push({ nama_barang: '', qty: '1', harga: '' })">
        <x-icon name="plus" class="w-3.5 h-3.5" /> Tambah barang
      </button>
      <output aria-live="polite" class="text-sm font-bold" x-text="'Total biaya: ' + rupiah(totalRp)"></output>
    </div>
  </fieldset>
  <div class="sm:col-span-3 flex items-center justify-between gap-3">
    <p aria-live="polite" class="{{ $c['error'] }}" x-text="error"></p>
    <button type="submit" :disabled="saving" class="{{ $c['primary'] }}">
      <x-icon name="save" class="w-3.5 h-3.5" /> <span x-text="saving ? 'Menyimpan…' : ticket ? 'Simpan Perubahan' : 'Simpan Tiket'"></span>
    </button>
  </div>
</form>
