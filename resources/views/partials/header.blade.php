{{-- Port of components/layout/Header.tsx --}}
@php
  $tab = 'flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-md transition-all cursor-pointer';
  $tabOn = 'bg-white dark:bg-[#034694] text-[#034694] dark:text-white shadow-xs font-bold';
  $tabOff = 'text-[#475569] dark:text-[#94a3b8] hover:text-[#0f172a] dark:hover:text-white';
  $iconBtn = 'p-2 rounded-lg text-[#475569] dark:text-[#94a3b8] hover:bg-[#f1f5f9] dark:hover:bg-[#1e293b] hover:text-[#0f172a] dark:hover:text-white transition-colors';
  $menuItem = 'w-full flex items-center gap-2 px-3 py-2 text-xs text-[#334155] dark:text-[#cbd5e1] hover:bg-[#f1f5f9] dark:hover:bg-[#1b273d]';
  $tabs = [['hub', 'layers', 'Project Hub', 'Daftar Papan Proyek (Level 1)'], ['board', 'kanban', 'Board', 'Kanban Board (Level 2)'], ['timeline', 'clock', 'Timeline', 'Timeline & Subtasks Schedule']];
@endphp
<header x-data="header" @keydown.window="keydown($event)"
  class="h-16 bg-white dark:bg-[#111726] border-b border-[#cbd5e1] dark:border-[#1e2a3f] px-3 sm:px-6 flex items-center justify-between gap-2 sm:gap-4 shrink-0 shadow-xs z-30 transition-colors">
  <div class="flex items-center gap-2 sm:gap-3">
    <button @click="$store.kb.toggleMobileSidebar()" class="lg:hidden p-2 rounded-lg text-[#334155] dark:text-[#cbd5e1] hover:bg-[#f1f5f9] dark:hover:bg-[#1e293b] transition-colors" title="Buka Menu">
      <x-icon name="menu" class="w-5 h-5" />
    </button>
    <div class="flex items-center bg-[#f1f5f9] dark:bg-[#1a2436] p-1 rounded-lg border border-[#cbd5e1] dark:border-[#2b3a52]">
      @foreach ($tabs as [$v, $icon, $label, $title])
        <button @click="$store.kb.setActiveView('{{ $v }}')" class="{{ $tab }}" :class="$store.kb.activeView === '{{ $v }}' ? @js($tabOn) : @js($tabOff)" title="{{ $title }}">
          <x-icon :name="$icon" class="w-3.5 h-3.5" />
          <span class="hidden sm:inline">{{ $label }}</span>
        </button>
      @endforeach
    </div>
  </div>

  <div class="flex items-center gap-2 flex-1 max-w-md mx-2">
    <div class="relative flex-1">
      <x-icon name="search" class="w-4 h-4 text-[#94a3b8] absolute left-3 top-1/2 -translate-y-1/2" />
      <input x-ref="search" type="text" x-model="localSearch" :placeholder="placeholder"
        class="w-full pl-9 pr-7 py-1.5 bg-[#f8fafc] dark:bg-[#192336] border border-[#cbd5e1] dark:border-[#2b3b55] rounded-lg text-xs text-[#0f172a] dark:text-white placeholder-[#94a3b8] focus:outline-none focus:ring-2 focus:ring-[#034694]/20 focus:border-[#034694] transition-all">
      <button x-show="localSearch" x-cloak @click="localSearch = ''" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-xs text-[#94a3b8] hover:text-[#0f172a] dark:hover:text-white">×</button>
    </div>
    <div class="hidden md:flex items-center">
      <select x-model="$store.kb.priorityFilter" aria-label="Filter tasks by priority"
        class="px-2 py-1.5 bg-[#f8fafc] dark:bg-[#192336] border border-[#cbd5e1] dark:border-[#2b3b55] rounded-lg text-xs font-semibold text-[#334155] dark:text-[#cbd5e1] focus:outline-none focus:border-[#034694] cursor-pointer">
        <option value="All">All Priority</option>
        <option value="Urgent">Urgent</option>
        <option value="High">High</option>
        <option value="Medium">Medium</option>
        <option value="Low">Low</option>
      </select>
    </div>
  </div>

  <div class="flex items-center gap-1.5 sm:gap-2">
    <button @click="$store.kb.openCreateModal()" class="flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 bg-[#034694] hover:bg-[#023470] text-white text-xs font-bold rounded-lg shadow-sm transition-all">
      <x-icon name="plus" class="w-3.5 h-3.5" />
      <span class="hidden sm:inline">New Task</span>
    </button>

    <button @click="$store.kb.toggleDarkMode()" class="{{ $iconBtn }}" :title="$store.kb.isDarkMode ? 'Beralih ke Mode Terang (D)' : 'Beralih ke Mode Gelap (D)'">
      <span x-show="$store.kb.isDarkMode" class="contents"><x-icon name="sun" class="w-4 h-4 text-amber-400" /></span>
      <span x-show="!$store.kb.isDarkMode" class="contents"><x-icon name="moon" class="w-4 h-4" /></span>
    </button>

    <button @click="$store.kb.openHelp()" class="hidden sm:flex {{ $iconBtn }}" title="Panduan Pintasan Keyboard (?)">
      <x-icon name="help-circle" class="w-4 h-4" />
    </button>

    <div class="relative" @mousedown.outside="notifOpen = false">
      <button @click="notifOpen = !notifOpen" class="relative {{ $iconBtn }}" title="Notifications">
        <x-icon name="bell" class="w-4 h-4" />
        <span x-show="$store.kb.unreadNotifsCount > 0" x-cloak x-text="$store.kb.unreadNotifsCount > 99 ? '99+' : $store.kb.unreadNotifsCount"
          class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 bg-rose-500 text-white text-[length:calc(10px+var(--fs-up))] font-extrabold rounded-full flex items-center justify-center ring-2 ring-white dark:ring-[#111726] shadow-sm animate-pulse"></span>
      </button>
      <div x-show="notifOpen" x-cloak class="absolute right-0 mt-2 w-80 bg-white dark:bg-[#162032] border border-[#cbd5e1] dark:border-[#2b3b55] rounded-xl shadow-xl py-2 z-50 animate-in fade-in zoom-in-95 duration-100">
        <div class="px-4 py-2 border-b border-[#e2e8f0] dark:border-[#24334a] flex items-center justify-between">
          <div class="font-bold text-xs text-[#0f172a] dark:text-white">Semua Notifikasi Aksi</div>
          <button x-show="$store.kb.unreadNotifsCount > 0" @click="$store.kb.markNotificationsAsRead()" class="text-[length:calc(11px+var(--fs-up))] text-[#034694] dark:text-blue-400 hover:underline font-bold">Tandai terbaca</button>
        </div>
        <div class="max-h-72 overflow-y-auto divide-y divide-[#f1f5f9] dark:divide-[#1e293b]">
          <div x-show="!$store.kb.notifications.length" class="p-4 text-center text-xs text-[#94a3b8]">Belum ada notifikasi baru.</div>
          <template x-for="n in $store.kb.notifications" :key="n.id">
            <div @click="clickNotif(n)" class="p-3 flex items-start gap-3 hover:bg-[#f8fafc] dark:hover:bg-[#1b273d] cursor-pointer transition-colors" :class="!n.is_read && 'bg-[#e0edfb]/50 dark:bg-blue-950/30'">
              <div class="mt-0.5" x-data="{ get k() { return notifIcon(n) } }">
                <span x-show="k === 'chat'" class="contents"><x-icon name="message-square" class="w-4 h-4 text-[#034694] dark:text-blue-400" /></span>
                <span x-show="k === 'deadline'" class="contents"><x-icon name="alert-triangle" class="w-4 h-4 text-amber-500" /></span>
                <span x-show="k === 'mention'" class="contents"><x-icon name="at-sign" class="w-4 h-4 text-[#034694] dark:text-blue-400" /></span>
                <span x-show="k === 'assignment'" class="contents"><x-icon name="user-check" class="w-4 h-4 text-emerald-500" /></span>
                <span x-show="k === 'other'" class="contents"><x-icon name="check-circle-2" class="w-4 h-4 text-slate-500" /></span>
              </div>
              <div class="flex-1 min-w-0">
                <div class="text-xs font-bold text-[#0f172a] dark:text-white flex items-center justify-between">
                  <span class="truncate" x-text="n.title"></span>
                  <span class="text-[length:calc(10px+var(--fs-up))] text-[#94a3b8] font-normal shrink-0 ml-2" x-text="$u.formatDate(n.created_at)"></span>
                </div>
                <p class="text-[length:calc(11px+var(--fs-up))] text-[#475569] dark:text-[#94a3b8] mt-0.5 leading-snug" x-text="n.message"></p>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>

    <div class="relative" @mousedown.outside="userOpen = false">
      <button @click="userOpen = !userOpen" class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-[#f1f5f9] dark:hover:bg-[#1e293b] border border-[#cbd5e1] dark:border-[#2b3b55] transition-colors" title="Menu Akun">
        <template x-if="$store.kb.activeUser?.avatar_url"><img :src="$store.kb.activeUser.avatar_url" :alt="$store.kb.activeUser.name" class="w-6 h-6 rounded-full object-cover ring-1 ring-[#cbd5e1]"></template>
        <template x-if="!$store.kb.activeUser?.avatar_url"><div class="w-6 h-6 rounded-full bg-[#034694] text-white flex items-center justify-center text-xs font-bold" x-text="$store.kb.activeUser?.name?.charAt(0)"></div></template>
        <div class="text-left hidden lg:block">
          <span class="text-xs font-bold text-[#0f172a] dark:text-white block leading-tight" x-text="$store.kb.activeUser?.name?.split(' ')[0]"></span>
          <span class="text-[length:calc(10px+var(--fs-up))] uppercase tracking-wider text-[#034694] dark:text-blue-400 font-extrabold block leading-tight" x-text="$store.kb.activeUser?.role || 'user'"></span>
        </div>
        <x-icon name="chevron-down" class="w-3 h-3 text-[#94a3b8]" />
      </button>
      <div x-show="userOpen" x-cloak class="absolute right-0 mt-2 w-56 bg-white dark:bg-[#162032] border border-[#cbd5e1] dark:border-[#2b3b55] rounded-xl shadow-xl py-1.5 z-50">
        <div class="px-3 py-2 border-b border-[#e2e8f0] dark:border-[#24334a]">
          <div class="font-bold text-xs text-[#0f172a] dark:text-white" x-text="$store.kb.activeUser?.name"></div>
          <div class="text-[length:calc(10px+var(--fs-up))] text-[#64748b] dark:text-[#94a3b8]" x-text="$store.kb.activeUser?.email"></div>
        </div>
        <div class="py-1">
          <button x-show="$store.kb.activeUser?.role === 'admin'" @click="$store.kb.openUserManagement(); userOpen = false" class="{{ $menuItem }}">
            <x-icon name="shield" class="w-3.5 h-3.5 text-amber-500" />
            <span x-text="'Kelola Pengguna (' + pendingUsersCount + ' pending)'"></span>
          </button>
          <button @click="$store.kb.openSettings(); userOpen = false" class="{{ $menuItem }}">
            <x-icon name="settings" class="w-3.5 h-3.5 text-[#64748b]" /><span>Pengaturan Akun</span>
          </button>
          <button @click="$store.kb.openHelp(); userOpen = false" class="{{ $menuItem }}">
            <x-icon name="help-circle" class="w-3.5 h-3.5 text-[#64748b]" /><span>Pintasan Keyboard</span>
          </button>
        </div>
        <div class="border-t border-[#e2e8f0] dark:border-[#24334a] pt-1">
          <button @click="$store.kb.logout()" class="w-full flex items-center gap-2 px-3 py-2 text-xs text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 font-bold">
            <x-icon name="log-out" class="w-3.5 h-3.5" /><span>Keluar / Logout</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <div x-show="toast" x-cloak class="fixed top-16 right-6 z-50 max-w-sm w-full bg-white dark:bg-[#162032] border border-[#cbd5e1] dark:border-[#2b3b55] rounded-xl shadow-2xl p-3.5 flex items-start gap-3 animate-in slide-in-from-top-3 fade-in duration-200">
    <div class="p-2 rounded-lg bg-blue-50 dark:bg-blue-950/60 text-[#034694] dark:text-blue-400 shrink-0 mt-0.5">
      <x-icon name="bell" class="w-4 h-4 animate-bounce" />
    </div>
    <div class="flex-1 min-w-0 cursor-pointer" @click="clickToast()">
      <div class="text-xs font-bold text-[#0f172a] dark:text-white truncate" x-text="toast?.title"></div>
      <div class="text-[length:calc(11px+var(--fs-up))] text-[#64748b] dark:text-[#94a3b8] line-clamp-2 mt-0.5 leading-snug" x-text="toast?.message"></div>
    </div>
    <button @click="toast = null" class="text-[#94a3b8] hover:text-[#0f172a] dark:hover:text-white text-xs p-1 cursor-pointer font-bold">×</button>
  </div>
</header>
