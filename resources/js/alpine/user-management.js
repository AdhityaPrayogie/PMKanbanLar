// Port of components/admin/UserManagementModal.tsx
import { api } from '../lib/hd';

export default (Alpine) =>
  Alpine.data('userManagement', () => ({
    users: [],
    loading: false,
    statusFilter: 'all',
    searchQuery: '',
    debouncedSearch: '',
    visiblePasswords: {},
    copiedId: null,

    init() {
      this.$watch('$store.kb.isUserManagementOpen', (open) => {
        if (!open) return;
        this.loadUsers();
        this.$store.kb.fetchMasterUnits();
      });
      let t;
      this.$watch('searchQuery', (q) => {
        clearTimeout(t);
        t = setTimeout(() => (this.debouncedSearch = q), 250);
      });
    },

    count(status) {
      return this.users.filter((u) => u.status === status).length;
    },

    filterLabel(st) {
      if (st === 'all') return `Semua (${this.users.length})`;
      if (st === 'pending') return `Pending (${this.count('pending')})`;
      if (st === 'active') return `Aktif (${this.count('active')})`;
      return `Ditolak (${this.count('rejected')})`;
    },

    get filteredUsers() {
      const q = this.debouncedSearch.trim().toLowerCase();
      return this.users.filter((u) => {
        if (this.statusFilter !== 'all' && u.status !== this.statusFilter) return false;
        if (!q) return true;
        return u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q) || (u.unit_name || '').toLowerCase().includes(q);
      });
    },

    isSelf(u) {
      return u.id === this.$store.kb.activeUser?.id;
    },

    async loadUsers() {
      this.loading = true;
      try {
        const data = await (await fetch('/api/admin/users')).json();
        if (data.users) this.users = data.users;
      } catch (err) {
        console.error('Failed to load users', err);
      } finally {
        this.loading = false;
      }
    },

    togglePassword(id) {
      this.visiblePasswords[id] = !this.visiblePasswords[id];
    },

    copyPassword(u) {
      navigator.clipboard.writeText(u.password_plain || 'password123');
      this.copiedId = u.id;
      setTimeout(() => (this.copiedId = null), 2000);
    },

    nameOf(userId) { return this.users.find((u) => u.id === userId)?.name || 'pengguna'; },

    // el = the changed <select>: on cancel/failure it is put back on the stored value
    async patch(body, opts, okMsg, el, stored) {
      const ok = await this.$store.kb.act(opts, okMsg, () => api('/api/admin/users', { method: 'PATCH', body: JSON.stringify(body) }));
      if (!ok && el) el.value = stored;
      if (ok) {
        this.loadUsers();
        this.$store.kb.fetchUsers();
      }
      return ok;
    },

    updateUser(userId, status, role, el) {
      const n = this.nameOf(userId);
      const opts = role ? { title: 'Ubah role akses?', message: `Role ${n} akan diubah menjadi ${role}.`, confirmText: 'Ya, Ubah' }
        : status === 'active' ? { title: 'Setujui pengguna?', message: `${n} akan diaktifkan dan bisa masuk.`, confirmText: 'Ya, Setujui' }
        : { title: 'Tolak pengguna?', message: `${n} tidak akan bisa masuk.`, confirmText: 'Ya, Tolak', danger: true };
      const msg = role ? `Role ${n} diubah menjadi ${role}` : status === 'active' ? `${n} berhasil disetujui` : `${n} ditolak`;
      this.patch({ userId, status, role }, opts, msg, el, this.users.find((u) => u.id === userId)?.role || 'user');
    },

    async updateUserUnit(userId, unitCode, el) {
      const target = this.$store.kb.masterUnits.find((m) => m.code === unitCode);
      const n = this.nameOf(userId);
      if (await this.patch({ userId, unit_id: target?.id || null, unit_name: unitCode },
        { title: 'Pindahkan unit pengguna?', message: `${n} akan ditempatkan di unit #${unitCode}.`, confirmText: 'Ya, Pindahkan' }, `Unit ${n} diubah ke #${unitCode}`, el, this.users.find((u) => u.id === userId)?.unit_name || 'IT')) {
        this.$store.kb.fetchMasterUnits();
      }
    },

    async deleteUser(userId) {
      const n = this.nameOf(userId);
      if (await this.$store.kb.act({ title: 'Hapus pengguna?', message: `Akun ${n} akan dihapus permanen.`, confirmText: 'Ya, Hapus', danger: true },
        `${n} berhasil dihapus`, () => api(`/api/admin/users?userId=${userId}`, { method: 'DELETE' }))) {
        this.loadUsers();
        this.$store.kb.fetchUsers();
      }
    },
  }));
