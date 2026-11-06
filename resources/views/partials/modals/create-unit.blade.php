{{-- Port of components/unit/CreateUnitModal.tsx --}}
@php
  $label = 'block text-xs font-bold text-[#0f172a] dark:text-[#f8fafc] mb-1.5';
  $select = 'w-full px-3 py-2 border border-[#cbd5e1] dark:border-[#2b3b55] rounded-xl text-xs bg-white dark:bg-[#162032] text-[#0f172a] dark:text-white focus:outline-none focus:border-[#034694]';
@endphp
<div x-data="createUnit" x-show="$store.kb.isCreateUnitModalOpen" x-cloak class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4 animate-in fade-in duration-150">
  <div class="bg-white dark:bg-[#111726] rounded-2xl border border-[#cbd5e1] dark:border-[#1e2a3f] shadow-2xl w-full max-w-lg overflow-hidden animate-in zoom-in-95 duration-150 flex flex-col" @click.stop>
    <div class="px-6 py-4 border-b border-[#cbd5e1] dark:border-[#1e2a3f] flex items-center justify-between bg-[#f8fafc] dark:bg-[#0d1424]">
      <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-lg bg-[#034694] text-white flex items-center justify-center font-bold shadow-xs">
          <x-icon name="building-2" class="w-4 h-4" />
        </div>
        <div>
          <h3 class="text-sm font-bold text-[#0f172a] dark:text-white">Tambah Papan Kerja / Board Baru</h3>
          <p class="text-[length:calc(11px+var(--fs-up))] text-[#64748b] dark:text-[#94a3b8]">Level 1: Papan Utama dengan Child Kanban &amp; Timeline mandiri</p>
        </div>
      </div>
      <button @click="$store.kb.closeCreateUnitModal()" class="p-1.5 text-[#64748b] hover:text-[#0f172a] dark:hover:text-white rounded-lg hover:bg-[#e2e8f0] dark:hover:bg-[#1e293b] transition-colors">
        <x-icon name="x" class="w-4 h-4" />
      </button>
    </div>

    <form @submit.prevent="submit" class="p-6 space-y-4">
      <div>
        <label class="{{ $label }}">Nama Board Utama <span class="text-rose-500">*</span></label>
        <input x-ref="name" type="text" required x-model="name" placeholder="Contoh: IT: Infrastruktur Server, Markom: Campaign Ramadhan..."
          class="w-full px-3.5 py-2.5 border border-[#cbd5e1] dark:border-[#2b3b55] rounded-xl text-xs bg-white dark:bg-[#162032] text-[#0f172a] dark:text-white placeholder-[#94a3b8] focus:outline-none focus:border-[#034694] dark:focus:border-blue-500 font-medium">
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="{{ $label }}">Tag Unit / Divisi</label>
          <select x-model="unitTag" class="{{ $select }}">
            @foreach (['IT', 'Markom', 'MFK', 'PPSDM', 'Umum'] as $opt)
              <option value="{{ $opt }}">#{{ $opt }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="{{ $label }}">Tahap / Kolom Papan</label>
          <select x-model="stage" class="{{ $select }}">
            @foreach (['Planning' => 'Perencanaan (Planning)', 'Active' => 'Sedang Berjalan (Active)', 'Review' => 'Ditinjau / Evaluasi (Review)', 'Completed' => 'Selesai / Terawat (Completed)'] as $v => $l)
              <option value="{{ $v }}">{{ $l }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div>
        <label class="{{ $label }}">Deskripsi / Lingkup Kerja (Opsional)</label>
        <textarea rows="2" x-model="description" placeholder="Deskripsikan tujuan dan ruang lingkup board utama ini..."
          class="w-full px-3.5 py-2.5 border border-[#cbd5e1] dark:border-[#2b3b55] rounded-xl text-xs bg-white dark:bg-[#162032] text-[#0f172a] dark:text-white placeholder-[#94a3b8] focus:outline-none focus:border-[#034694] dark:focus:border-blue-500 font-medium resize-none"></textarea>
      </div>

      <div class="bg-[#f1f5f9] dark:bg-[#162032]/60 rounded-xl p-3 border border-[#cbd5e1] dark:border-[#24334a] text-xs space-y-1">
        <div class="flex items-center gap-2 font-bold text-[#034694] dark:text-blue-400 text-[length:calc(11px+var(--fs-up))]">
          <x-icon name="sparkles" class="w-3.5 h-3.5" /><span>Otomatis Dilengkapi:</span>
        </div>
        <ul class="text-[length:calc(11px+var(--fs-up))] text-[#475569] dark:text-[#cbd5e1] space-y-0.5 pl-5 list-disc">
          <li>Papan Child Kanban 4 kolom (To Do, In Progress, In Review, Done) yang bisa dikembangkan.</li>
          <li>Timeline Gantt dan Subtask mandiri untuk setiap tugas di board ini.</li>
        </ul>
      </div>

      <div class="flex items-center justify-end gap-2 pt-2">
        <button type="button" @click="$store.kb.closeCreateUnitModal()" class="px-4 py-2 border border-[#cbd5e1] dark:border-[#24334a] text-[#475569] dark:text-[#cbd5e1] hover:bg-[#f1f5f9] dark:hover:bg-[#162032] rounded-xl text-xs font-bold transition-colors">Batal</button>
        <button type="submit" :disabled="submitting || !name.trim()" class="px-5 py-2 bg-[#034694] hover:bg-[#023470] disabled:bg-[#cbd5e1] dark:disabled:bg-[#1e2a3f] text-white rounded-xl text-xs font-bold flex items-center gap-2 shadow-sm transition-all">
          <span x-show="submitting">Membuat...</span>
          <span x-show="!submitting" class="contents"><x-icon name="plus" class="w-3.5 h-3.5" /><span>Buat Board Utama</span></span>
        </button>
      </div>
    </form>
  </div>
</div>
