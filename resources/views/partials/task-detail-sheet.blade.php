{{-- Port of components/task/TaskDetailSheet.tsx --}}
@php
  $box = 'p-4 rounded-xl bg-[#f8fafc] dark:bg-[#162032] border border-[#cbd5e1] dark:border-[#2b3b55]';
  $boxLbl = 'text-xs font-bold text-[#0f172a] dark:text-white flex items-center gap-2';
  $search = 'w-full pl-8 pr-3 py-1.5 bg-white dark:bg-[#101726] border border-[#cbd5e1] dark:border-[#334460] rounded-lg text-xs text-[#0f172a] dark:text-white placeholder-[#94a3b8] focus:outline-none focus:border-[#034694]';
  $prop = 'w-full bg-white dark:bg-[#101726] border border-[#cbd5e1] dark:border-[#334460] rounded-lg px-2.5 py-2 text-xs text-[#0f172a] dark:text-white font-medium focus:outline-none focus:border-[#034694]';
  $propLbl = 'text-[length:calc(11px+var(--fs-up))] font-bold text-[#64748b] dark:text-[#94a3b8] block mb-1';
  $userOn = 'bg-[#e0edfb]/80 dark:bg-[#17253d] border-[#034694] dark:border-blue-500 font-bold text-[#034694] dark:text-blue-300';
  $userOff = 'bg-white dark:bg-[#101726] border-[#cbd5e1] dark:border-[#2d3d56] text-[#475569] dark:text-[#cbd5e1] hover:border-[#94a3b8]';
  $tab = 'flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer';
  $tabOn = 'bg-white dark:bg-[#0f172a] text-[#034694] dark:text-blue-400 shadow-2xs';
  $tabOff = 'text-[#64748b] dark:text-[#94a3b8] hover:text-[#0f172a] dark:hover:text-white';
  $dot = ['create' => 'bg-emerald-500 text-white', 'board' => 'bg-emerald-500 text-white', 'move' => 'bg-indigo-500 text-white', 'upload' => 'bg-teal-500 text-white', 'delete' => 'bg-rose-500 text-white', 'other' => 'bg-[#034694] text-white'];
  $accept = '.png,.jpg,.jpeg,.webp,.gif,.doc,.docx,.pdf,.md,.markdown,.xls,.xlsx,.csv,.zip,.rar,.7z,.tar,.gz,image/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv,application/zip,application/x-zip-compressed,application/x-rar-compressed,application/x-7z-compressed,text/markdown';
@endphp
{{-- z-[55]: above the mini-chat bubble (z-50), which otherwise covers the comment send button --}}
<div x-data="taskDetail" x-show="open" x-cloak class="fixed inset-0 z-[55] overflow-hidden flex justify-end">
  <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs transition-opacity animate-in fade-in" @click="close()"></div>

  <div class="relative w-full max-w-[54rem] bg-white dark:bg-[#111726] h-full shadow-2xl flex flex-col z-10 border-l border-[#cbd5e1] dark:border-[#1e2a3f] animate-in slide-in-from-right duration-200">
    {{-- Header --}}
    <div class="h-16 px-4 sm:px-6 border-b border-[#cbd5e1] dark:border-[#1e2a3f] flex items-center justify-between bg-[#f8fafc] dark:bg-[#0d1424] shrink-0">
      <div class="flex items-center gap-2 text-xs font-bold text-[#034694] dark:text-blue-400">
        <x-icon name="folder-kanban" class="w-4 h-4" />
        <span class="truncate">Detail Tugas &amp; Kolaborasi Multi-Unit</span>
      </div>
      <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
        <button @click="deleteTask()" class="p-2 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition-colors cursor-pointer" title="Hapus tugas ini">
          <x-icon name="trash-2" class="w-4 h-4" />
        </button>
        <button @click="close()" class="p-2 text-[#64748b] hover:bg-[#e2e8f0] dark:hover:bg-[#1e293b] hover:text-[#0f172a] dark:hover:text-white rounded-lg transition-colors cursor-pointer" title="Tutup sheet">
          <x-icon name="x" class="w-5 h-5" />
        </button>
      </div>
    </div>

    <div x-show="saveSuccessMessage" class="bg-emerald-50 dark:bg-emerald-950/60 border-b border-emerald-200 dark:border-emerald-800 px-4 sm:px-6 py-2.5 flex items-center gap-2 text-xs text-emerald-800 dark:text-emerald-300 font-bold animate-in fade-in">
      <x-icon name="check" class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
      <span>Perubahan tugas berhasil disimpan ke database!</span>
    </div>

    <div x-show="isDirty" class="bg-amber-50 dark:bg-amber-950/60 border-b border-amber-300 dark:border-amber-800/80 px-4 sm:px-6 py-2.5 flex items-center justify-between gap-2 text-xs text-amber-900 dark:text-amber-200 animate-in fade-in">
      <div class="flex items-center gap-2 font-bold truncate">
        <x-icon name="alert-triangle" class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0" />
        <span class="truncate">Ada perubahan belum disimpan.</span>
      </div>
      <div class="flex items-center gap-2 shrink-0">
        <button type="button" @click="resetDraft()" class="px-2.5 py-1 text-[length:calc(11px+var(--fs-up))] font-semibold text-slate-600 dark:text-slate-300 hover:bg-amber-100 dark:hover:bg-amber-900/60 rounded-md transition-colors cursor-pointer">Reset</button>
        <button type="button" @click="openConfirmSave()" class="px-3 py-1 bg-[#034694] hover:bg-[#023470] text-white font-bold rounded-md text-[length:calc(11px+var(--fs-up))] flex items-center gap-1.5 shadow-2xs transition-colors cursor-pointer active:scale-95">
          <x-icon name="save" class="w-3.5 h-3.5" /><span>Simpan</span>
        </button>
      </div>
    </div>

    <div x-show="loading || !task" class="flex-1 flex items-center justify-center text-xs text-[#94a3b8]">Memuat data tugas...</div>

    <template x-if="!loading && task">
      <div class="flex-1 overflow-y-auto px-4 sm:px-6 py-4 sm:py-5 space-y-5 sm:space-y-6">
        <div>
          <label class="text-xs font-bold text-[#64748b] dark:text-[#94a3b8] block mb-1">Judul Tugas</label>
          <input type="text" x-model="draftTitle" placeholder="Judul tugas..."
            class="w-full text-base font-bold text-[#0f172a] dark:text-white bg-transparent border-b border-[#cbd5e1] dark:border-[#2b3b55] focus:border-[#034694] dark:focus:border-blue-400 focus:outline-none py-1.5 transition-colors">
        </div>

        {{-- Customer --}}
        <div class="{{ $box }}">
          <label class="{{ $boxLbl }} mb-1.5">
            <x-icon name="briefcase" class="w-4 h-4 text-[#034694] dark:text-blue-400" />
            <span>Kolom Customer / Klien Rekanan</span>
          </label>
          <input type="text" x-model="draftCustomer" placeholder="Nama Customer (Contoh: PT Telkom Indonesia, RS Medika Utama, Bank Mandiri)..."
            class="w-full px-3 py-2 bg-white dark:bg-[#101726] border border-[#cbd5e1] dark:border-[#334460] rounded-lg text-xs font-semibold text-[#0f172a] dark:text-white placeholder-[#94a3b8] focus:outline-none focus:border-[#034694]">
        </div>

        {{-- Unit tags --}}
        <div class="{{ $box }}">
          <div class="flex items-center justify-between mb-2">
            <label class="{{ $boxLbl }}">
              <x-icon name="building-2" class="w-4 h-4 text-[#034694] dark:text-blue-400" />
              <span>Tags Unit Terkait (Tersimpan ke Table Units):</span>
            </label>
            <span class="text-[length:calc(10px+var(--fs-up))] px-2 py-0.5 rounded-full font-bold bg-[#e0edfb] dark:bg-[#1e293b] text-[#034694] dark:text-blue-300" x-text="draftUnitTags.length + ' Unit Dipilih'"></span>
          </div>
          <div class="flex flex-wrap gap-2 mb-3">
            <span x-show="draftUnitTags.length === 0" class="text-xs text-[#94a3b8] italic">Belum ada unit terkait yang dipilih</span>
            <template x-for="tag in draftUnitTags" :key="tag">
              <span class="px-2.5 py-1 rounded-lg text-xs font-extrabold bg-[#034694] text-white flex items-center gap-1.5 border border-[#034694] shadow-2xs">
                <x-icon name="tag" class="w-3 h-3 text-white" />
                <span x-text="'#' + tag"></span>
                <button type="button" @click="removeUnitTag(tag)" class="p-0.5 hover:bg-rose-500 rounded" title="Hapus unit ini"><x-icon name="x" class="w-3 h-3 text-white" /></button>
              </span>
            </template>
          </div>
          <div class="flex items-center gap-2">
            <input type="text" x-model="unitInput" @keydown.enter.prevent="addUnitTag()" placeholder="Ketik nama unit baru lalu tekan Enter / Tambah..."
              class="flex-1 px-3 py-1.5 bg-white dark:bg-[#101726] border border-[#cbd5e1] dark:border-[#334460] rounded-lg text-xs text-[#0f172a] dark:text-white placeholder-[#94a3b8] focus:outline-none focus:border-[#034694]">
            <button type="button" @click="addUnitTag()" class="px-3.5 py-1.5 bg-[#034694] hover:bg-[#023470] text-white text-xs font-bold rounded-lg shadow-2xs transition-colors shrink-0">+ Tambah</button>
          </div>
          <template x-if="similar">
            <div class="mt-2.5 p-2 rounded-lg bg-amber-50 dark:bg-amber-950/60 border border-amber-300 dark:border-amber-800 text-amber-900 dark:text-amber-200 text-[length:calc(11px+var(--fs-up))] flex items-center justify-between gap-2 animate-in fade-in">
              <div class="flex items-center gap-1.5">
                <x-icon name="alert-triangle" class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400 shrink-0" />
                <span x-text="similar.message"></span>
              </div>
              <button type="button" @click="addUnitTag(similar.unit.code)" class="px-2 py-0.5 bg-amber-600 text-white font-bold rounded text-[length:calc(10px+var(--fs-up))] hover:bg-amber-700 shrink-0" x-text="'Gunakan #' + similar.unit.code"></button>
            </div>
          </template>
          <div x-show="unitNotice" class="mt-2.5 p-2 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-300 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-[length:calc(11px+var(--fs-up))] flex items-center gap-1.5 animate-in fade-in">
            <x-icon name="sparkles" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
            <span x-text="unitNotice"></span>
          </div>
        </div>

        {{-- Assignees --}}
        <div class="{{ $box }}">
          <div class="flex items-center justify-between mb-2">
            <label class="{{ $boxLbl }}">
              <x-icon name="users" class="w-4 h-4 text-[#034694] dark:text-blue-400" />
              <span x-text="'Kolom Assignees (' + draftAssigneeIds.length + ' Ditugaskan)'"></span>
            </label>
          </div>
          <div x-show="draftAssigneeIds.length > 0" class="flex flex-wrap gap-1.5 mb-2.5">
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
            <input type="text" x-model="assigneeSearch" placeholder="Cari anggota tim berdasarkan nama, email, unit..." class="{{ $search }}">
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto pr-1">
            <div x-show="filteredUsers.length === 0" class="col-span-2 p-3 text-center text-xs text-[#94a3b8] italic" x-text="'Tidak ada anggota yang cocok dengan &quot;' + assigneeSearch + '&quot;'"></div>
            <template x-for="u in filteredUsers" :key="u.id">
              <button type="button" @click="toggleAssignee(u.id)" class="flex items-center justify-between p-2 rounded-lg text-left text-xs transition-all border"
                :class="draftAssigneeIds.includes(u.id) ? @js($userOn) : @js($userOff)">
                <div class="flex items-center gap-2 truncate mr-1">
                  <template x-if="u.avatar_url"><img :src="u.avatar_url" :alt="u.name" class="w-6 h-6 rounded-full object-cover ring-1 ring-[#cbd5e1]"></template>
                  <template x-if="!u.avatar_url"><div class="w-6 h-6 rounded-full bg-[#034694] text-white flex items-center justify-center text-[length:calc(10px+var(--fs-up))] font-bold" x-text="u.name.charAt(0)"></div></template>
                  <div class="truncate">
                    <div class="truncate" x-text="u.name"></div>
                    <div class="text-[length:calc(10px+var(--fs-up))] text-[#94a3b8] font-normal" x-text="'#' + (u.unit_name || 'IT')"></div>
                  </div>
                </div>
                <div class="shrink-0">
                  <div x-show="draftAssigneeIds.includes(u.id)" class="w-5 h-5 rounded-full bg-[#034694] text-white flex items-center justify-center"><x-icon name="check" class="w-3 h-3" /></div>
                  <div x-show="!draftAssigneeIds.includes(u.id)" class="w-5 h-5 rounded-full border border-[#cbd5e1] dark:border-[#334460]"></div>
                </div>
              </button>
            </template>
          </div>
        </div>

        {{-- Properties --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-xl bg-[#f8fafc] dark:bg-[#162032] border border-[#cbd5e1] dark:border-[#2b3b55] text-xs">
          <div>
            <label class="{{ $propLbl }}">Status Kolom</label>
            <select x-model="draftColumnId" class="{{ $prop }}">
              <template x-for="col in $store.kb.columns" :key="col.id">
                <option :value="col.id" x-text="col.title" :selected="col.id === draftColumnId"></option>
              </template>
            </select>
          </div>
          <div>
            <label class="{{ $propLbl }}">Label Prioritas</label>
            <select x-model="draftPriority" class="{{ $prop }}">
              @foreach (['Low', 'Medium', 'High', 'Urgent'] as $p)<option value="{{ $p }}">{{ $p }}</option>@endforeach
            </select>
          </div>
          <div>
            <label class="{{ $propLbl }}">Tanggal Mulai</label>
            <input type="date" x-model="draftStartDate" class="{{ $prop }}">
          </div>
          <div>
            <label class="{{ $propLbl }}">Tanggal Tenggat (Deadline)</label>
            <input type="date" x-model="draftDueDate" class="{{ $prop }}">
          </div>
        </div>

        <div>
          <label class="text-xs font-bold text-[#0f172a] dark:text-white block mb-1.5">Deskripsi Tugas</label>
          <textarea rows="3" x-model="draftDesc" placeholder="Tambahkan detail keterangan untuk tugas ini..."
            class="w-full p-3 bg-white dark:bg-[#162032] border border-[#cbd5e1] dark:border-[#2b3b55] rounded-xl text-xs text-[#0f172a] dark:text-white placeholder-[#94a3b8] focus:outline-none focus:border-[#034694] dark:focus:border-blue-500 leading-relaxed resize-none"></textarea>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#cbd5e1] dark:border-[#1e2a3f]">
          <button type="button" @click="resetDraft()" :disabled="!isDirty" class="px-4 py-2 border border-[#cbd5e1] dark:border-[#2b3b55] disabled:opacity-40 text-[#475569] dark:text-[#cbd5e1] hover:bg-[#f1f5f9] dark:hover:bg-[#162032] rounded-xl text-xs font-bold transition-colors flex items-center gap-1.5">
            <x-icon name="rotate-ccw" class="w-3.5 h-3.5" /><span>Batal Perubahan</span>
          </button>
          <button type="button" @click="openConfirmSave()" :disabled="!isDirty || isSaving" class="px-5 py-2 bg-[#034694] hover:bg-[#023470] disabled:bg-[#cbd5e1] dark:disabled:bg-[#1e2a3f] text-white rounded-xl text-xs font-bold flex items-center gap-2 shadow-xs transition-all">
            <x-icon name="save" class="w-3.5 h-3.5" /><span>Simpan Perubahan</span>
          </button>
        </div>

        {{-- Sub-tasks --}}
        <div class="pt-2">
          <div class="flex items-center justify-between mb-2">
            <label class="text-xs font-bold text-[#0f172a] dark:text-white flex items-center gap-1.5">
              <x-icon name="check-circle-2" class="w-3.5 h-3.5 text-[#034694] dark:text-blue-400" />
              <span x-text="'Sub-tasks (' + (task.sub_tasks?.length || 0) + ')'"></span>
            </label>
            <button type="button" @click="$store.kb.openCreateModal(task.column_id, task.id)" class="text-xs text-[#034694] dark:text-blue-400 hover:underline font-bold flex items-center gap-1">
              <x-icon name="plus" class="w-3.5 h-3.5" /><span>Tambah Sub-task</span>
            </button>
          </div>
          <div class="space-y-1.5">
            <template x-for="sub in task.sub_tasks || []" :key="sub.id">
              <div class="p-2.5 rounded-lg bg-[#f8fafc] dark:bg-[#162032] border border-[#cbd5e1] dark:border-[#2b3b55] flex items-center justify-between text-xs hover:bg-[#f1f5f9] dark:hover:bg-[#1a2538] transition-colors">
                <div class="flex items-center gap-2 truncate">
                  <x-icon name="circle" class="w-3 h-3 text-[#94a3b8]" />
                  <span class="font-semibold text-[#0f172a] dark:text-white truncate" x-text="sub.title"></span>
                </div>
                <span class="text-[length:calc(10px+var(--fs-up))] text-[#475569] dark:text-[#94a3b8] bg-white dark:bg-[#101726] px-2 py-0.5 rounded border border-[#cbd5e1] dark:border-[#2b3b55]" x-text="sub.priority"></span>
              </div>
            </template>
            <div x-show="!task.sub_tasks?.length" class="text-xs text-[#94a3b8] italic p-3 bg-[#f8fafc] dark:bg-[#162032]/40 rounded-xl border border-dashed border-[#cbd5e1] dark:border-[#2b3b55] text-center">
              Belum ada sub-task. Tambahkan sub-task untuk memecah pekerjaan kompleks.
            </div>
          </div>
        </div>

        {{-- Attachments --}}
        <div class="pt-2">
          <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
              <label class="text-xs font-bold text-[#0f172a] dark:text-white flex items-center gap-1.5">
                <x-icon name="paperclip" class="w-3.5 h-3.5 text-[#034694] dark:text-blue-400" />
                <span x-text="'Lampiran Berkas (' + attachments.length + ')'"></span>
              </label>
              <span class="text-[length:calc(10px+var(--fs-up))] font-semibold text-[#64748b] dark:text-[#94a3b8] hidden sm:inline">(IMG, DOC, PDF, MD, XLS, ZIP)</span>
            </div>
            <div>
              <input x-ref="fileInput" type="file" class="hidden" accept="{{ $accept }}" @change="uploadFile($event)">
              <button type="button" :disabled="isUploading" @click="$refs.fileInput.click()" class="flex items-center gap-1.5 px-3 py-1.5 bg-[#034694] hover:bg-[#023470] disabled:bg-[#94a3b8] text-white rounded-lg text-xs font-bold shadow-2xs transition-all cursor-pointer">
                <span x-show="isUploading" class="contents"><x-icon name="loader-2" class="w-3.5 h-3.5 animate-spin" /><span>Mengunggah...</span></span>
                <span x-show="!isUploading" class="contents"><x-icon name="upload" class="w-3.5 h-3.5" /><span>Upload Berkas</span></span>
              </button>
            </div>
          </div>

          <div x-show="uploadError" class="mb-2.5 p-2.5 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-rose-700 dark:text-rose-300 text-xs flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <x-icon name="alert-triangle" class="w-4 h-4 shrink-0 text-rose-500" />
              <span x-text="uploadError"></span>
            </div>
            <button type="button" @click="uploadError = null" class="text-rose-400 hover:text-rose-700">×</button>
          </div>
          <div x-show="uploadSuccess" class="mb-2.5 p-2.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900/60 text-emerald-700 dark:text-emerald-300 text-xs flex items-center gap-2">
            <x-icon name="check" class="w-4 h-4 shrink-0 text-emerald-500" />
            <span x-text="uploadSuccess"></span>
          </div>

          <div class="space-y-2">
            <div x-show="attachments.length === 0" @click="$refs.fileInput.click()" class="p-4 rounded-xl border border-dashed border-[#cbd5e1] dark:border-[#2b3b55] hover:border-[#034694] dark:hover:border-blue-500 bg-[#f8fafc]/60 dark:bg-[#162032]/40 text-center cursor-pointer transition-colors group">
              <x-icon name="upload" class="w-5 h-5 mx-auto text-[#94a3b8] group-hover:text-[#034694] dark:group-hover:text-blue-400 mb-1.5 transition-colors" />
              <p class="text-xs font-semibold text-[#475569] dark:text-[#cbd5e1]">Klik untuk mengunggah dokumen, spreadsheet, arsip, atau gambar</p>
              <p class="text-[length:calc(10px+var(--fs-up))] text-[#94a3b8] mt-0.5 leading-relaxed">Mendukung format: Gambar (PNG, JPG, WEBP), PDF, Word, Excel (XLSX, CSV), Arsip (ZIP, RAR, 7Z), dan MD maks 50 MB (gambar otomatis terkompresi)</p>
            </div>
            <div x-show="attachments.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-2">
              <template x-for="att in attachments" :key="att.id">
                <div class="p-2.5 rounded-xl bg-white dark:bg-[#121929] border border-[#cbd5e1] dark:border-[#25354e] hover:border-[#94a3b8] dark:hover:border-[#384d70] flex items-center justify-between gap-2.5 shadow-2xs transition-all group">
                  <div class="flex items-center gap-2.5 min-w-0 flex-1">
                    <div class="w-9 h-9 rounded-lg bg-[#f1f5f9] dark:bg-[#1a2538] flex items-center justify-center shrink-0 overflow-hidden border border-[#e2e8f0] dark:border-[#2b3b55]">
                      <template x-if="att.file_type === 'image'"><img :src="att.file_url" :alt="att.file_name" class="w-full h-full object-cover"></template>
                      <span x-show="att.file_type === 'pdf'" class="contents"><x-icon name="file-text" class="w-4 h-4 text-rose-500" /></span>
                      <span x-show="att.file_type === 'doc'" class="contents"><x-icon name="file-text" class="w-4 h-4 text-blue-500" /></span>
                      <span x-show="att.file_type === 'excel'" class="contents"><x-icon name="file-spreadsheet" class="w-4 h-4 text-teal-600 dark:text-teal-400" /></span>
                      <span x-show="att.file_type === 'zip'" class="contents"><x-icon name="archive" class="w-4 h-4 text-amber-500" /></span>
                      <span x-show="!['image', 'pdf', 'doc', 'excel', 'zip'].includes(att.file_type)" class="contents"><x-icon name="file-code" class="w-4 h-4 text-purple-500" /></span>
                    </div>
                    <div class="min-w-0 flex-1">
                      <a :href="att.file_url" target="_blank" rel="noopener noreferrer" :title="att.file_name" x-text="att.file_name"
                        class="font-bold text-xs text-[#0f172a] dark:text-white truncate block hover:text-[#034694] dark:hover:text-blue-400 transition-colors"></a>
                      <div class="flex items-center gap-1.5 text-[length:calc(10px+var(--fs-up))] text-[#94a3b8] mt-0.5 flex-wrap">
                        <span class="px-1 py-px rounded font-extrabold border text-[length:calc(10px+var(--fs-up))]" :class="badge(att.file_type).color" x-text="badge(att.file_type).label"></span>
                        <span x-text="formatFileSize(att.file_size)"></span>
                        <template x-if="att.uploader?.name">
                          <span class="contents"><span>•</span><span class="truncate max-w-[80px]" x-text="att.uploader.name"></span></span>
                        </template>
                      </div>
                    </div>
                  </div>
                  <div class="flex items-center gap-1 shrink-0">
                    <a :href="att.file_url" target="_blank" rel="noopener noreferrer" :download="att.file_name" title="Buka / Unduh Berkas"
                      class="p-1.5 rounded-md text-[#475569] dark:text-[#94a3b8] hover:text-[#034694] dark:hover:text-blue-400 hover:bg-[#f1f5f9] dark:hover:bg-[#1f2d44] transition-colors">
                      <x-icon name="download" class="w-3.5 h-3.5" />
                    </a>
                    <button type="button" @click="deleteAttachment(att.id)" title="Hapus Lampiran" class="p-1.5 rounded-md text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors cursor-pointer">
                      <x-icon name="trash-2" class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </div>
              </template>
            </div>
          </div>
        </div>

        {{-- Activities & Comments --}}
        <div class="pt-4 border-t border-[#cbd5e1] dark:border-[#1e2a3f]">
          <div class="flex items-center justify-between border-b border-[#cbd5e1] dark:border-[#1e2a3f] pb-2 mb-3">
            <div class="flex items-center gap-1.5 p-1 bg-[#f1f5f9] dark:bg-[#162032] rounded-xl border border-[#cbd5e1]/60 dark:border-[#2b3b55]/60">
              <button type="button" @click="activeLogTab = 'activities'" class="{{ $tab }}" :class="activeLogTab === 'activities' ? @js($tabOn) : @js($tabOff)">
                <x-icon name="history" class="w-3.5 h-3.5" /><span x-text="'Riwayat Log (' + activities.length + ')'"></span>
              </button>
              <button type="button" @click="activeLogTab = 'comments'" class="{{ $tab }}" :class="activeLogTab === 'comments' ? @js($tabOn) : @js($tabOff)">
                <x-icon name="message-square" class="w-3.5 h-3.5" /><span x-text="'Diskusi (' + comments.length + ')'"></span>
              </button>
            </div>
          </div>

          {{-- Tab: activities --}}
          <div x-show="activeLogTab === 'activities'" class="space-y-3 mb-4 max-h-72 overflow-y-auto pr-1">
            <div x-show="activities.length === 0" class="text-xs text-[#94a3b8] text-center py-6 bg-[#f8fafc] dark:bg-[#162032]/40 rounded-xl border border-dashed border-[#cbd5e1] dark:border-[#2b3b55]">
              Belum ada riwayat aktivitas yang tercatat.
            </div>
            <div x-show="activities.length > 0" class="relative pl-5 space-y-3.5 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-[#e2e8f0] dark:before:bg-[#1e293b]">
              <template x-for="act in activities" :key="act.id">
                <div class="relative text-xs">
                  <div class="absolute -left-5 top-1 w-4 h-4 rounded-full flex items-center justify-center ring-2 ring-white dark:ring-[#111726]" :class="@js($dot)[actKind(act)]">
                    <span x-show="actKind(act) === 'create'" class="contents"><x-icon name="check" class="w-2.5 h-2.5" /></span>
                    <span x-show="actKind(act) === 'board'" class="contents"><x-icon name="folder-kanban" class="w-2.5 h-2.5" /></span>
                    <span x-show="actKind(act) === 'move'" class="contents"><x-icon name="arrow-right" class="w-2.5 h-2.5" /></span>
                    <span x-show="actKind(act) === 'upload'" class="contents"><x-icon name="paperclip" class="w-2.5 h-2.5" /></span>
                    <span x-show="['delete', 'other'].includes(actKind(act))" class="contents"><x-icon name="history" class="w-2.5 h-2.5" /></span>
                  </div>
                  <div class="bg-[#f8fafc] dark:bg-[#162032] p-2.5 rounded-xl border border-[#cbd5e1] dark:border-[#2b3b55] space-y-1.5 shadow-2xs">
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                      <div class="flex items-center gap-1.5">
                        <template x-if="act.user?.avatar_url"><img :src="act.user.avatar_url" :alt="act.user.name" class="w-4 h-4 rounded-full object-cover"></template>
                        <template x-if="!act.user?.avatar_url"><div class="w-4 h-4 rounded-full bg-[#034694] text-white flex items-center justify-center text-[length:calc(10px+var(--fs-up))] font-bold" x-text="act.user?.name?.charAt(0) || 'U'"></div></template>
                        <span class="font-extrabold text-[#0f172a] dark:text-white" x-text="actTitle(act)"></span>
                        <span x-show="act.user?.role" class="text-[length:calc(10px+var(--fs-up))] font-bold px-1 py-px rounded bg-slate-200 dark:bg-slate-750 text-slate-700 dark:text-slate-300 uppercase" x-text="act.user?.role"></span>
                        <span x-show="act.user?.unit_name" class="text-[length:calc(10px+var(--fs-up))] font-bold px-1 py-px rounded bg-blue-100 dark:bg-blue-950 text-[#034694] dark:text-blue-300" x-text="'#' + act.user?.unit_name"></span>
                      </div>
                      <span class="text-[length:calc(10px+var(--fs-up))] text-[#94a3b8]" x-text="$u.formatDate(act.created_at)"></span>
                    </div>
                    <p x-show="act.details?.summary" class="leading-relaxed text-[length:calc(11px+var(--fs-up))] font-medium text-[#475569] dark:text-[#94a3b8]" x-text="act.details?.summary"></p>
                    <div x-show="act.details?.changes?.length > 0" class="mt-1.5 pt-1.5 border-t border-[#e2e8f0] dark:border-[#2b3b55] space-y-1">
                      <template x-for="(ch, idx) in act.details?.changes || []" :key="idx">
                        <div class="text-[length:calc(10.5px+var(--fs-up))] flex items-center gap-1.5 flex-wrap">
                          <span class="font-bold text-[#64748b] dark:text-[#94a3b8]" x-text="ch.field + ':'"></span>
                          <span x-show="ch.old !== undefined" class="line-through text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-800/80 px-1 py-px rounded text-[length:calc(10px+var(--fs-up))]" x-text="String(ch.old)"></span>
                          <span x-show="ch.old !== undefined && ch.new !== undefined" class="contents"><x-icon name="arrow-right" class="w-2.5 h-2.5 text-slate-400" /></span>
                          <span x-show="ch.new !== undefined" class="font-extrabold text-[#034694] dark:text-blue-300 bg-[#e0edfb]/80 dark:bg-blue-950/70 px-1.5 py-px rounded text-[length:calc(10px+var(--fs-up))] border border-blue-200 dark:border-blue-900/60" x-text="String(ch.new)"></span>
                        </div>
                      </template>
                    </div>
                  </div>
                </div>
              </template>
            </div>
          </div>

          {{-- Tab: comments --}}
          <div x-show="activeLogTab === 'comments'">
            <div class="space-y-3 mb-4 max-h-60 overflow-y-auto pr-1">
              <div x-show="comments.length === 0" class="text-xs text-[#94a3b8] text-center py-4 bg-[#f8fafc] dark:bg-[#162032]/40 rounded-xl">Belum ada komentar. Mulai percakapan di sini!</div>
              <template x-for="comm in comments" :key="comm.id">
                <div class="flex items-start gap-2.5 text-xs">
                  <template x-if="comm.user?.avatar_url"><img :src="comm.user.avatar_url" :alt="comm.user.name" class="w-7 h-7 rounded-full object-cover shrink-0 mt-0.5 ring-1 ring-[#cbd5e1]"></template>
                  <template x-if="!comm.user?.avatar_url"><div class="w-7 h-7 rounded-full bg-[#034694] text-white flex items-center justify-center text-[length:calc(10px+var(--fs-up))] font-bold shrink-0" x-text="comm.user?.name?.charAt(0) || 'U'"></div></template>
                  <div class="flex-1 bg-[#f8fafc] dark:bg-[#162032] p-2.5 rounded-xl border border-[#cbd5e1] dark:border-[#2b3b55]">
                    <div class="flex items-center justify-between mb-1">
                      <span class="font-bold text-[#0f172a] dark:text-white" x-text="comm.user?.name || 'Anggota Tim'"></span>
                      <span class="text-[length:calc(10px+var(--fs-up))] text-[#94a3b8]" x-text="$u.formatDate(comm.created_at)"></span>
                    </div>
                    <template x-if="comm.image_url">
                      <div class="mb-1.5 rounded-lg overflow-hidden border border-[#cbd5e1] dark:border-[#2b3b55] max-w-xs">
                        <a :href="comm.image_url" target="_blank" rel="noopener noreferrer">
                          <img :src="comm.image_url" alt="Lampiran diskusi" class="w-full max-h-56 object-cover hover:opacity-90 transition-opacity cursor-pointer bg-black/5 dark:bg-white/5">
                        </a>
                      </div>
                    </template>
                    <p x-show="comm.content && comm.content !== '[Gambar]'" class="text-[#334155] dark:text-[#cbd5e1] leading-relaxed text-[length:calc(11px+var(--fs-up))]" x-text="comm.content"></p>
                  </div>
                </div>
              </template>
            </div>

            <form @submit.prevent="addComment()" class="space-y-2">
              <template x-if="commentImage">
                <div class="flex items-center gap-2 p-1.5 bg-[#f1f5f9] dark:bg-[#1a2538] rounded-lg border border-[#cbd5e1] dark:border-[#2b3b55] w-fit">
                  <img :src="commentImage.preview" alt="Pratinjau gambar" class="w-10 h-10 object-cover rounded-md border border-white/20">
                  <div class="text-[length:calc(10px+var(--fs-up))] text-[#475569] dark:text-[#cbd5e1] max-w-[150px] truncate" x-text="commentImage.file.name"></div>
                  <button type="button" @click="clearCommentImage()" class="text-rose-500 hover:text-rose-700 text-xs px-1 font-bold cursor-pointer" title="Batalkan gambar">×</button>
                </div>
              </template>
              <div class="flex gap-2">
                <input x-ref="commentImage" type="file" accept=".png,.jpg,.jpeg,.webp,.gif" class="hidden" @change="selectCommentImage($event)">
                <textarea rows="2" x-model="newComment" placeholder="Tulis komentar atau unggah gambar untuk tim..."
                  class="flex-1 p-2.5 bg-white dark:bg-[#162032] border border-[#cbd5e1] dark:border-[#2b3b55] rounded-xl text-xs text-[#0f172a] dark:text-white placeholder-[#94a3b8] focus:outline-none focus:border-[#034694] dark:focus:border-blue-500 resize-none"></textarea>
                <div class="flex flex-col gap-1 self-end">
                  <button type="button" @click="$refs.commentImage.click()" title="Unggah Gambar ke Diskusi"
                    class="p-2 border border-[#cbd5e1] dark:border-[#2b3b55] hover:border-[#034694] dark:hover:border-blue-500 text-[#64748b] hover:text-[#034694] dark:text-[#94a3b8] dark:hover:text-blue-400 bg-white dark:bg-[#162032] rounded-xl text-xs flex items-center justify-center transition-colors cursor-pointer">
                    <x-icon name="image" class="w-3.5 h-3.5" />
                  </button>
                  <button type="submit" title="Kirim Komentar" :disabled="(!newComment.trim() && !commentImage) || isSubmittingComment || isUploadingCommentImage"
                    class="px-3 py-2 bg-[#034694] hover:bg-[#023470] disabled:bg-[#cbd5e1] dark:disabled:bg-[#1e2a3f] text-white rounded-xl text-xs font-bold flex items-center justify-center gap-1 transition-colors cursor-pointer">
                    <span x-show="isSubmittingComment || isUploadingCommentImage" class="contents"><x-icon name="loader-2" class="w-3.5 h-3.5 animate-spin" /></span>
                    <span x-show="!(isSubmittingComment || isUploadingCommentImage)" class="contents"><x-icon name="send" class="w-3.5 h-3.5" /></span>
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </template>
  </div>

  {{-- Confirm save modal --}}
  <div x-show="isConfirmModalOpen" class="fixed inset-0 z-60 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4 animate-in fade-in duration-100">
    <div class="bg-white dark:bg-[#111726] rounded-2xl border border-[#cbd5e1] dark:border-[#1e2a3f] shadow-2xl w-full max-w-sm overflow-hidden animate-in zoom-in-95 duration-100" @click.stop>
      <div class="p-5 text-center space-y-3">
        <div class="w-12 h-12 rounded-full bg-blue-50 dark:bg-blue-950/60 text-[#034694] dark:text-blue-400 mx-auto flex items-center justify-center">
          <x-icon name="alert-triangle" class="w-6 h-6 text-amber-500" />
        </div>
        <div>
          <h4 class="text-sm font-extrabold text-[#0f172a] dark:text-white">Konfirmasi Simpan Perubahan</h4>
          <p class="text-xs text-[#475569] dark:text-[#94a3b8] mt-1">Apakah Anda yakin ingin menyimpan perubahan data pada tugas ini?</p>
        </div>
        <div class="bg-[#f8fafc] dark:bg-[#162032] p-3 rounded-xl border border-[#cbd5e1] dark:border-[#2b3b55] text-left text-[length:calc(11px+var(--fs-up))] space-y-1.5 text-[#334155] dark:text-[#cbd5e1]">
          <div class="font-bold text-[#0f172a] dark:text-white truncate" x-text="'&quot;' + draftTitle + '&quot;'"></div>
          <div x-show="draftCustomer"><span class="text-[#64748b] dark:text-[#94a3b8]">Customer:</span> <span class="font-bold" x-text="draftCustomer"></span></div>
          <div>
            <span class="text-[#64748b] dark:text-[#94a3b8]">Unit Tags:</span>
            <span x-show="draftUnitTags.length > 0" class="font-bold text-[#034694] dark:text-blue-300" x-text="draftUnitTags.map((t) => '#' + t).join(', ')"></span>
            <span x-show="draftUnitTags.length === 0">Belum ada tag unit</span>
          </div>
          <div><span class="text-[#64748b] dark:text-[#94a3b8]">Assignees:</span> <span class="font-bold" x-text="draftAssigneeIds.length + ' Anggota'"></span></div>
          <div><span class="text-[#64748b] dark:text-[#94a3b8]">Prioritas:</span> <span x-text="draftPriority"></span></div>
          <div><span class="text-[#64748b] dark:text-[#94a3b8]">Tanggal:</span> <span x-text="(draftStartDate || 'Belum diatur') + ' s/d ' + (draftDueDate || 'Belum diatur')"></span></div>
        </div>
        <div class="flex items-center justify-center gap-2 pt-2">
          <button type="button" @click="isConfirmModalOpen = false" :disabled="isSaving" class="w-1/2 px-3 py-2 border border-[#cbd5e1] dark:border-[#2b3b55] text-[#475569] dark:text-[#cbd5e1] hover:bg-[#f1f5f9] dark:hover:bg-[#162032] rounded-xl text-xs font-bold transition-colors">Batal</button>
          <button type="button" @click="confirmSave()" :disabled="isSaving" class="w-1/2 px-3 py-2 bg-[#034694] hover:bg-[#023470] text-white rounded-xl text-xs font-bold transition-all shadow-xs flex items-center justify-center gap-1.5">
            <span x-show="isSaving">Menyimpan...</span>
            <span x-show="!isSaving" class="contents"><x-icon name="check" class="w-3.5 h-3.5" /><span>Ya, Simpan</span></span>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
