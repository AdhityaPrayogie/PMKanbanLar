{{-- Port of components/task/CreateTaskModal.tsx --}}
@php
  $input = 'w-full px-3 py-2 border border-[#cbd5e1] dark:border-[#2b3b55] bg-white dark:bg-[#162032] rounded-lg text-xs text-[#0f172a] dark:text-white placeholder-[#94a3b8] focus:outline-none focus:border-[#034694]';
  $small = 'w-full px-2.5 py-1.5 border border-[#cbd5e1] dark:border-[#2b3b55] rounded-lg text-xs bg-white dark:bg-[#162032] text-[#0f172a] dark:text-white focus:outline-none focus:border-[#034694]';
  $lbl = 'block text-xs font-bold text-[#64748b] dark:text-[#94a3b8] mb-1';
  $box = 'p-3.5 rounded-xl bg-[#f8fafc] dark:bg-[#162032] border border-[#cbd5e1] dark:border-[#2b3b55]';
  $userOn = 'bg-[#e0edfb]/80 dark:bg-[#17253d] border-[#034694] dark:border-blue-500 text-[#034694] dark:text-blue-300 font-bold';
  $userOff = 'bg-white dark:bg-[#101726] border-[#cbd5e1] dark:border-[#2b3b55] text-[#475569] dark:text-[#cbd5e1] hover:border-[#034694]';
  $labelOff = 'bg-white dark:bg-[#162032] border-[#cbd5e1] dark:border-[#2b3b55] text-[#475569] dark:text-[#cbd5e1]';
@endphp
<div x-data="createTask" x-show="$store.kb.isCreateModalOpen" x-cloak
  class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-slate-900/60 backdrop-blur-xs animate-in fade-in">
  <div class="bg-white dark:bg-[#111726] rounded-2xl border border-[#cbd5e1] dark:border-[#1e2a3f] shadow-2xl w-full max-w-xl overflow-hidden animate-in zoom-in-95 duration-150 flex flex-col max-h-[90vh]">
    <div class="px-6 py-4 border-b border-[#cbd5e1] dark:border-[#1e2a3f] flex items-center justify-between bg-[#f8fafc] dark:bg-[#0d1322]">
      <h3 class="text-sm font-extrabold text-[#0f172a] dark:text-white flex items-center gap-2">
        <x-icon name="plus" class="w-4 h-4 text-[#034694] dark:text-blue-400" />
        <span x-text="isSubTask ? 'Buat Sub-task Baru' : 'Buat Tugas Baru'"></span>
      </h3>
      <button type="button" @click="$store.kb.closeCreateModal()" class="p-2 text-[#64748b] hover:text-[#0f172a] dark:hover:text-white rounded-lg hover:bg-[#e2e8f0] dark:hover:bg-[#1e293b] transition-colors cursor-pointer">
        <x-icon name="x" class="w-4 h-4" />
      </button>
    </div>

    <form @submit.prevent="submit()" class="p-4 sm:p-6 space-y-4 overflow-y-auto flex-1">
      <div>
        <label class="block text-xs font-bold text-[#0f172a] dark:text-white mb-1">Judul Tugas <span class="text-rose-500">*</span></label>
        <input type="text" required x-model="title" placeholder="Contoh: Implementasi integrasi payment gateway" class="{{ $input }}">
      </div>

      <div>
        <label class="block text-xs font-bold text-[#0f172a] dark:text-white mb-1">Deskripsi Tugas</label>
        <textarea rows="2" x-model="description" placeholder="Rincian konteks atau kriteria penerimaan tugas..." class="{{ $input }} resize-none"></textarea>
      </div>

      <div>
        <label class="block text-xs font-bold text-[#0f172a] dark:text-white mb-1 flex items-center gap-1.5">
          <x-icon name="briefcase" class="w-3.5 h-3.5 text-[#034694] dark:text-blue-400" />
          <span>Customer / Klien Rekanan</span>
        </label>
        <input type="text" x-model="customer" placeholder="Contoh: PT Telkom Indonesia, RS Medika Utama, Bank Mandiri..." class="{{ $input }}">
      </div>

      {{-- Tags Unit --}}
      <div class="{{ $box }}">
        <div class="flex items-center justify-between mb-1.5">
          <label class="text-xs font-bold text-[#0f172a] dark:text-white flex items-center gap-1.5">
            <x-icon name="tag" class="w-3.5 h-3.5 text-[#034694] dark:text-blue-400" />
            <span>Tags Unit (Tersimpan ke Table Units)</span>
          </label>
          <span class="text-[length:calc(10px+var(--fs-up))] text-[#64748b] dark:text-[#94a3b8]" x-text="selectedUnitTags.length + ' Unit Dipilih'"></span>
        </div>
        <div class="flex flex-wrap gap-1.5 mb-2.5">
          <template x-for="tag in selectedUnitTags" :key="tag">
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-bold bg-[#e0edfb] dark:bg-[#1e293b] text-[#034694] dark:text-blue-300 border border-blue-200 dark:border-blue-900/60 shadow-2xs">
              <span x-text="'#' + tag"></span>
              <button type="button" @click="removeUnitTag(tag)" class="p-0.5 hover:text-rose-500 rounded"><x-icon name="x" class="w-3 h-3" /></button>
            </span>
          </template>
        </div>
        <div class="flex items-center gap-2">
          <input type="text" x-model="unitInput" @keydown.enter.prevent="addUnitTag()" placeholder="Ketik nama/kode unit lalu tekan Enter atau tombol Tambah..."
            class="flex-1 px-3 py-1.5 bg-white dark:bg-[#101726] border border-[#cbd5e1] dark:border-[#334460] rounded-lg text-xs text-[#0f172a] dark:text-white placeholder-[#94a3b8] focus:outline-none focus:border-[#034694]">
          <button type="button" @click="addUnitTag()" class="px-3 py-1.5 bg-[#034694] hover:bg-[#023470] text-white text-xs font-bold rounded-lg shadow-2xs transition-colors shrink-0">+ Tambah</button>
        </div>
        <template x-if="similar">
          <div class="mt-2 p-2 rounded-lg bg-amber-50 dark:bg-amber-950/60 border border-amber-300 dark:border-amber-800 text-amber-900 dark:text-amber-200 text-[length:calc(11px+var(--fs-up))] flex items-center justify-between gap-2 animate-in fade-in">
            <div class="flex items-center gap-1.5">
              <x-icon name="alert-circle" class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400 shrink-0" />
              <span x-text="similar.message"></span>
            </div>
            <button type="button" @click="addUnitTag(similar.unit.code)" class="px-2 py-0.5 bg-amber-600 text-white font-bold rounded text-[length:calc(10px+var(--fs-up))] hover:bg-amber-700 shrink-0" x-text="'Pilih #' + similar.unit.code"></button>
          </div>
        </template>
        <div x-show="unitNotice" class="mt-2 p-2 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-300 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-[length:calc(11px+var(--fs-up))] flex items-center gap-1.5 animate-in fade-in">
          <x-icon name="sparkles" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
          <span x-text="unitNotice"></span>
        </div>
      </div>

      {{-- Assignees --}}
      <div class="{{ $box }}">
        <div class="flex items-center justify-between mb-1.5">
          <label class="text-xs font-bold text-[#0f172a] dark:text-white flex items-center gap-1.5">
            <x-icon name="users" class="w-3.5 h-3.5 text-[#034694] dark:text-blue-400" />
            <span x-text="'Assign Team Members (' + selectedAssignees.length + ')'"></span>
          </label>
        </div>
        <div x-show="selectedAssignees.length > 0" class="flex flex-wrap gap-1.5 mb-2.5">
          <template x-for="u in chosenUsers" :key="u.id">
            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-semibold bg-white dark:bg-[#101726] border border-[#cbd5e1] dark:border-[#334460] shadow-2xs">
              <template x-if="u.avatar_url"><img :src="u.avatar_url" :alt="u.name" class="w-4 h-4 rounded-full object-cover"></template>
              <template x-if="!u.avatar_url"><div class="w-4 h-4 rounded-full bg-[#034694] text-white flex items-center justify-center text-[length:calc(10px+var(--fs-up))] font-bold" x-text="u.name.charAt(0)"></div></template>
              <span x-text="u.name"></span>
              <button type="button" @click="toggleAssignee(u.id)" class="p-0.5 hover:text-rose-500 rounded-full"><x-icon name="x" class="w-3 h-3" /></button>
            </span>
          </template>
        </div>
        <div class="relative mb-2">
          <x-icon name="search" class="w-3.5 h-3.5 text-[#94a3b8] absolute left-2.5 top-1/2 -translate-y-1/2" />
          <input type="text" x-model="assigneeSearch" placeholder="Cari nama, email, atau unit anggota..."
            class="w-full pl-8 pr-3 py-1.5 bg-white dark:bg-[#101726] border border-[#cbd5e1] dark:border-[#334460] rounded-lg text-xs text-[#0f172a] dark:text-white placeholder-[#94a3b8] focus:outline-none focus:border-[#034694]">
        </div>
        <div class="max-h-36 overflow-y-auto space-y-1 pr-1">
          <div x-show="filteredUsers.length === 0" class="p-3 text-center text-xs text-[#94a3b8] italic" x-text="'Tidak ada anggota yang cocok dengan pencarian &quot;' + assigneeSearch + '&quot;'"></div>
          <template x-for="u in filteredUsers" :key="u.id">
            <button type="button" @click="toggleAssignee(u.id)" class="w-full flex items-center justify-between p-1.5 rounded-lg text-xs transition-colors border text-left"
              :class="selectedAssignees.includes(u.id) ? @js($userOn) : @js($userOff)">
              <div class="flex items-center gap-2 truncate">
                <template x-if="u.avatar_url"><img :src="u.avatar_url" :alt="u.name" class="w-5 h-5 rounded-full object-cover ring-1 ring-[#cbd5e1]"></template>
                <template x-if="!u.avatar_url"><div class="w-5 h-5 rounded-full bg-[#034694] text-white flex items-center justify-center text-[length:calc(10px+var(--fs-up))] font-bold" x-text="u.name.charAt(0)"></div></template>
                <span class="truncate" x-text="u.name"></span>
                <span class="text-[length:calc(10px+var(--fs-up))] text-[#94a3b8] font-normal" x-text="'#' + (u.unit_name || 'IT')"></span>
              </div>
              <div class="shrink-0">
                <span x-show="selectedAssignees.includes(u.id)" class="contents"><x-icon name="check" class="w-3.5 h-3.5 text-[#034694] dark:text-blue-400" /></span>
                <div x-show="!selectedAssignees.includes(u.id)" class="w-3.5 h-3.5 rounded border border-[#cbd5e1] dark:border-[#334460]"></div>
              </div>
            </button>
          </template>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="{{ $lbl }}">Kolom Status</label>
          <select x-model="columnId" class="{{ $small }}">
            <template x-for="c in $store.kb.columns" :key="c.id">
              <option :value="c.id" x-text="c.title" :selected="c.id === columnId"></option>
            </template>
          </select>
        </div>
        <div>
          <label class="{{ $lbl }}">Prioritas</label>
          <select x-model="priority" class="{{ $small }}">
            @foreach (['Low', 'Medium', 'High', 'Urgent'] as $p)<option value="{{ $p }}">{{ $p }}</option>@endforeach
          </select>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="{{ $lbl }}">Tanggal Mulai</label>
          <input type="date" x-model="startDate" class="{{ $small }}">
        </div>
        <div>
          <label class="{{ $lbl }}">Tenggat Waktu</label>
          <input type="date" x-model="dueDate" class="{{ $small }}">
        </div>
      </div>

      <div x-show="$store.kb.masterLabels?.length > 0">
        <label class="block text-xs font-bold text-[#0f172a] dark:text-white mb-1.5">Labels</label>
        <div class="flex flex-wrap gap-1.5">
          <template x-for="l in $store.kb.masterLabels || []" :key="l.id">
            <button type="button" @click="toggleLabel(l.id)" class="px-2.5 py-0.5 rounded text-xs font-semibold transition-all border"
              :class="selectedLabels.includes(l.id) ? 'ring-2 ring-offset-1 ring-[#034694] text-white' : @js($labelOff)"
              :style="selectedLabels.includes(l.id) ? { backgroundColor: l.color } : {}" x-text="l.name"></button>
          </template>
        </div>
      </div>

      <div class="pt-3 border-t border-[#cbd5e1] dark:border-[#1e2a3f] flex items-center justify-end gap-2">
        <button type="button" @click="$store.kb.closeCreateModal()" class="px-4 py-2 text-xs font-bold text-[#64748b] hover:text-[#0f172a] dark:hover:text-white cursor-pointer">Batal</button>
        <button type="submit" :disabled="isSubmitting" class="px-5 py-2.5 bg-[#034694] hover:bg-[#023470] disabled:bg-[#94a3b8] text-white rounded-lg text-xs font-bold shadow-xs transition-all cursor-pointer active:scale-95"
          x-text="isSubmitting ? 'Menyimpan...' : isSubTask ? 'Buat Sub-task' : 'Buat Tugas'"></button>
      </div>
    </form>
  </div>
</div>
