// Port of store/useKanbanStore.ts -> Alpine.store('kb'). Templates read `$store.kb.x`, components call `$store.kb.fn()`.
// activeUser comes from the server (window.__USER__) so pages render without an /api/auth/me round-trip.
import { api } from './lib/hd';

let toastId = 0;

export default {
  activeUser: window.__USER__ || null,
  allUsers: [],
  currentProject: null,
  projects: [],
  masterUnits: [],
  columns: [],
  allTasks: [],
  masterLabels: [],
  searchQuery: '',
  priorityFilter: 'All',
  assigneeFilter: 'All',
  activeView: 'hub',

  selectedTaskId: null,
  isTaskSheetOpen: false,

  isCreateModalOpen: false,
  createModalColumnId: null,
  createModalParentId: null,

  isMiniChatOpen: false,
  miniChatUser: null,

  isUserManagementOpen: false,
  isSettingsOpen: false,
  isHelpOpen: false,
  isDarkMode: document.documentElement.classList.contains('dark'),
  isMobileSidebarOpen: false,
  isCreateUnitModalOpen: false,
  isMasterUnitModalOpen: false,
  createUnitModalDefaultStage: 'Active',
  createUnitModalDefaultTag: 'IT',

  notifications: [],
  unreadNotifsCount: 0,
  unreadMessagesCount: 0,

  // Global feedback: toast stack + confirm dialog (markup: partials/feedback.blade.php)
  toasts: [],
  confirm: null,

  toast(message, type = 'success') {
    const t = { id: ++toastId, message, type };
    this.toasts.push(t);
    setTimeout(() => (this.toasts = this.toasts.filter((x) => x.id !== t.id)), type === 'error' ? 5000 : 3500);
  },
  // Resolves false on cancel; true on OK (or the typed string when opts.input is set)
  ask(opts) {
    this.answer(false); // a newer prompt cancels a pending one
    return new Promise((resolve) => (this.confirm = { confirmText: 'Ya, Lanjutkan', danger: false, ...opts, value: opts.input, resolve }));
  },
  answer(ok) {
    const c = this.confirm;
    if (!c) return;
    this.confirm = null;
    c.resolve(ok ? (c.input !== undefined ? c.value : true) : false);
  },
  // confirm (skipped when opts is null) -> fn (throws on failure) -> toast. True only when fn finished.
  async act(opts, okMsg, fn) {
    if (opts && !(await this.ask(opts))) return false;
    try {
      await fn();
      if (okMsg) this.toast(okMsg);
      return true;
    } catch (err) {
      this.toast(err.message || 'Terjadi kesalahan sistem', 'error');
      return false;
    }
  },

  init() {
    const view = new URLSearchParams(location.search).get('view');
    if (view === 'hub' || view === 'board' || view === 'timeline') this.activeView = view;
  },

  openUserManagement() { this.isUserManagementOpen = true; },
  closeUserManagement() { this.isUserManagementOpen = false; },
  openSettings() { this.isSettingsOpen = true; },
  closeSettings() { this.isSettingsOpen = false; },
  openHelp() { this.isHelpOpen = true; },
  closeHelp() { this.isHelpOpen = false; },
  openCreateUnitModal(defaultStage, defaultTag) {
    this.isCreateUnitModalOpen = true;
    this.createUnitModalDefaultStage = defaultStage || 'Active';
    this.createUnitModalDefaultTag = defaultTag || 'IT';
  },
  closeCreateUnitModal() { this.isCreateUnitModalOpen = false; },
  openMasterUnitModal() { this.isMasterUnitModalOpen = true; },
  closeMasterUnitModal() { this.isMasterUnitModalOpen = false; },
  toggleMobileSidebar() { this.isMobileSidebarOpen = !this.isMobileSidebarOpen; },
  closeMobileSidebar() { this.isMobileSidebarOpen = false; },

  toggleDarkMode() {
    this.isDarkMode = !this.isDarkMode;
    document.documentElement.classList.toggle('dark', this.isDarkMode);
    try { localStorage.setItem('theme', this.isDarkMode ? 'dark' : 'light'); } catch (_) {}
  },

  async logout() {
    const ok = await this.act({ title: 'Keluar dari akun?', message: 'Sesi Anda akan diakhiri.', confirmText: 'Ya, Keluar', danger: true },
      'Berhasil keluar', () => api('/api/auth/logout', { method: 'POST' }));
    if (ok) setTimeout(() => (location.href = '/login'), 600);
  },

  setActiveUser(user) {
    this.activeUser = user;
    this.fetchNotifications();
  },

  setSearchQuery(q) { this.searchQuery = q; },
  setPriorityFilter(f) { this.priorityFilter = f; },
  setAssigneeFilter(f) { this.assigneeFilter = f; },
  setActiveView(v) { this.activeView = v; },

  openTaskSheet(taskId) { this.selectedTaskId = taskId; this.isTaskSheetOpen = true; },
  closeTaskSheet() { this.selectedTaskId = null; this.isTaskSheetOpen = false; },

  openCreateModal(columnId, parentId) {
    this.isCreateModalOpen = true;
    this.createModalColumnId = columnId || null;
    this.createModalParentId = parentId || null;
  },
  closeCreateModal() {
    this.isCreateModalOpen = false;
    this.createModalColumnId = null;
    this.createModalParentId = null;
  },

  openMiniChatWith(user) { this.isMiniChatOpen = true; this.miniChatUser = user; },
  closeMiniChat() { this.isMiniChatOpen = false; this.miniChatUser = null; },

  async fetchUsers() {
    try {
      const data = await (await fetch('/api/users')).json();
      if (data.users) this.allUsers = data.users;
    } catch (err) {
      console.error('Failed to fetch users', err);
    }
  },

  async fetchNotifications() {
    try {
      if (!this.activeUser) return;
      const data = await (await fetch('/api/notifications')).json();
      if (data.notifications) {
        this.notifications = data.notifications;
        this.unreadNotifsCount = data.unreadCount || 0;
      }
      this.fetchUnreadMessagesCount();
    } catch (err) {
      console.error('Failed to fetch notifications', err);
    }
  },

  async fetchUnreadMessagesCount() {
    try {
      if (!this.activeUser) return;
      const data = await (await fetch('/api/messages')).json();
      if (typeof data.totalUnreadMessages === 'number') this.unreadMessagesCount = data.totalUnreadMessages;
    } catch (err) {
      console.error('Failed to fetch unread messages count', err);
    }
  },

  async markNotificationsAsRead() {
    try {
      if (!this.activeUser) return;
      await fetch('/api/notifications', {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ markAll: true }),
      });
      this.notifications = this.notifications.map((n) => ({ ...n, is_read: true }));
      this.unreadNotifsCount = 0;
    } catch (err) {
      console.error('Failed to mark notifications read', err);
    }
  },

  async markSingleNotificationAsRead(id) {
    try {
      await fetch('/api/notifications', {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id }),
      });
      this.notifications = this.notifications.map((n) => (n.id === id ? { ...n, is_read: true } : n));
      this.unreadNotifsCount = this.notifications.filter((n) => !n.is_read).length;
    } catch (err) {
      console.error('Failed to mark single notification read', err);
    }
  },

  async fetchBoardData(projectId) {
    try {
      const targetId = projectId || this.currentProject?.id;
      const data = await (await fetch(targetId ? `/api/board?projectId=${targetId}` : '/api/board')).json();
      if (data.columns) {
        this.currentProject = data.project;
        this.projects = data.projects;
        this.columns = data.columns;
        this.allTasks = data.allTasks;
        this.masterLabels = data.masterLabels;
      }
      this.fetchMasterUnits();
    } catch (err) {
      console.error('Failed to load board data', err);
    }
  },

  async fetchMasterUnits() {
    try {
      const data = await (await fetch('/api/master-units')).json();
      if (data.units) this.masterUnits = data.units;
    } catch (err) {
      console.error('Failed to fetch master units:', err);
    }
  },

  createMasterUnit(name, code, description) {
    return this.act({ title: 'Tambah unit?', message: `Unit "${name}" (#${code}) akan ditambahkan ke Master Unit.`, confirmText: 'Ya, Tambah' },
      `Unit "${name}" berhasil ditambahkan`, async () => {
        const data = await api('/api/master-units', { method: 'POST', body: JSON.stringify({ name, code, description }) });
        if (!data.unit) throw new Error('Gagal menambahkan unit');
        await this.fetchMasterUnits();
        this.fetchNotifications();
      });
  },

  async updateMasterUnit(id, name, code, description) {
    if (!(await this.ask({ title: 'Simpan perubahan unit?', message: `Unit "${name}" (#${code}) akan diperbarui.`, confirmText: 'Ya, Simpan' }))) {
      return { success: false, cancelled: true };
    }
    try {
      await api('/api/master-units', { method: 'PATCH', body: JSON.stringify({ id, name, code, description }) });
      await this.fetchMasterUnits();
      await this.fetchUsers();
      await this.fetchBoardData();
      this.toast(`Unit "${name}" berhasil diperbarui`);
      return { success: true };
    } catch (err) {
      this.toast(err.message || 'Gagal memperbarui unit', 'error');
      return { success: false, error: err.message || 'Terjadi kesalahan sistem' };
    }
  },

  assignUserUnit(userId, unitId, unitName) {
    const u = this.allUsers.find((x) => x.id === userId);
    return this.act({ title: 'Pindahkan unit pengguna?', message: `${u?.name || 'Pengguna'} akan ditempatkan di unit #${unitName}.`, confirmText: 'Ya, Pindahkan' },
      'Unit pengguna diperbarui', async () => {
        await api('/api/admin/users', { method: 'PATCH', body: JSON.stringify({ userId, unit_id: unitId, unit_name: unitName }) });
        await this.fetchUsers();
        await this.fetchMasterUnits();
      });
  },

  async selectProject(projectId) {
    await this.fetchBoardData(projectId);
  },

  createUnit(name, description) {
    return this.act({ title: 'Buat project baru?', message: `Project "${name}" akan dibuat.`, confirmText: 'Ya, Buat' },
      `Project "${name}" berhasil dibuat`, async () => {
        const data = await api('/api/units', { method: 'POST', body: JSON.stringify({ name, description }) });
        if (!data.unit) throw new Error('Gagal membuat unit');
        await this.fetchBoardData(data.unit.id);
        this.fetchNotifications();
      });
  },

  createBoard(name, description, unit_tag = 'IT', stage = 'Active') {
    return this.act({ title: 'Buat board baru?', message: `Board "${name}" akan dibuat di tahap ${stage}.`, confirmText: 'Ya, Buat' },
      `Board "${name}" berhasil dibuat`, async () => {
        const data = await api('/api/units', { method: 'POST', body: JSON.stringify({ name, description, unit_tag, stage }) });
        if (!data.unit) throw new Error('Gagal membuat board');
        await this.fetchBoardData();
        this.fetchNotifications();
      });
  },

  async moveBoardStage(boardId, targetStage, newPosition) {
    const board = this.projects.find((p) => p.id === boardId);
    if (!(await this.ask({ title: 'Pindahkan board?', message: `Pindahkan "${board?.name || 'board'}" ke tahap ${targetStage}?`, confirmText: 'Ya, Pindahkan' }))) return false;
    this.projects = this.projects.map((p) =>
      p.id === boardId ? { ...p, stage: targetStage, position: newPosition ?? (p.position || 1000) } : p
    );
    let ok = true;
    try {
      await api('/api/units', { method: 'PATCH', body: JSON.stringify({ id: boardId, stage: targetStage, position: newPosition }) });
      this.toast(`Board dipindahkan ke ${targetStage}`);
    } catch (err) {
      ok = false;
      this.toast(err.message || 'Gagal memindahkan board', 'error');
    }
    await this.fetchBoardData();
    return ok;
  },

  deleteUnit(unitId) {
    const u = this.projects.find((p) => p.id === unitId);
    return this.act({ title: 'Hapus project?', message: `Project "${u?.name || ''}" akan dihapus.`, confirmText: 'Ya, Hapus', danger: true },
      'Project berhasil dihapus', async () => {
        await api(`/api/units?id=${unitId}`, { method: 'DELETE' });
        await this.fetchBoardData();
      });
  },

  async moveTask(taskId, targetColId, newPosition) {
    // Optimistic update
    const taskToMove = this.allTasks.find((t) => t.id === taskId);
    if (!taskToMove) return;
    const prevColId = taskToMove.column_id;
    if (prevColId === targetColId && taskToMove.position === newPosition) return;
    const to = this.columns.find((c) => c.id === targetColId);
    if (!(await this.ask({ title: 'Pindahkan tugas?', message: `Pindahkan "${taskToMove.title}" ke kolom ${to?.title || ''}?`, confirmText: 'Ya, Pindahkan' }))) return false;

    const updatedTask = { ...taskToMove, column_id: targetColId, position: newPosition };
    this.columns = this.columns.map((col) => {
      if (col.id === prevColId && prevColId !== targetColId) {
        return { ...col, tasks: (col.tasks || []).filter((t) => t.id !== taskId) };
      }
      if (col.id === targetColId) {
        const remaining = (col.tasks || []).filter((t) => t.id !== taskId);
        return { ...col, tasks: [...remaining, updatedTask].sort((a, b) => a.position - b.position) };
      }
      return col;
    });
    this.allTasks = this.allTasks.map((t) => (t.id === taskId ? updatedTask : t));

    try {
      await api('/api/tasks', { method: 'PATCH', body: JSON.stringify({ id: taskId, column_id: targetColId, position: newPosition }) });
      this.toast(`Tugas dipindahkan ke ${to?.title || 'kolom lain'}`);
      return true;
    } catch (err) {
      this.toast(err.message || 'Gagal memindahkan tugas', 'error');
      this.fetchBoardData(); // revert on error
      return false;
    }
  },
};
