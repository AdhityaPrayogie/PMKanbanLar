// Port of components/settings/SettingsModal.tsx
export default (Alpine) =>
  Alpine.data('settingsModal', () => ({
    tab: 'profile',
    name: Alpine.store('kb').activeUser?.name || '',
    avatarUrl: Alpine.store('kb').activeUser?.avatar_url || '',
    oldPassword: '',
    newPassword: '',
    confirmPassword: '',
    message: null,
    loading: false,

    setTab(t) {
      this.tab = t;
      this.message = null;
    },

    async patch(body, fallback) {
      const res = await fetch('/api/settings', { method: 'PATCH', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
      const data = await res.json();
      if (!res.ok) {
        this.message = { type: 'error', text: data.error || fallback };
        this.$store.kb.toast(this.message.text, 'error');
        return null;
      }
      return data;
    },

    async updateProfile() {
      this.message = null;
      if (!(await this.$store.kb.ask({ title: 'Simpan profil?', message: 'Nama dan foto profil Anda akan diperbarui.', confirmText: 'Ya, Simpan' }))) return;
      this.loading = true;
      try {
        const data = await this.patch({ name: this.name, avatar_url: this.avatarUrl }, 'Gagal memperbarui profil');
        if (data?.user) {
          this.$store.kb.setActiveUser(data.user);
          this.message = { type: 'success', text: 'Profil berhasil diperbarui!' };
          this.$store.kb.toast('Profil berhasil diperbarui');
        }
      } catch {
        this.message = { type: 'error', text: 'Terjadi kesalahan sistem' };
      } finally {
        this.loading = false;
      }
    },

    async changePassword() {
      this.message = null;
      if (this.newPassword !== this.confirmPassword) {
        this.message = { type: 'error', text: 'Konfirmasi password baru tidak cocok' };
        return;
      }
      if (!(await this.$store.kb.ask({ title: 'Ubah password?', message: 'Password akun Anda akan diganti.', confirmText: 'Ya, Ubah', danger: true }))) return;
      this.loading = true;
      try {
        const data = await this.patch({ oldPassword: this.oldPassword, newPassword: this.newPassword }, 'Gagal mengubah password');
        if (!data) return;
        this.message = { type: 'success', text: 'Password berhasil diubah!' };
        this.$store.kb.toast('Password berhasil diubah');
        this.oldPassword = this.newPassword = this.confirmPassword = '';
      } catch {
        this.message = { type: 'error', text: 'Terjadi kesalahan sistem' };
      } finally {
        this.loading = false;
      }
    },
  }));
