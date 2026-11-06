// Port of layout/Sidebar.tsx + layout/Header.tsx logic (markup in partials/sidebar|header.blade.php)
import { subscribeRealtime } from '../lib/realtime';
import { debounce } from '../lib/utils';

export default (Alpine) => {
  const kb = () => Alpine.store('kb');

  Alpine.data('sidebar', (path) => ({
    offBoard: path !== '/',
    get otherUsers() { return kb().allUsers.filter((u) => u.id !== kb().activeUser?.id); },
    get pendingCount() { return kb().allUsers.filter((u) => u.status === 'pending').length; },
    get visibleProjects() {
      const s = kb();
      if (s.activeUser?.role === 'admin') return s.projects;
      const unit = (s.activeUser?.unit_name || '').trim().toLowerCase();
      return s.projects.filter((p) => (p.unit_tag || '').trim().toLowerCase() === unit || p.name.toLowerCase().includes(unit));
    },
    // View links carry ?view=; on "/" they stay client-side
    view(e, v) {
      if (!this.offBoard) e.preventDefault();
      kb().setActiveView(v);
      kb().closeMobileSidebar();
    },
    pickProject(p) {
      kb().selectProject(p.id);
      kb().setActiveView('board');
      kb().closeMobileSidebar();
      if (this.offBoard) location.href = '/';
    },
  }));

  Alpine.data('header', () => ({
    notifOpen: false,
    userOpen: false,
    toast: null,
    localSearch: kb().searchQuery,
    timer: null,
    unsub: null,

    init() {
      const push = debounce((v) => kb().setSearchQuery(v), 250);
      this.$watch('localSearch', push);
      this.$watch('$store.kb.searchQuery', (v) => (this.localSearch = v));

      const me = kb().activeUser;
      if (!me) return;
      this.unsub = subscribeRealtime({
        notification: (n) => {
          kb().fetchNotifications();
          if (!n.title) return;
          this.toast = { id: n.id || String(Date.now()), title: n.title, message: n.message, type: n.type, taskId: n.related_task_id };
          clearTimeout(this.timer);
          this.timer = setTimeout(() => (this.toast = null), 5000);
        },
        // Message arrival is always followed by a 'notification' event, which refetches.
        read: (d) => { if (d.readerId === me.id) kb().fetchNotifications(); },
      });
    },
    destroy() {
      this.unsub?.();
      clearTimeout(this.timer);
    },

    get placeholder() {
      const v = kb().activeView;
      return v === 'hub' ? 'Cari board/project (Ctrl+K)...' : v === 'timeline' ? 'Cari tugas di timeline (Ctrl+K)...' : 'Cari tugas kanban (Ctrl+K)...';
    },
    get pendingUsersCount() { return kb().allUsers.filter((u) => u.status === 'pending').length; },
    isChat(n) { return n.related_task_id && kb().allUsers.some((u) => u.id === n.related_task_id); },
    notifIcon(n) {
      if (this.isChat(n)) return 'chat';
      return { deadline: 'deadline', mention: 'mention', assignment: 'assignment' }[n.type] || 'other';
    },
    clickNotif(n) {
      kb().markSingleNotificationAsRead(n.id);
      if (!n.related_task_id) return;
      const partner = kb().allUsers.find((u) => u.id === n.related_task_id);
      if (partner) kb().openMiniChatWith(partner);
      else kb().openTaskSheet(n.related_task_id);
      this.notifOpen = false;
    },
    clickToast() {
      const t = this.toast;
      if (t.taskId) kb().openTaskSheet(t.taskId);
      this.toast = null;
    },

    // Global keyboard shortcuts
    keydown(e) {
      // Inside a native <dialog> (confirm popup, helpdesk modals) the browser owns Esc/keys
      if (document.querySelector('dialog[open]')) return;
      const tag = e.target?.tagName;
      if (['INPUT', 'TEXTAREA', 'SELECT'].includes(tag)) {
        if (e.key === 'Escape') e.target.blur();
        return;
      }
      const k = e.key.toLowerCase();
      const act =
        ((e.ctrlKey || e.metaKey) && k === 'k') || e.key === '/' ? () => this.$refs.search.focus()
        : k === 'n' ? () => kb().openCreateModal()
        : k === 'b' ? () => kb().setActiveView('board')
        : k === 't' ? () => kb().setActiveView('timeline')
        : k === 'd' ? () => kb().toggleDarkMode()
        : e.key === '?' || k === 'h' ? () => kb().openHelp()
        : e.key === 'Escape' ? () => this.escape()
        : null;
      if (act) { e.preventDefault(); act(); }
    },

    // Esc closes the topmost open layer (help listed it, React version never did)
    escape() {
      const s = kb();
      const layers = [
        ['isHelpOpen', 'closeHelp'], ['isCreateUnitModalOpen', 'closeCreateUnitModal'],
        ['isMasterUnitModalOpen', 'closeMasterUnitModal'], ['isSettingsOpen', 'closeSettings'],
        ['isUserManagementOpen', 'closeUserManagement'], ['isCreateModalOpen', 'closeCreateModal'],
        ['isTaskSheetOpen', 'closeTaskSheet'], ['isMiniChatOpen', 'closeMiniChat'],
      ];
      const hit = layers.find(([flag]) => s[flag]);
      if (hit) s[hit[1]]();
      this.notifOpen = this.userOpen = false;
    },
  }));
};
