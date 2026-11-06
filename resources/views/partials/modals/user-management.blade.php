{{-- Port of components/admin/UserManagementModal.tsx --}}
@php
  $stat = 'bg-white dark:bg-[#141d2e] p-2.5 rounded-xl border border-[#cbd5e1] dark:border-[#223147] flex items-center gap-2.5';
  $statLabel = 'text-[length:calc(10px+var(--fs-up))] font-semibold text-[#64748b] dark:text-[#94a3b8]';
  $headBtn = 'p-2 text-[#64748b] hover:text-[#0f172a] dark:hover:text-white rounded-lg hover:bg-[#e2e8f0] dark:hover:bg-[#1e293b] transition-colors';
  $filterOn = 'bg-[#034694] text-white shadow-2xs';
  $filterOff = 'bg-[#f1f5f9] dark:bg-[#162032] text-[#475569] dark:text-[#cbd5e1] hover:bg-[#e2e8f0] dark:hover:bg-[#1f2d45] border border-[#cbd5e1] dark:border-[#2b3b55]';
  $passOn = 'bg-amber-50 dark:bg-amber-950/40 text-amber-900 dark:text-amber-200 border-amber-300 dark:border-amber-800 font-bold';
  $passOffMobile = 'bg-white dark:bg-[#182337] text-[#64748b] dark:text-[#94a3b8] border-[#cbd5e1] dark:border-[#283850]';
  $passOffDesk = 'bg-[#f1f5f9] dark:bg-[#182337] text-[#64748b] dark:text-[#94a3b8] border-[#cbd5e1] dark:border-[#283850]';
  $badge = 'inline-flex items-center px-2.5 py-1 rounded-full text-[length:calc(10px+var(--fs-up))] font-bold';
  $pending = 'bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-800 animate-pulse';
  $active = 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800';
  $rejected = 'bg-rose-100 dark:bg-rose-950/80 text-rose-800 dark:text-rose-300 border border-rose-300 dark:border-rose-800';
  $roleSel = 'px-2.5 bg-white dark:bg-[#162032] border border-[#cbd5e1] dark:border-[#2b3b55] rounded-lg text-xs font-semibold text-[#0f172a] dark:text-white focus:outline-none focus:border-[#034694] cursor-pointer disabled:opacity-60';
  $unitSel = 'px-2.5 bg-[#e0edfb]/80 dark:bg-[#16253d] border border-blue-200 dark:border-blue-900/60 rounded-lg text-xs font-extrabold text-[#034694] dark:text-blue-300 focus:outline-none cursor-pointer';
  $accBtn = 'bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold flex items-center gap-1 shadow-2xs transition-colors cursor-pointer';
  $rejBtn = 'bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-bold flex items-center gap-1 shadow-2xs transition-colors cursor-pointer';
  $delBtn = 'p-1.5 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition-colors cursor-pointer';
  $self = 'text-[length:calc(10px+var(--fs-up))] px-1.5 py-px rounded font-extrabold bg-[#e0edfb] text-[#034694] border border-blue-200';
@endphp
<div x-data="userManagement" x-show="$store.kb.isUserManagementOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-slate-950/60 backdrop-blur-xs animate-in fade-in">
  <div class="bg-white dark:bg-[#111726] rounded-2xl border border-[#cbd5e1] dark:border-[#1e2a3f] shadow-2xl w-full max-w-5xl overflow-hidden animate-in zoom-in-95 duration-150 flex flex-col max-h-[90vh]">
    {{-- Header --}}
    <div class="px-6 py-4 border-b border-[#cbd5e1] dark:border-[#1e2a3f] flex items-center justify-between bg-[#f8fafc] dark:bg-[#0d1322]">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-[#034694] text-white flex items-center justify-center shadow-sm">
          <x-icon name="shield" class="w-5 h-5 text-white" />
        </div>
        <div>
          <h3 class="text-base font-extrabold text-[#0f172a] dark:text-white flex items-center gap-2">
            <span>Kelola Pengguna &amp; Hak Akses Akun</span>
            <span x-show="count('pending') > 0" class="px-2.5 py-0.5 rounded-full text-[length:calc(10px+var(--fs-up))] font-black bg-amber-500 text-white animate-pulse shadow-2xs" x-text="count('pending') + ' Menunggu ACC'"></span>
          </h3>
          <p class="text-xs text-[#64748b] dark:text-[#94a3b8]">Approval registrasi pendaftaran, wewenang role, unit kerja, dan verifikasi password pengguna.</p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <button @click="loadUsers()" class="{{ $headBtn }}" title="Refresh Data Pengguna">
          <x-icon name="refresh-cw" class="w-4 h-4" x-bind:class="loading && 'animate-spin'" />
        </button>
        <button @click="$store.kb.closeUserManagement()" class="{{ $headBtn }}" title="Tutup Modal">
          <x-icon name="x" class="w-5 h-5" />
        </button>
      </div>
    </div>

    {{-- Stats Summary Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 px-6 py-3.5 bg-[#f1f5f9]/60 dark:bg-[#0a0f1a] border-b border-[#cbd5e1] dark:border-[#1e2a3f] text-xs">
      @foreach ([
        ['users', 'bg-blue-100 dark:bg-blue-950 text-[#034694] dark:text-blue-300', 'Total Pengguna', 'text-[#0f172a] dark:text-white', 'users.length'],
        ['clock', 'bg-amber-100 dark:bg-amber-950 text-amber-600 dark:text-amber-400', 'Menunggu ACC', 'text-amber-600 dark:text-amber-400', "count('pending')"],
        ['check-circle-2', 'bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400', 'Akun Aktif', 'text-emerald-600 dark:text-emerald-400', "count('active')"],
        ['alert-circle', 'bg-rose-100 dark:bg-rose-950 text-rose-600 dark:text-rose-400', 'Ditolak', 'text-rose-600 dark:text-rose-400', "count('rejected')"],
      ] as [$icon, $iconCls, $label, $valCls, $expr])
        <div class="{{ $stat }}">
          <div class="w-8 h-8 rounded-lg {{ $iconCls }} flex items-center justify-center font-bold shrink-0">
            <x-icon :name="$icon" class="w-4 h-4" />
          </div>
          <div>
            <div class="{{ $statLabel }}">{{ $label }}</div>
            <div class="text-sm font-black {{ $valCls }}" x-text="{{ $expr }} + ' Akun'"></div>
          </div>
        </div>
      @endforeach
    </div>

    {{-- Filter and Search Bar --}}
    <div class="px-6 py-3 border-b border-[#cbd5e1] dark:border-[#1e2a3f] bg-white dark:bg-[#111726] flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 text-xs">
      <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
        <span class="text-[#64748b] dark:text-[#94a3b8] font-bold mr-1">Status:</span>
        @foreach (['all', 'pending', 'active', 'rejected'] as $st)
          <button @click="statusFilter = @js($st)" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all shrink-0"
            :class="statusFilter === @js($st) ? @js($filterOn) : @js($filterOff)" x-text="filterLabel(@js($st))"></button>
        @endforeach
      </div>
      <div class="relative w-full sm:w-64">
        <x-icon name="search" class="w-3.5 h-3.5 text-[#94a3b8] absolute left-3 top-1/2 -translate-y-1/2" />
        <input type="text" x-model="searchQuery" placeholder="Cari nama, email, unit..."
          class="w-full pl-8 pr-3 py-1.5 bg-[#f8fafc] dark:bg-[#162032] border border-[#cbd5e1] dark:border-[#2b3b55] rounded-lg text-xs text-[#0f172a] dark:text-white placeholder-[#94a3b8] focus:outline-none focus:border-[#034694]">
      </div>
    </div>

    <div class="flex-1 overflow-y-auto p-4 sm:p-6">
      {{-- Mobile Card List (< md) --}}
      <div class="block md:hidden space-y-3">
        <div x-show="filteredUsers.length === 0" class="p-8 text-center text-xs text-[#94a3b8] bg-[#f8fafc] dark:bg-[#162032] rounded-xl border border-dashed border-[#cbd5e1] dark:border-[#223147]">
          Tidak ada pengguna dalam filter ini.
        </div>
        <template x-for="u in filteredUsers" :key="u.id">
          <div class="p-4 rounded-xl bg-white dark:bg-[#141d2e] border border-[#cbd5e1] dark:border-[#223147] shadow-xs space-y-3">
            <div class="flex items-start justify-between gap-2">
              <div class="flex items-center gap-3 min-w-0">
                <template x-if="u.avatar_url">
                  <img :src="u.avatar_url" :alt="u.name" class="w-10 h-10 rounded-full object-cover ring-1 ring-[#cbd5e1] shrink-0">
                </template>
                <template x-if="!u.avatar_url">
                  <div class="w-10 h-10 rounded-full bg-[#034694] text-white flex items-center justify-center font-bold text-sm shrink-0" x-text="u.name.charAt(0)"></div>
                </template>
                <div class="min-w-0">
                  <div class="font-bold text-sm text-[#0f172a] dark:text-white flex items-center gap-1.5 truncate">
                    <span class="truncate" x-text="u.name"></span>
                    <span x-show="isSelf(u)" class="{{ $self }} shrink-0">Anda</span>
                  </div>
                  <div class="text-xs text-[#64748b] dark:text-[#94a3b8] truncate" x-text="u.email"></div>
                </div>
              </div>
              <div class="shrink-0">
                <span x-show="u.status === 'pending'" class="{{ $badge }} gap-1 {{ $pending }}"><x-icon name="clock" class="w-3 h-3" /><span>Pending</span></span>
                <span x-show="u.status === 'active'" class="{{ $badge }} gap-1 {{ $active }}"><x-icon name="check-circle-2" class="w-3 h-3" /><span>Aktif</span></span>
                <span x-show="u.status !== 'pending' && u.status !== 'active'" class="{{ $badge }} gap-1 {{ $rejected }}"><x-icon name="alert-circle" class="w-3 h-3" /><span>Ditolak</span></span>
              </div>
            </div>

            <div class="p-2.5 rounded-lg bg-[#f8fafc] dark:bg-[#101726] border border-[#cbd5e1] dark:border-[#223147] flex items-center justify-between gap-2">
              <div class="flex items-center gap-2 min-w-0">
                <x-icon name="key-round" class="w-3.5 h-3.5 text-[#034694] dark:text-blue-400 shrink-0" />
                <span class="text-[length:calc(11px+var(--fs-up))] text-[#64748b] dark:text-[#94a3b8] shrink-0 font-medium">Password:</span>
                <span class="font-mono text-xs px-2 py-0.5 rounded border select-all truncate"
                  :class="visiblePasswords[u.id] ? @js($passOn) : @js($passOffMobile)"
                  x-text="visiblePasswords[u.id] ? (u.password_plain || 'password123') : '••••••••'"></span>
              </div>
              <div class="flex items-center gap-1 shrink-0">
                <button type="button" @click="togglePassword(u.id)" class="p-1.5 text-[#64748b] hover:text-[#034694] dark:hover:text-blue-400 rounded-md hover:bg-[#e2e8f0] dark:hover:bg-[#1e2a3f] cursor-pointer"
                  :title="visiblePasswords[u.id] ? 'Sembunyikan' : 'Lihat password'">
                  <span x-show="visiblePasswords[u.id]" class="contents"><x-icon name="eye-off" class="w-4 h-4" /></span>
                  <span x-show="!visiblePasswords[u.id]" class="contents"><x-icon name="eye" class="w-4 h-4" /></span>
                </button>
                <button type="button" @click="copyPassword(u)" class="p-1.5 text-[#64748b] hover:text-[#034694] dark:hover:text-blue-400 rounded-md hover:bg-[#e2e8f0] dark:hover:bg-[#1e2a3f] cursor-pointer" title="Salin password">
                  <span x-show="copiedId === u.id" class="contents"><x-icon name="check" class="w-4 h-4 text-emerald-500" /></span>
                  <span x-show="copiedId !== u.id" class="contents"><x-icon name="copy" class="w-4 h-4" /></span>
                </button>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-2 text-xs">
              <div>
                <label class="block text-[length:calc(10px+var(--fs-up))] font-bold text-[#64748b] dark:text-[#94a3b8] mb-1">Role Akses</label>
                <select :disabled="isSelf(u)" @change="updateUser(u.id, undefined, $event.target.value, $event.target)" class="w-full py-1.5 {{ $roleSel }}">
                  <option value="user" :selected="(u.role || 'user') === 'user'">User</option>
                  <option value="admin" :selected="u.role === 'admin'">Admin</option>
                </select>
              </div>
              <div>
                <label class="block text-[length:calc(10px+var(--fs-up))] font-bold text-[#64748b] dark:text-[#94a3b8] mb-1">Unit Kerja</label>
                <select @change="updateUserUnit(u.id, $event.target.value, $event.target)" class="w-full py-1.5 {{ $unitSel }}">
                  <template x-for="mu in $store.kb.masterUnits" :key="mu.id">
                    <option :value="mu.code" :selected="mu.code === (u.unit_name || 'IT')" x-text="'#' + mu.code"></option>
                  </template>
                </select>
              </div>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-[#e2e8f0] dark:border-[#223147] text-xs">
              <span class="text-[length:calc(10px+var(--fs-up))] text-[#64748b] dark:text-[#94a3b8]" x-text="'Daftar: ' + $u.formatDate(u.created_at)"></span>
              <div class="flex items-center gap-1.5">
                <button x-show="u.status !== 'active'" @click="updateUser(u.id, 'active')" class="px-3 py-1.5 text-xs {{ $accBtn }}">
                  <x-icon name="user-check" class="w-3.5 h-3.5" /><span>ACC</span>
                </button>
                <button x-show="u.status !== 'rejected' && !isSelf(u)" @click="updateUser(u.id, 'rejected')" class="px-3 py-1.5 text-xs {{ $rejBtn }}">
                  <x-icon name="user-x" class="w-3.5 h-3.5" /><span>Tolak</span>
                </button>
                <button x-show="!isSelf(u)" @click="deleteUser(u.id)" class="{{ $delBtn }}" title="Hapus Pengguna">
                  <x-icon name="trash-2" class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </template>
      </div>

      {{-- Desktop Table View (>= md) --}}
      <div class="hidden md:block border border-[#cbd5e1] dark:border-[#1e2a3f] rounded-xl overflow-hidden shadow-2xs">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs min-w-[850px]">
            <thead class="bg-[#f8fafc] dark:bg-[#0d1322] text-[#475569] dark:text-[#94a3b8] font-bold border-b border-[#cbd5e1] dark:border-[#1e2a3f]">
              <tr>
                <th class="p-3.5">Pengguna</th>
                <th class="p-3.5">Password</th>
                <th class="p-3.5">Role</th>
                <th class="p-3.5">Unit / Divisi</th>
                <th class="p-3.5">Status Akun</th>
                <th class="p-3.5">Terdaftar</th>
                <th class="p-3.5 text-right">Aksi Kelola</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#e2e8f0] dark:divide-[#1e2a3f]">
              <tr x-show="filteredUsers.length === 0">
                <td colspan="7" class="p-8 text-center text-[#94a3b8]">Tidak ada pengguna dalam filter ini.</td>
              </tr>
              <template x-for="u in filteredUsers" :key="u.id">
                <tr class="hover:bg-[#f8fafc] dark:hover:bg-[#162032]/60 transition-colors bg-white dark:bg-[#111726]">
                  <td class="p-3.5">
                    <div class="flex items-center gap-3">
                      <template x-if="u.avatar_url">
                        <img :src="u.avatar_url" :alt="u.name" class="w-8 h-8 rounded-full object-cover ring-1 ring-[#cbd5e1]">
                      </template>
                      <template x-if="!u.avatar_url">
                        <div class="w-8 h-8 rounded-full bg-[#034694] text-white flex items-center justify-center font-bold text-xs" x-text="u.name.charAt(0)"></div>
                      </template>
                      <div>
                        <div class="font-bold text-[#0f172a] dark:text-white flex items-center gap-1.5">
                          <span x-text="u.name"></span>
                          <span x-show="isSelf(u)" class="{{ $self }}">Anda</span>
                        </div>
                        <div class="text-[length:calc(11px+var(--fs-up))] text-[#64748b] dark:text-[#94a3b8]" x-text="u.email"></div>
                      </div>
                    </div>
                  </td>

                  <td class="p-3.5">
                    <div class="flex items-center gap-1.5">
                      <span class="font-mono text-xs px-2 py-1 rounded-md border select-all transition-all"
                        :class="visiblePasswords[u.id] ? @js($passOn) : @js($passOffDesk)"
                        x-text="visiblePasswords[u.id] ? (u.password_plain || 'password123') : '••••••••'"></span>
                      <button type="button" @click="togglePassword(u.id)" class="p-1 text-[#64748b] hover:text-[#034694] dark:hover:text-blue-400 rounded hover:bg-[#e2e8f0] dark:hover:bg-[#1e2a3f] transition-colors cursor-pointer"
                        :title="visiblePasswords[u.id] ? 'Sembunyikan password' : 'Lihat password'">
                        <span x-show="visiblePasswords[u.id]" class="contents"><x-icon name="eye-off" class="w-3.5 h-3.5" /></span>
                        <span x-show="!visiblePasswords[u.id]" class="contents"><x-icon name="eye" class="w-3.5 h-3.5" /></span>
                      </button>
                      <button type="button" @click="copyPassword(u)" class="p-1 text-[#64748b] hover:text-[#034694] dark:hover:text-blue-400 rounded hover:bg-[#e2e8f0] dark:hover:bg-[#1e2a3f] transition-colors cursor-pointer" title="Salin password">
                        <span x-show="copiedId === u.id" class="contents"><x-icon name="check" class="w-3.5 h-3.5 text-emerald-500" /></span>
                        <span x-show="copiedId !== u.id" class="contents"><x-icon name="copy" class="w-3.5 h-3.5" /></span>
                      </button>
                    </div>
                  </td>

                  <td class="p-3.5">
                    <select :disabled="isSelf(u)" @change="updateUser(u.id, undefined, $event.target.value, $event.target)" class="py-1 {{ $roleSel }}">
                      <option value="user" :selected="(u.role || 'user') === 'user'">User</option>
                      <option value="admin" :selected="u.role === 'admin'">Admin</option>
                    </select>
                  </td>

                  <td class="p-3.5">
                    <select @change="updateUserUnit(u.id, $event.target.value, $event.target)" class="py-1 {{ $unitSel }}">
                      <template x-for="mu in $store.kb.masterUnits" :key="mu.id">
                        <option :value="mu.code" :selected="mu.code === (u.unit_name || 'IT')" x-text="'#' + mu.code + ' (' + mu.name + ')'"></option>
                      </template>
                    </select>
                  </td>

                  <td class="p-3.5">
                    <span x-show="u.status === 'pending'" class="{{ $badge }} gap-1.5 {{ $pending }}"><x-icon name="clock" class="w-3 h-3" /><span>Menunggu ACC</span></span>
                    <span x-show="u.status === 'active'" class="{{ $badge }} gap-1.5 {{ $active }}"><x-icon name="check-circle-2" class="w-3 h-3" /><span>Aktif</span></span>
                    <span x-show="u.status !== 'pending' && u.status !== 'active'" class="{{ $badge }} gap-1.5 {{ $rejected }}"><x-icon name="alert-circle" class="w-3 h-3" /><span>Ditolak</span></span>
                  </td>

                  <td class="p-3.5 text-[length:calc(11px+var(--fs-up))] text-[#64748b] dark:text-[#94a3b8]" x-text="$u.formatDate(u.created_at)"></td>

                  <td class="p-3.5 text-right">
                    <div class="flex items-center justify-end gap-1.5">
                      <button x-show="u.status !== 'active'" @click="updateUser(u.id, 'active')" class="px-2.5 py-1.5 text-[length:calc(11px+var(--fs-up))] {{ $accBtn }}" title="ACC / Setujui Pengguna">
                        <x-icon name="user-check" class="w-3.5 h-3.5" /><span>ACC</span>
                      </button>
                      <button x-show="u.status !== 'rejected' && !isSelf(u)" @click="updateUser(u.id, 'rejected')" class="px-2.5 py-1.5 text-[length:calc(11px+var(--fs-up))] {{ $rejBtn }}" title="Tolak Pengguna">
                        <x-icon name="user-x" class="w-3.5 h-3.5" /><span>Tolak</span>
                      </button>
                      <button x-show="!isSelf(u)" @click="deleteUser(u.id)" class="{{ $delBtn }}" title="Hapus Akun Pengguna">
                        <x-icon name="trash-2" class="w-3.5 h-3.5" />
                      </button>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
