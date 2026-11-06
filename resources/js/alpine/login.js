// Port of pages/Login.tsx
export default (Alpine) =>
  Alpine.data('loginPage', () => ({
    tab: 'login',
    email: '',
    password: '',
    name: '',
    errorMsg: '',
    successMsg: '',
    loading: false,

    async post(url, body, fallback) {
      this.errorMsg = '';
      this.successMsg = '';
      this.loading = true;
      try {
        const res = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
        const data = await res.json();
        if (!res.ok) {
          this.errorMsg = data.error || fallback;
          return null;
        }
        return data;
      } catch {
        this.errorMsg = 'Terjadi kesalahan jaringan atau server';
        return null;
      } finally {
        this.loading = false;
      }
    },

    async login() {
      const data = await this.post('/api/auth/login', { email: this.email, password: this.password }, 'Login gagal');
      if (data?.user) location.href = '/';
    },

    async register() {
      const data = await this.post('/api/auth/register', { name: this.name, email: this.email, password: this.password }, 'Pendaftaran gagal');
      if (!data) return;
      this.successMsg = data.message;
      this.name = this.email = this.password = '';
      this.tab = 'login';
    },

    fill(email, password) {
      this.email = email;
      this.password = password;
      this.errorMsg = '';
    },
  }));
