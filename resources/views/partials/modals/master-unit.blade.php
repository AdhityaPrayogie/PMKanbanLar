{{-- Port of components/unit/MasterUnitModal.tsx --}}
@php
  $label = 'block text-[length:calc(11px+var(--fs-up))] font-bold text-[#475569] dark:text-[#cbd5e1] mb-1';
  $input = 'w-full px-3 py-2 border border-[#cbd5e1] dark:border-[#334460] rounded-lg text-xs bg-white dark:bg-[#101726] text-[#0f172a] dark:text-white focus:outline-none focus:border-[#034694]';
  $search = 'w-full pl-8 pr-7 py-1.5 bg-white dark:bg-[#101726] border border-[#cbd5e1] dark:border-[#334460] rounded-lg text-xs text-[#0f172a] dark:text-white placeholder-[#94a3b8] focus:outline-none focus:border-[#034694]';
  $clear = 'absolute right-2 top-1/2 -translate-y-1/2 text-xs text-[#94a3b8] hover:text-[#0f172a] dark:hover:text-white';
  $primary = 'bg-[#034694] hover:bg-[#023470] disabled:bg-[#cbd5e1] dark:disabled:bg-[#1e2a3f] text-white text-xs font-bold rounded-lg flex items-center gap-1.5 transition-all shadow-xs';
  $closeBtn = 'text-[#64748b] hover:text-[#0f172a] dark:hover:text-white rounded-lg hover:bg-[#e2e8f0] dark:hover:bg-[#1e293b] transition-colors';
  $unitOn = 'border-[#034694] dark:border-blue-500 bg-[#e0edfb]/40 dark:bg-blue-950/30 ring-1 ring-[#034694]';
  $unitOff = 'border-[#cbd5e1] dark:border-[#2b3b55] bg-white dark:bg-[#162032] hover:border-[#034694]';
  $roleAdmin = 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300';
  $roleUser = 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300';
@endphp
<div x-data="masterUnit" x-show="$store.kb.isMasterUnitModalOpen" x-cloak class="fixed inset-0 z-50 bg-slate-950/65 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5 animate-in fade-in duration-150">
  <div class="bg-white dark:bg-[#111726] rounded-2xl border border-[#cbd5e1] dark:border-[#1e2a3f] shadow-2xl w-full max-w-4xl lg:max-w-5xl h-[92vh] max-h-[92vh] overflow-hidden flex flex-col animate-in zoom-in-95 duration-150" @click.stop>
    {{-- Header --}}
    <div class="px-6 py-4 border-b border-[#cbd5e1] dark:border-[#1e2a3f] flex items-center justify-between bg-[#f8fafc] dark:bg-[#0d1424] shrink-0">
      <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-lg bg-[#034694] text-white flex items-center justify-center font-bold shadow-xs">
          <x-icon name="building-2" class="w-4 h-4" />
        </div>
        <div>
          <h3 class="text-sm font-bold text-[#0f172a] dark:text-white">Kelola Master Unit &amp; Penempatan Tim</h3>
          <p class="text-[length:calc(11px+var(--fs-up))] text-[#64748b] dark:text-[#94a3b8]">Master table units dan asosiasi unit untuk setiap pengguna</p>
        </div>
      </div>
      <button @click="$store.kb.closeMasterUnitModal()" class="p-1.5 {{ $closeBtn }}">
        <x-icon name="x" class="w-4 h-4" />
      </button>
    </div>

    <div class="flex-1 overflow-y-auto p-6 space-y-6">
      {{-- Section 1: Add New Master Unit Form --}}
      <div class="bg-[#f8fafc] dark:bg-[#162032] p-4 rounded-xl border border-[#cbd5e1] dark:border-[#2b3b55] space-y-3">
        <div class="flex items-center gap-2 text-xs font-bold text-[#034694] dark:text-blue-400">
          <x-icon name="plus" class="w-4 h-4" /><span>Tambah Unit Baru ke Master Table</span>
        </div>
        <form @submit.prevent="submit" class="space-y-3">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="sm:col-span-2">
              <label class="{{ $label }}">Nama Unit / Divisi <span class="text-rose-500">*</span></label>
              <input type="text" required x-model="name" placeholder="Contoh: Keuangan, Operasional, Legal..." class="{{ $input }}">
            </div>
            <div>
              <label class="{{ $label }}">Kode Tag <span class="text-rose-500">*</span></label>
              <input type="text" required maxlength="10" :value="code" @input="code = $event.target.value.toUpperCase()" placeholder="Contoh: KEU, OPS" class="{{ $input }} uppercase font-bold">
            </div>
          </div>
          <div>
            <label class="{{ $label }}">Deskripsi / Lingkup Kerja (Opsional)</label>
            <input type="text" x-model="description" placeholder="Deskripsi singkat fungsi unit kerja ini..." class="{{ $input }}">
          </div>
          <div class="flex justify-end pt-1">
            <button type="submit" :disabled="isSubmitting || !name.trim() || !code.trim()" class="px-4 py-2 {{ $primary }}">
              <x-icon name="plus" class="w-3.5 h-3.5" />
              <span x-text="isSubmitting ? 'Menyimpan...' : 'Tambah ke Master Units'"></span>
            </button>
          </div>
        </form>
      </div>

      {{-- Section 2: Master Units Directory --}}
      <div class="space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
          <h4 class="text-xs font-bold text-[#0f172a] dark:text-white uppercase tracking-wider flex items-center gap-1.5">
            <x-icon name="building-2" class="w-3.5 h-3.5 text-[#034694] dark:text-blue-400" />
            <span x-text="`Daftar Master Unit (${filteredUnits.length}${filteredUnits.length !== $store.kb.masterUnits.length ? ` dari ${$store.kb.masterUnits.length}` : ''})`"></span>
          </h4>
          <div class="relative w-full sm:w-60">
            <x-icon name="search" class="w-3.5 h-3.5 text-[#94a3b8] absolute left-2.5 top-1/2 -translate-y-1/2" />
            <input type="text" x-model="searchUnit" placeholder="Cari nama / kode unit..." class="{{ $search }}">
            <button x-show="searchUnit" @click="searchUnit = ''" class="{{ $clear }}">×</button>
          </div>
        </div>

        <div x-show="filteredUnits.length === 0" class="p-8 text-center bg-[#f8fafc] dark:bg-[#162032] border border-dashed border-[#cbd5e1] dark:border-[#2b3b55] rounded-xl text-xs text-[#94a3b8]"
          x-text="`Tidak ada unit yang cocok dengan pencarian &quot;${searchUnit}&quot;.`"></div>
        <div x-show="filteredUnits.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-64 overflow-y-auto pr-1 scrollbar-thin">
          <template x-for="u in filteredUnits" :key="u.id">
            <div @click="selectedUnitCode = selectedUnitCode === u.code ? 'All' : u.code"
              class="p-3.5 rounded-xl border transition-all cursor-pointer flex flex-col justify-between"
              :class="selectedUnitCode === u.code ? @js($unitOn) : @js($unitOff)">
              <div>
                <div class="flex items-center justify-between gap-2 mb-1.5">
                  <span class="flex items-center gap-1 px-2 py-0.5 rounded text-[length:calc(10px+var(--fs-up))] font-extrabold bg-[#e0edfb] dark:bg-[#1e293b] text-[#034694] dark:text-blue-300 border border-blue-200 dark:border-blue-900/60">
                    <x-icon name="tag" class="w-2.5 h-2.5 text-[#034694] dark:text-blue-400" />
                    <span x-text="'#' + u.code"></span>
                  </span>
                  <div class="flex items-center gap-1.5">
                    <span class="text-[length:calc(10px+var(--fs-up))] font-bold text-[#64748b] dark:text-[#94a3b8]" x-text="(u.users_count || 0) + ' Anggota'"></span>
                    <button type="button" @click.stop="openEdit(u)" class="p-1 rounded-md text-[#64748b] hover:text-[#034694] dark:hover:text-blue-400 hover:bg-[#e2e8f0]/80 dark:hover:bg-[#1e293b] transition-colors" title="Edit unit ini">
                      <x-icon name="pencil" class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </div>
                <div class="text-xs font-extrabold text-[#0f172a] dark:text-white" x-text="u.name"></div>
                <p class="text-[length:calc(11px+var(--fs-up))] text-[#64748b] dark:text-[#94a3b8] line-clamp-2 leading-relaxed mt-1" x-text="u.description || 'Unit kerja operasional.'"></p>
              </div>
              <div class="pt-2 mt-2 border-t border-[#cbd5e1]/60 dark:border-[#2b3b55]/60 flex items-center justify-between text-[length:calc(10px+var(--fs-up))] text-[#64748b] dark:text-[#94a3b8]">
                <span x-text="(u.projects_count || 0) + ' Board Terdaftar'"></span>
                <div class="flex items-center gap-2">
                  <button type="button" @click.stop="openEdit(u)" class="font-bold text-[#64748b] hover:text-[#034694] dark:hover:text-blue-300 transition-colors flex items-center gap-1">
                    <x-icon name="pencil" class="w-2.5 h-2.5" /><span>Edit</span>
                  </button>
                  <span class="font-bold text-[#034694] dark:text-blue-400" x-text="selectedUnitCode === u.code ? 'Menampilkan Tim' : 'Filter Tim →'"></span>
                </div>
              </div>
            </div>
          </template>
        </div>
      </div>

      {{-- Section 3: Users Unit Assignment --}}
      <div class="space-y-3 pt-3 border-t border-[#cbd5e1] dark:border-[#1e2a3f]">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
          <div class="flex items-center gap-2 flex-wrap">
            <h4 class="text-xs font-bold text-[#0f172a] dark:text-white uppercase tracking-wider flex items-center gap-1.5">
              <x-icon name="users" class="w-3.5 h-3.5 text-[#034694] dark:text-blue-400" />
              <span x-text="'Penempatan Unit Pengguna ' + (selectedUnitCode !== 'All' ? `(Unit #${selectedUnitCode})` : '(Semua Pengguna)')"></span>
            </h4>
            <span class="text-[length:calc(11px+var(--fs-up))] font-semibold text-[#64748b] dark:text-[#94a3b8]" x-text="`(${filteredUsers.length} dari ${$store.kb.allUsers.length} user)`"></span>
            <button x-show="selectedUnitCode !== 'All'" @click="selectedUnitCode = 'All'" class="text-[length:calc(10px+var(--fs-up))] font-bold text-[#034694] dark:text-blue-400 hover:underline cursor-pointer">(Reset Filter Unit)</button>
          </div>
          <div class="relative w-full sm:w-64">
            <x-icon name="search" class="w-3.5 h-3.5 text-[#94a3b8] absolute left-2.5 top-1/2 -translate-y-1/2" />
            <input type="text" x-model="searchUser" placeholder="Cari nama / email pengguna..." class="{{ $search }}">
            <button x-show="searchUser" @click="searchUser = ''" class="{{ $clear }}">×</button>
          </div>
        </div>

        <div class="border border-[#cbd5e1] dark:border-[#2b3b55] rounded-xl overflow-hidden max-h-72 overflow-y-auto scrollbar-thin">
          <table class="w-full text-left text-xs">
            <thead class="bg-[#f8fafc] dark:bg-[#0d1424] border-b border-[#cbd5e1] dark:border-[#1e2a3f] text-[#64748b] dark:text-[#94a3b8] font-bold text-[length:calc(11px+var(--fs-up))] sticky top-0 z-10">
              <tr>
                <th class="py-2.5 px-3.5">Nama Pengguna</th>
                <th class="py-2.5 px-3.5">Role</th>
                <th class="py-2.5 px-3.5">Unit / Divisi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#cbd5e1] dark:divide-[#1e2a3f] bg-white dark:bg-[#111726]">
              <tr x-show="filteredUsers.length === 0">
                <td colspan="3" class="py-8 text-center text-xs text-[#94a3b8] italic bg-white dark:bg-[#111726]" x-text="`Tidak ada pengguna yang cocok dengan pencarian &quot;${searchUser}&quot;.`"></td>
              </tr>
              <template x-for="user in filteredUsers" :key="user.id">
                <tr class="hover:bg-[#f8fafc] dark:hover:bg-[#162032] transition-colors">
                  <td class="py-2.5 px-3.5">
                    <div class="flex items-center gap-2">
                      <div class="w-7 h-7 rounded-full bg-[#034694] text-white flex items-center justify-center text-[length:calc(10px+var(--fs-up))] font-bold" x-text="user.name.charAt(0)"></div>
                      <div>
                        <div class="font-bold text-[#0f172a] dark:text-white">
                          <span x-text="user.name"></span><span x-show="user.id === $store.kb.activeUser?.id" class="ml-1.5 text-[length:calc(10px+var(--fs-up))] px-1 py-px bg-blue-100 text-[#034694] rounded font-bold">Anda</span>
                        </div>
                        <div class="text-[length:calc(10px+var(--fs-up))] text-[#64748b] dark:text-[#94a3b8]" x-text="user.email"></div>
                      </div>
                    </div>
                  </td>
                  <td class="py-2.5 px-3.5">
                    <span class="px-2 py-0.5 rounded text-[length:calc(10px+var(--fs-up))] font-bold" :class="user.role === 'admin' ? @js($roleAdmin) : @js($roleUser)" x-text="user.role"></span>
                  </td>
                  <td class="py-2.5 px-3.5">
                    <select @change="userUnitChange(user.id, $event.target.value, $event.target)" class="bg-[#f1f5f9] dark:bg-[#1a2538] border border-[#cbd5e1] dark:border-[#334460] rounded-lg px-2 py-1 text-xs font-bold text-[#0f172a] dark:text-white focus:outline-none focus:border-[#034694] cursor-pointer">
                      <template x-for="mu in $store.kb.masterUnits" :key="mu.id">
                        <option :value="mu.code" :selected="mu.code === (user.unit_name || 'IT')" x-text="`Unit: ${mu.name} (#${mu.code})`"></option>
                      </template>
                    </select>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- Footer --}}
    <div class="px-6 py-3 border-t border-[#cbd5e1] dark:border-[#1e2a3f] bg-[#f8fafc] dark:bg-[#0d1424] flex items-center justify-between shrink-0">
      <span class="text-[length:calc(11px+var(--fs-up))] text-[#64748b] dark:text-[#94a3b8]">Setiap user otomatis terikat pada unit kerjanya dan tercermin pada task/board.</span>
      <button @click="$store.kb.closeMasterUnitModal()" class="px-4 py-2 bg-[#034694] hover:bg-[#023470] text-white text-xs font-bold rounded-xl shadow-xs transition-colors">Selesai</button>
    </div>
  </div>

  {{-- Edit Master Unit Dialog --}}
  <div x-show="editingUnit" @click.stop="editingUnit = null" class="fixed inset-0 z-60 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4 animate-in fade-in duration-150">
    <div @click.stop class="bg-white dark:bg-[#111726] rounded-2xl border border-[#cbd5e1] dark:border-[#1e2a3f] shadow-2xl w-full max-w-md overflow-hidden flex flex-col animate-in zoom-in-95 duration-150">
      <div class="px-5 py-3.5 border-b border-[#cbd5e1] dark:border-[#1e2a3f] flex items-center justify-between bg-[#f8fafc] dark:bg-[#0d1424]">
        <div class="flex items-center gap-2.5">
          <div class="w-7 h-7 rounded-lg bg-[#034694] text-white flex items-center justify-center font-bold">
            <x-icon name="pencil" class="w-3.5 h-3.5" />
          </div>
          <div>
            <h4 class="text-xs font-bold text-[#0f172a] dark:text-white">Edit Master Unit</h4>
            <p class="text-[length:calc(10px+var(--fs-up))] text-[#64748b] dark:text-[#94a3b8]" x-text="editingUnit && `#${editingUnit.code} — ${editingUnit.name}`"></p>
          </div>
        </div>
        <button type="button" @click="editingUnit = null" class="p-1 {{ $closeBtn }}">
          <x-icon name="x" class="w-4 h-4" />
        </button>
      </div>

      <form @submit.prevent="submitEdit" class="p-5 space-y-3.5">
        <div x-show="editError" class="p-2.5 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/50 rounded-lg text-xs text-rose-600 dark:text-rose-400 font-medium" x-text="editError"></div>
        <div>
          <label class="{{ $label }}">Nama Unit / Divisi <span class="text-rose-500">*</span></label>
          <input type="text" required x-model="editName" placeholder="Contoh: Keuangan, IT..." class="{{ $input }}">
        </div>
        <div>
          <label class="{{ $label }}">Kode Tag <span class="text-rose-500">*</span></label>
          <input type="text" required maxlength="10" :value="editCode" @input="editCode = $event.target.value.toUpperCase()" placeholder="Contoh: KEU, IT" class="{{ $input }} uppercase font-bold">
          <p class="text-[length:calc(10px+var(--fs-up))] text-[#64748b] dark:text-[#94a3b8] mt-1">Kode tag diperbarui otomatis pada anggota tim dan filter board terkait.</p>
        </div>
        <div>
          <label class="{{ $label }}">Deskripsi / Lingkup Kerja (Opsional)</label>
          <textarea rows="2" x-model="editDescription" placeholder="Deskripsi singkat fungsi unit kerja ini..." class="{{ $input }} resize-none"></textarea>
        </div>
        <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#cbd5e1] dark:border-[#1e2a3f]">
          <button type="button" @click="editingUnit = null" class="px-3.5 py-1.5 text-xs font-bold text-[#64748b] dark:text-[#94a3b8] hover:text-[#0f172a] dark:hover:text-white rounded-lg hover:bg-[#e2e8f0] dark:hover:bg-[#1e293b] transition-colors">Batal</button>
          <button type="submit" :disabled="isEditingSubmitting || !editName.trim() || !editCode.trim()" class="px-4 py-1.5 {{ $primary }}">
            <x-icon name="check-circle-2" class="w-3.5 h-3.5" />
            <span x-text="isEditingSubmitting ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
