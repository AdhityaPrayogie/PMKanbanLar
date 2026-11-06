{{-- Port of components/layout/Sidebar.tsx. $path is the current page path ("/", "/messages", ...) --}}
@php
  $path = '/'.ltrim(request()->path(), '/');
  $nav = 'flex items-center gap-2.5 px-2.5 py-2 text-xs font-semibold rounded-lg transition-all cursor-pointer';
  $on = 'bg-[#e0edfb] dark:bg-[#1e293b] text-[#034694] dark:text-blue-400 font-bold border border-blue-200 dark:border-blue-900/60 shadow-2xs';
  $off = 'border border-transparent text-[#334155] dark:text-[#cbd5e1] hover:bg-[#f1f5f9] dark:hover:bg-[#162032] hover:text-[#0f172a] dark:hover:text-white';
  $head = 'px-2.5 pb-1 text-[length:calc(10px+var(--fs-up))] font-extrabold uppercase tracking-wider text-[#64748b] dark:text-[#94a3b8]';
  $adminBtn = 'w-full flex items-center justify-between px-2.5 py-2 text-xs font-semibold rounded-lg text-[#334155] dark:text-[#cbd5e1] hover:bg-[#f1f5f9] dark:hover:bg-[#162032] hover:text-[#0f172a] dark:hover:text-white transition-all cursor-pointer border border-[#cbd5e1]/50 dark:border-[#1e2a3f] bg-white/40 dark:bg-[#131b2e]/40 shadow-2xs';
  $pref = 'w-full flex border border-transparent items-center gap-2.5 px-2.5 py-2 text-xs font-semibold rounded-lg text-[#475569] dark:text-[#94a3b8] hover:bg-[#f1f5f9] dark:hover:bg-[#162032] cursor-pointer transition-colors';
  $views = [['hub', 'layers', 'Project Hub'], ['board', 'kanban', 'Kanban Board'], ['timeline', 'clock', 'Timeline & Sub-tasks']];
  $isAdmin = ($user->role ?? null) === 'admin';
@endphp
<div x-data="sidebar(@js($path))" class="contents">
  <div x-show="$store.kb.isMobileSidebarOpen" x-cloak @click="$store.kb.closeMobileSidebar()"
    class="fixed inset-0 bg-slate-950/50 backdrop-blur-xs z-40 lg:hidden animate-in fade-in"></div>

  <aside class="fixed lg:static top-0 bottom-0 left-0 z-50 w-72 lg:w-64 bg-white dark:bg-[#111726] border-r border-[#cbd5e1] dark:border-[#1e2a3f] flex flex-col h-screen select-none shrink-0 shadow-md lg:shadow-none transition-transform duration-200 ease-in-out"
    :class="$store.kb.isMobileSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
    <div class="h-16 border-b border-[#cbd5e1] dark:border-[#1e2a3f] flex items-center justify-between px-5.5">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-lg bg-[#034694] flex items-center justify-center text-white shadow-sm font-bold text-lg">
          <x-icon name="kanban" class="w-5 h-5 text-white" />
        </div>
        <div>
          <div class="font-extrabold text-[#0f172a] dark:text-white text-base leading-tight tracking-tight flex items-center gap-1.5">
            PM Kanban
            <span class="text-[length:calc(10px+var(--fs-up))] px-1.5 py-0.5 rounded font-bold bg-[#e0edfb] dark:bg-blue-950 text-[#034694] dark:text-blue-300">v1.0</span>
          </div>
          <p class="text-xs text-[#475569] dark:text-[#94a3b8]">Modular Workspace</p>
        </div>
      </div>
      <button @click="$store.kb.closeMobileSidebar()" class="lg:hidden p-1.5 rounded-md text-[#475569] hover:text-[#0f172a] dark:hover:text-white hover:bg-[#f1f5f9] dark:hover:bg-[#1e2a3f]">
        <x-icon name="x" class="w-5 h-5" />
      </button>
    </div>

    <div class="flex-1 overflow-y-auto px-3 py-3 space-y-5 scrollbar-thin">
      <div class="space-y-1">
        <div class="{{ $head }}">Workspace Views</div>
        @foreach ($views as [$v, $icon, $label])
          <a href="/?view={{ $v }}" @click="view($event, '{{ $v }}')" class="{{ $nav }}"
            :class="!offBoard && $store.kb.activeView === '{{ $v }}' ? @js($on) : @js($off)">
            <x-icon :name="$icon" class="w-4 h-4 shrink-0 text-[#034694] dark:text-blue-400" />
            <span>{{ $label }}</span>
          </a>
        @endforeach
        <a href="/messages" @click="$store.kb.closeMobileSidebar()" class="{{ str_replace('gap-2.5', 'justify-between', $nav) }} {{ $path === '/messages' ? $on : $off }}">
          <div class="flex items-center gap-2.5">
            <x-icon name="message-square" class="w-4 h-4 shrink-0 text-[#034694] dark:text-blue-400" />
            <span>Private Chat</span>
          </div>
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
        </a>
      </div>

      <div class="space-y-1">
        <div class="{{ $head }}">Layanan IT</div>
        @foreach ([['/pengajuan', 'Pengajuan Helpdesk', 'life-buoy', true], ['/helpdesk', 'Helpdesk IT', 'headset', \App\Support\Auth::canHelpdesk($user)]] as [$href, $label, $icon, $show])
          @if ($show)
            <a href="{{ $href }}" @click="$store.kb.closeMobileSidebar()" @if ($path === $href) aria-current="page" @endif class="{{ $nav }} {{ $path === $href ? $on : $off }}">
              <x-icon :name="$icon" class="w-4 h-4 shrink-0 text-[#034694] dark:text-blue-400" />
              <span>{{ $label }}</span>
            </a>
          @endif
        @endforeach
      </div>

      <div class="space-y-1">
        <div class="{{ $head }} flex items-center justify-between">
          <span class="flex items-center gap-1.5">
            <x-icon name="folder-kanban" class="w-3.5 h-3.5 text-[#034694] dark:text-blue-400" />
            <span>List Project</span>
          </span>
          <button @click="$store.kb.openCreateUnitModal(); $store.kb.closeMobileSidebar()" title="Tambah Project Baru"
            class="p-1 rounded text-[#034694] dark:text-blue-400 hover:bg-[#e0edfb] dark:hover:bg-[#1e293b] transition-colors cursor-pointer">
            <x-icon name="plus" class="w-3.5 h-3.5" />
          </button>
        </div>
        <div class="space-y-1 max-h-40 overflow-y-auto pr-1 scrollbar-thin">
          <div x-show="!visibleProjects.length" class="p-3 text-center text-[length:calc(11px+var(--fs-up))] text-[#94a3b8] italic bg-[#f8fafc] dark:bg-[#111726] rounded-lg border border-dashed border-[#cbd5e1] dark:border-[#202e44]"
            x-text="'Belum ada project untuk #' + ($store.kb.activeUser?.unit_name || 'Unit')"></div>
          <template x-for="proj in visibleProjects" :key="proj.id">
            <button @click="pickProject(proj)"
              class="w-full flex items-center justify-between px-2.5 py-2 text-xs font-semibold rounded-lg transition-all text-left group cursor-pointer"
              :class="proj.id === $store.kb.currentProject?.id && $store.kb.activeView === 'board' ? @js($on) : @js($off)">
              <div class="truncate mr-2 flex items-center gap-1.5 min-w-0">
                <span class="text-[length:calc(10px+var(--fs-up))] px-1 py-px rounded font-bold bg-[#f1f5f9] dark:bg-[#1e293b] text-[#034694] dark:text-blue-300 border border-blue-200 dark:border-blue-900/50 shrink-0" x-text="'#' + (proj.unit_tag || 'IT')"></span>
                <span class="truncate" x-text="proj.name"></span>
              </div>
              <span class="text-[length:calc(10px+var(--fs-up))] px-1.5 py-px rounded-full font-bold shrink-0"
                :class="proj.id === $store.kb.currentProject?.id ? 'bg-[#034694] text-white dark:bg-blue-600' : 'bg-[#cbd5e1] dark:bg-[#24334a] text-[#475569] dark:text-[#cbd5e1]'"
                x-text="proj.tasks_count || 0"></span>
            </button>
          </template>
        </div>
      </div>

      <div class="space-y-1">
        <div class="{{ $head }} flex items-center justify-between">
          <span>Direct Message</span>
          <x-icon name="users" class="w-3.5 h-3.5" />
        </div>
        <div class="space-y-1 max-h-56 overflow-y-auto pr-1 scrollbar-thin">
          <template x-for="user in otherUsers" :key="user.id">
            <button @click="$store.kb.openMiniChatWith(user); $store.kb.closeMobileSidebar()" x-data="{ get st() { return $u.formatLastActive(user.last_active_at) } }"
              class="w-full flex items-center gap-2.5 border border-transparent px-2.5 py-2 text-xs font-medium rounded-lg text-[#334155] dark:text-[#cbd5e1] hover:bg-[#f1f5f9] dark:hover:bg-[#162032] hover:text-[#0f172a] dark:hover:text-white transition-colors text-left group cursor-pointer">
                <div class="relative shrink-0">
                  <template x-if="user.avatar_url"><img :src="user.avatar_url" :alt="user.name" class="w-6 h-6 rounded-full object-cover ring-1 ring-[#cbd5e1]"></template>
                  <template x-if="!user.avatar_url"><div class="w-6 h-6 rounded-full bg-[#034694] text-white flex items-center justify-center text-[length:calc(10px+var(--fs-up))] font-bold" x-text="user.name.charAt(0)"></div></template>
                  <span class="absolute -bottom-0.5 -right-0.5 w-2 h-2 rounded-full ring-2 ring-white dark:ring-[#111726]" :title="st.text"
                    :class="st.isOnline ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400 dark:bg-slate-600'"></span>
                </div>
                {{-- two lines: name + unit on top, status below, so the name gets the full row width --}}
                <div class="min-w-0 flex-1 leading-snug">
                  <div class="flex items-center gap-1.5 min-w-0">
                    <span class="truncate font-semibold text-[#0f172a] dark:text-white text-xs" x-text="user.name"></span>
                    <span class="text-[length:calc(10px+var(--fs-up))] px-1 py-px rounded font-bold bg-[#e0edfb] dark:bg-[#1e293b] text-[#034694] dark:text-blue-300 shrink-0" x-text="'#' + (user.unit_name || 'IT')"></span>
                  </div>
                  <div class="truncate text-[length:calc(10px+var(--fs-up))] font-medium" :class="st.isOnline ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-[#94a3b8]'"
                    x-text="st.isOnline ? 'Online' : st.text"></div>
                </div>
            </button>
          </template>
        </div>
      </div>

      @if ($isAdmin)
        <div class="space-y-1">
          <div class="{{ $head }} flex items-center justify-between">
            <span>Administrasi</span>
            <x-icon name="shield" class="w-3.5 h-3.5 text-amber-500" />
          </div>
          <button @click="$store.kb.openUserManagement(); $store.kb.closeMobileSidebar()" class="{{ $adminBtn }}">
            <div class="flex items-center gap-2.5">
              <x-icon name="users" class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0" />
              <span>Kelola Pengguna</span>
            </div>
            <span x-show="pendingCount > 0" class="px-1.5 py-0.5 rounded-full text-[length:calc(10px+var(--fs-up))] font-black bg-amber-500 text-white animate-pulse" x-text="pendingCount + ' ACC'"></span>
            <span x-show="pendingCount === 0" class="text-[length:calc(10px+var(--fs-up))] px-1.5 py-px rounded-full font-bold bg-[#f1f5f9] dark:bg-[#1e293b] text-[#64748b] dark:text-[#94a3b8]" x-text="$store.kb.allUsers.length"></span>
          </button>
          <button @click="$store.kb.openMasterUnitModal(); $store.kb.closeMobileSidebar()" class="{{ $adminBtn }}" title="Kelola Master Table Units & Penempatan Tim">
            <div class="flex items-center gap-2.5">
              <x-icon name="building-2" class="w-4 h-4 text-[#034694] dark:text-blue-400 shrink-0" />
              <span>Master Unit</span>
            </div>
            <span class="text-[length:calc(10px+var(--fs-up))] px-1.5 py-px rounded-full font-bold bg-[#e0edfb] dark:bg-[#1e293b] text-[#034694] dark:text-blue-300 border border-blue-200 dark:border-blue-900/60" x-text="$store.kb.masterUnits.length"></span>
          </button>
        </div>
      @endif

      <div class="space-y-1 pt-2 border-t border-[#e2e8f0] dark:border-[#1e2a3f]">
        <div class="{{ $head }}">Preferensi</div>
        <button @click="$store.kb.openSettings(); $store.kb.closeMobileSidebar()" class="{{ $pref }}">
          <x-icon name="settings" class="w-4 h-4 text-[#64748b] dark:text-[#94a3b8]" /><span>Pengaturan</span>
        </button>
        <button @click="$store.kb.openHelp(); $store.kb.closeMobileSidebar()" class="{{ $pref }}">
          <x-icon name="help-circle" class="w-4 h-4 text-[#64748b] dark:text-[#94a3b8]" /><span>Pintasan Keyboard</span>
        </button>
        <button @click="$store.kb.toggleDarkMode()" class="{{ str_replace('gap-2.5', 'justify-between', $pref) }}">
          <div class="flex items-center gap-2.5">
            <span x-show="$store.kb.isDarkMode" class="contents"><x-icon name="sun" class="w-4 h-4 text-amber-400" /></span>
            <span x-show="!$store.kb.isDarkMode" class="contents"><x-icon name="moon" class="w-4 h-4 text-slate-500" /></span>
            <span x-text="$store.kb.isDarkMode ? 'Mode Terang' : 'Mode Gelap'"></span>
          </div>
          <span class="text-[length:calc(10px+var(--fs-up))] font-mono px-1.5 py-0.5 rounded bg-[#f1f5f9] dark:bg-[#1e293b] text-[#64748b] dark:text-[#94a3b8]">D</span>
        </button>
      </div>
    </div>

    <div class="px-5.5 py-3 border-t border-[#cbd5e1] dark:border-[#1e2a3f] bg-[#f8fafc] dark:bg-[#0d1424]">
      <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-2.5 min-w-0">
          <div class="relative shrink-0">
            <template x-if="$store.kb.activeUser?.avatar_url"><img :src="$store.kb.activeUser.avatar_url" :alt="$store.kb.activeUser.name" class="w-8 h-8 rounded-full object-cover ring-2 ring-[#034694]/20"></template>
            <template x-if="!$store.kb.activeUser?.avatar_url"><div class="w-8 h-8 rounded-full bg-[#034694] text-white flex items-center justify-center font-bold text-xs" x-text="$store.kb.activeUser?.name?.charAt(0) || 'U'"></div></template>
            <span class="absolute bottom-0 right-0 w-2 h-2 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-[#111726]"></span>
          </div>
          <div class="min-w-0">
            <div class="text-xs font-bold text-[#0f172a] dark:text-white truncate" x-text="$store.kb.activeUser?.name || 'Active User'"></div>
            <div class="text-[length:calc(10px+var(--fs-up))] text-[#034694] dark:text-blue-400 font-bold flex items-center gap-1 truncate">
              <span class="uppercase" x-text="$store.kb.activeUser?.role || 'user'"></span>
              <span>•</span>
              <span class="px-1.5 py-px rounded font-extrabold bg-[#e0edfb] dark:bg-[#1e293b] text-[#034694] dark:text-blue-300 truncate" x-text="'#' + ($store.kb.activeUser?.unit_name || 'IT')"></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </aside>
</div>
