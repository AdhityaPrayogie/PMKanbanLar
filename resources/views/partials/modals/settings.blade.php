{{-- Port of components/settings/SettingsModal.tsx --}}
@php
  $tab = 'py-3 border-b-2 flex items-center gap-1.5 transition-colors';
  $on = 'border-[#034694] text-[#034694] dark:text-blue-400';
  $off = 'border-transparent text-[#64748b] dark:text-[#94a3b8]';
  $label = 'block text-xs font-semibold text-[#0f172a] dark:text-white mb-1';
  $input = 'w-full px-3 py-2 border border-[#e2e8f0] dark:border-[#334155] rounded-md text-xs bg-white dark:bg-[#1e293b] text-[#0f172a] dark:text-white focus:outline-none focus:border-[#034694]';
  $btn = 'px-4 py-2 bg-[#034694] hover:bg-[#023470] text-white text-xs font-semibold rounded-md shadow-xs transition-colors';
@endphp
<div x-data="settingsModal" x-show="$store.kb.isSettingsOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs animate-in fade-in">
  <div class="bg-white dark:bg-[#111726] rounded-xl border border-[#e2e8f0] dark:border-[#1e293b] shadow-2xl w-full max-w-lg overflow-hidden animate-in zoom-in-95 duration-150 flex flex-col">
    <div class="px-6 py-4 border-b border-[#e2e8f0] dark:border-[#1e293b] flex items-center justify-between bg-[#f8fafc] dark:bg-[#0d1322]">
      <div class="flex items-center gap-2">
        <x-icon name="settings" class="w-4 h-4 text-[#034694] dark:text-blue-400" />
        <h3 class="text-sm font-bold text-[#0f172a] dark:text-white">Pengaturan Sistem &amp; Profil</h3>
      </div>
      <button @click="$store.kb.closeSettings()" class="p-1 text-[#64748b] hover:text-[#0f172a] dark:hover:text-white rounded">
        <x-icon name="x" class="w-4 h-4" />
      </button>
    </div>

    <div class="flex border-b border-[#e2e8f0] dark:border-[#1e293b] bg-white dark:bg-[#111726] px-6 text-xs font-semibold">
      <button @click="setTab('profile')" class="{{ $tab }} mr-4" :class="tab === 'profile' ? @js($on) : @js($off)">
        <x-icon name="user" class="w-3.5 h-3.5" /><span>Profil</span>
      </button>
      <button @click="setTab('security')" class="{{ $tab }} mr-4" :class="tab === 'security' ? @js($on) : @js($off)">
        <x-icon name="lock" class="w-3.5 h-3.5" /><span>Keamanan</span>
      </button>
      <button @click="setTab('appearance')" class="{{ $tab }}" :class="tab === 'appearance' ? @js($on) : @js($off)">
        <span x-show="$store.kb.isDarkMode" class="contents"><x-icon name="moon" class="w-3.5 h-3.5" /></span>
        <span x-show="!$store.kb.isDarkMode" class="contents"><x-icon name="sun" class="w-3.5 h-3.5" /></span>
        <span>Tema &amp; Tampilan</span>
      </button>
    </div>

    <div x-show="message" class="px-6 pt-4 pb-0">
      <div class="p-3 rounded-md text-xs flex items-center gap-2"
        :class="message?.type === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200'">
        <span x-show="message?.type === 'success'" class="contents"><x-icon name="check-circle-2" class="w-4 h-4 shrink-0 text-emerald-600" /></span>
        <span x-show="message?.type !== 'success'" class="contents"><x-icon name="alert-circle" class="w-4 h-4 shrink-0 text-rose-600" /></span>
        <span x-text="message?.text"></span>
      </div>
    </div>

    <div class="p-6">
      <form x-show="tab === 'profile'" @submit.prevent="updateProfile" class="space-y-4">
        <div>
          <label class="{{ $label }}">Nama Tampilan</label>
          <input type="text" required x-model="name" class="{{ $input }}">
        </div>
        <div>
          <label class="{{ $label }}">Avatar URL</label>
          <input type="text" x-model="avatarUrl" class="{{ $input }}">
        </div>
        <div class="flex items-center gap-3 pt-2">
          <img :src="avatarUrl || $store.kb.activeUser?.avatar_url || ''" alt="Preview" class="w-10 h-10 rounded-full object-cover ring-2 ring-[#034694]/20">
          <span class="text-xs text-[#64748b] dark:text-[#94a3b8]">Pratinjau Avatar pengguna saat ini</span>
        </div>
        <div class="pt-2 flex justify-end">
          <button type="submit" :disabled="loading" class="{{ $btn }}" x-text="loading ? 'Menyimpan...' : 'Simpan Profil'"></button>
        </div>
      </form>

      <form x-show="tab === 'security'" @submit.prevent="changePassword" class="space-y-4">
        <div>
          <label class="{{ $label }}">Password Lama</label>
          <input type="password" required x-model="oldPassword" class="{{ $input }}">
        </div>
        <div>
          <label class="{{ $label }}">Password Baru</label>
          <input type="password" required minlength="6" x-model="newPassword" class="{{ $input }}">
        </div>
        <div>
          <label class="{{ $label }}">Konfirmasi Password Baru</label>
          <input type="password" required x-model="confirmPassword" class="{{ $input }}">
        </div>
        <div class="pt-2 flex justify-end">
          <button type="submit" :disabled="loading" class="{{ $btn }}" x-text="loading ? 'Mengubah...' : 'Ubah Password'"></button>
        </div>
      </form>

      <div x-show="tab === 'appearance'" class="space-y-4">
        <div class="p-4 rounded-lg border border-[#e2e8f0] dark:border-[#1e293b] bg-[#f8fafc] dark:bg-[#0d1322] flex items-center justify-between">
          <div>
            <h4 class="text-xs font-bold text-[#0f172a] dark:text-white">Mode Gelap / Dark Mode</h4>
            <p class="text-[length:calc(11px+var(--fs-up))] text-[#64748b] dark:text-[#94a3b8] mt-0.5">Aktifkan tema gelap bersih untuk kenyamanan mata di ruangan redup</p>
          </div>
          <button type="button" @click="$store.kb.toggleDarkMode()" class="px-3 py-1.5 rounded-md text-xs font-semibold bg-white dark:bg-[#1e293b] border border-[#e2e8f0] dark:border-[#334155] text-[#0f172a] dark:text-white flex items-center gap-1.5 shadow-2xs hover:bg-[#f1f5f9] transition-colors">
            <span x-show="$store.kb.isDarkMode" class="contents"><x-icon name="moon" class="w-3.5 h-3.5 text-blue-400" /></span>
            <span x-show="!$store.kb.isDarkMode" class="contents"><x-icon name="sun" class="w-3.5 h-3.5 text-amber-500" /></span>
            <span x-text="$store.kb.isDarkMode ? 'Mode Gelap' : 'Mode Terang'"></span>
          </button>
        </div>
        <div class="p-4 rounded-lg border border-[#e2e8f0] dark:border-[#1e293b] bg-[#f8fafc] dark:bg-[#0d1322] text-xs">
          <div class="flex items-center gap-2 font-bold text-[#0f172a] dark:text-white mb-1">
            <x-icon name="database" class="w-3.5 h-3.5 text-[#034694] dark:text-blue-400" /><span>Koneksi Database</span>
          </div>
          <p class="text-[length:calc(11px+var(--fs-up))] text-[#64748b] dark:text-[#94a3b8]">MySQL 8.0 terhubung ke basis data <strong>kanbandb</strong> di port 3306.</p>
        </div>
      </div>
    </div>
  </div>
</div>
