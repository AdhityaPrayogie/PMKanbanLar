// Port of components/helpdesk/{HelpdeskView,TicketForm,TicketTable,HelpdeskMaster,PengajuanView}.tsx
// Markup in views/partials/helpdesk/*. Children are nested inside helpdeskView, so they read its
// kategori/unitKerja/pic/version and call bump() through Alpine's merged scope.
import { subscribeRealtime } from '../lib/realtime';
import { debounce, formatLastActive } from '../lib/utils';
import { HELPDESK_UNIT_ID, api, canManageHelpdeskMaster, fmt, master, today } from '../lib/hd';

const PAGE = 25;
const pad = (n) => String(n).padStart(2, '0');
const byName = (rows, name) => rows.find((r) => r.nama.toLowerCase() === name.trim().toLowerCase());

export default (Alpine) => {
  const kb = () => Alpine.store('kb');

  Alpine.data('helpdeskView', () => ({
    ...fmt,
    tab: 'dashboard',
    // Bumped on every write (and on incoming notifications) so all lists refetch
    version: 0,
    kategori: [],
    unitKerja: [],
    pic: [],
    stats: null,
    // '' = let the server pick the month of the latest ticket, then adopt it
    period: '',
    queue: [],
    saved: null,
    day: null,
    assigning: null,
    busy: null,
    unsub: null,

    init() {
      this.unsub = subscribeRealtime({ notification: () => this.bump() });
      this.$watch('version', () => this.load(true));
      this.$watch('period', () => this.load());
      this.load(true);
    },
    destroy() { this.unsub?.(); },
    bump() { this.version++; },

    load(masters = false) {
      if (masters) {
        master('kategori').then((r) => (this.kategori = r));
        master('unit-kerja').then((r) => (this.unitKerja = r));
        master('pic').then((r) => (this.pic = r));
      }
      const [year, month] = this.period.split('-');
      api(`/api/helpdesk/stats${this.period ? `?month=${Number(month)}&year=${year}` : ''}`)
        .then((d) => {
          this.stats = d;
          if (!this.period) this.period = `${d.year}-${pad(d.month)}`;
        })
        .catch(() => (this.stats = null));
      api('/api/helpdesk?status=Open&limit=200').then((d) => (this.queue = d.rows)).catch(() => (this.queue = []));
    },

    get isMasterAdmin() { return canManageHelpdeskMaster(kb().activeUser); },
    get tabs() {
      return [
        ['dashboard', 'Dashboard'],
        ['antrian', `Antrian${this.queue.length ? ` (${this.queue.length})` : ''}`],
        ['input', 'Input'],
        ['data', 'Data'],
        ['trend', 'Trend Helpdesk'],
        ...(this.isMasterAdmin ? [['master', 'Master Data']] : []),
      ];
    },

    // Dashboard
    get cards() {
      const c = this.stats.cards;
      return [
        ['Total', c.total], ['Hari Ini', c.hari_ini], ['Antrian (Open)', c.open, 'text-amber-600'],
        ['Proses', c.proses, 'text-blue-600'], ['Selesai', c.selesai, 'text-emerald-600'], ['Batal', c.batal, 'text-slate-500'],
        ['Total Menit', c.total_menit], ['Total Biaya', this.rupiah(c.total_biaya)],
      ];
    },
    get dayCols() {
      const s = this.stats;
      return Array.from({ length: new Date(s.year, s.month, 0).getDate() }, (_, i) => {
        const d = s.perHari.find((x) => x.hari === i + 1);
        return {
          key: `${s.year}-${pad(s.month)}-${pad(i + 1)}`,
          label: String(i + 1),
          value: d?.tiket ?? 0,
          tip: `${i + 1}/${s.month}: ${d?.tiket ?? 0} tiket, ${d?.menit ?? 0} menit`,
        };
      });
    },
    get katBars() {
      const rows = this.stats.perKategori;
      const max = Math.max(1, ...rows.map((k) => k.tiket));
      return rows.map((k) => ({ label: k.nama, n: k.tiket, pct: (k.tiket / max) * 100 }));
    },

    // Trend: 12 months ending at the selected period
    get trendCols() {
      return this.stats.trend.map((t) => ({ key: t.ym, label: this.ymLabel(t.ym), value: t.tiket, tip: `${this.ymLabel(t.ym)}: ${t.tiket} tiket, ${t.menit} menit` }));
    },
    get kat() { return [...new Set(this.stats.trendKategori.map((k) => k.nama))].sort(); },
    cell(ym, nama) { return this.stats.trendKategori.find((k) => k.ym === ym && k.nama === nama)?.tiket ?? 0; },
    sum(k) { return this.stats.trend.reduce((a, t) => a + t[k], 0); },
    katSum(nama) { return this.stats.trend.reduce((a, t) => a + this.cell(t.ym, nama), 0); },

    // Queue
    async ambil(t) {
      if (!(await kb().ask({ title: 'Ambil tiket?', message: `Tiket #${t.id} akan Anda tangani dan berstatus Proses.`, confirmText: 'Ya, Ambil' }))) return;
      this.busy = t.id;
      await kb().act(null, `Tiket #${t.id} berhasil diambil`, () => api(`/api/helpdesk/${t.id}/ambil`, { method: 'POST' }));
      this.busy = null;
      this.bump();
    },
  }));

  // Tickets of one day (clicked column), sorted by start time
  Alpine.data('hdDay', (date) => ({
    rows: null,
    error: null,
    init() {
      api(`/api/helpdesk?start=${date}&end=${date}&limit=200`)
        .then((d) => (this.rows = [...d.rows].sort((a, b) => (a.jam_mulai ?? '').localeCompare(b.jam_mulai ?? ''))))
        .catch((e) => { this.error = e.message; this.rows = []; });
    },
  }));

  // Create (ticket = null) or edit. Unknown PIC names are added to master on save.
  Alpine.data('hdTicketForm', (ticket = null, onSaved = () => {}) => ({
    ticket,
    f: {
      tanggal: ticket?.tanggal ?? today(),
      jam_mulai: ticket?.jam_mulai ?? '',
      jam_selesai: ticket?.jam_selesai ?? '',
      pic: ticket?.pic_nama ?? '',
      kategori_id: String(ticket?.kategori_id ?? ''),
      unit_kerja: ticket?.unit_kerja ?? '',
      isi_helpdesk: ticket?.isi_helpdesk ?? '',
      tindakan: ticket?.tindakan ?? '',
      status: ticket?.status ?? 'Proses',
      jenis_pelayanan: ticket?.jenis_pelayanan ?? 'Pelayanan',
      staff_ids: [...(ticket?.staff_ids ?? (kb().activeUser ? [kb().activeUser.id] : []))],
    },
    // Inputs keep strings while typing; converted to numbers on submit
    biaya: (ticket?.biaya ?? []).map((b) => ({ nama_barang: b.nama_barang, qty: String(b.qty), harga: String(b.harga) })),
    error: null,
    saving: false,
    uid: ticket ? `e${ticket.id}` : 'new',
    fromPengajuan: !!ticket?.requested_by,

    get tdStaff() { return kb().allUsers.filter((u) => u.unit_id === HELPDESK_UNIT_ID && u.status === 'active'); },
    get totalRp() { return this.biaya.reduce((a, b) => a + (Number(b.qty) || 0) * (Number(b.harga) || 0), 0); },
    // Preview only; the DB computes the stored total_menit (generated column) the same way
    get totalMenit() {
      const toMin = (t) => Number(t.slice(0, 2)) * 60 + Number(t.slice(3, 5));
      return this.f.jam_mulai && this.f.jam_selesai ? toMin(this.f.jam_selesai) - toMin(this.f.jam_mulai) : null;
    },
    get picKnown() { return !this.f.pic.trim() || !!byName(this.pic, this.f.pic); },
    isOnline(u) { return formatLastActive(u.last_active_at).isOnline; },

    async submit() {
      const f = this.f;
      this.error = null;
      const uk = f.unit_kerja.trim() ? byName(this.unitKerja, f.unit_kerja) : null;
      if (uk === undefined) return (this.error = 'Unit kerja tidak ditemukan, pilih dari daftar');
      if (!this.fromPengajuan && !f.pic.trim()) return (this.error = 'PIC (peminta) wajib diisi');
      if (!(await kb().ask(ticket ? { title: 'Simpan perubahan tiket?', message: `Perubahan tiket #${ticket.id} akan disimpan.`, confirmText: 'Ya, Simpan' }
        : { title: 'Simpan tiket baru?', message: 'Tiket helpdesk baru akan disimpan.', confirmText: 'Ya, Simpan' }))) return;
      this.saving = true;
      try {
        let pic_id = null;
        if (f.pic.trim()) {
          pic_id = byName(this.pic, f.pic)?.id
            ?? (await api('/api/helpdesk/master/pic', { method: 'POST', body: JSON.stringify({ nama: f.pic }) })).row.id;
        }
        const body = JSON.stringify({
          tanggal: f.tanggal, jam_mulai: f.jam_mulai || null, jam_selesai: f.jam_selesai || null, pic_id,
          kategori_id: Number(f.kategori_id), unit_kerja_id: uk?.id ?? null, isi_helpdesk: f.isi_helpdesk,
          tindakan: f.tindakan, status: f.status, jenis_pelayanan: f.jenis_pelayanan, staff_ids: [...f.staff_ids],
          biaya: this.biaya.map((b) => ({ nama_barang: b.nama_barang, qty: Number(b.qty), harga: Number(b.harga) })),
        });
        const { ticket: saved } = await api(ticket ? `/api/helpdesk/${ticket.id}` : '/api/helpdesk', {
          method: ticket ? 'PUT' : 'POST', body,
        });
        onSaved(saved);
        kb().toast(`Tiket #${saved.id} berhasil disimpan`);
      } catch (err) {
        this.error = err.message;
        kb().toast(err.message, 'error');
      } finally {
        this.saving = false;
      }
    },
  }));

  Alpine.data('hdTicketTable', () => ({
    PAGE,
    // No default date range: show all tickets (older ones too); export asks for dates
    filter: { start: '', end: '', status: '', kategori_id: '', staff_id: '', sumber: '', q: '' },
    qIn: '',
    offset: 0,
    data: null,
    error: null,
    editing: null,
    historyOf: null,

    init() {
      this.$watch('qIn', debounce((v) => (this.filter.q = v), 300));
      // Any filter change goes back to page 1 (the offset watcher then reloads)
      this.$watch('filter', () => (this.offset ? (this.offset = 0) : this.load()));
      this.$watch('offset', () => this.load());
      this.$watch('version', () => this.load());
      this.load();
    },
    load() {
      const p = new URLSearchParams();
      Object.entries(this.filter).forEach(([k, v]) => v && p.set(k, v));
      p.set('limit', String(PAGE));
      p.set('offset', String(this.offset));
      api(`/api/helpdesk?${p}`).then((d) => (this.data = d)).catch((e) => (this.error = e.message));
    },
    get tdStaff() { return kb().allUsers.filter((u) => u.unit_id === HELPDESK_UNIT_ID); },
    canDelete(t) { return t.created_by === kb().activeUser?.id || canManageHelpdeskMaster(kb().activeUser); },
    biayaTitle(t) { return t.biaya.map((b) => `${b.nama_barang} ×${b.qty}`).join(', '); },

    async remove(t) {
      if (await kb().act({ title: `Hapus tiket #${t.id}?`, message: 'Riwayatnya ikut terhapus dan tidak bisa dikembalikan.', confirmText: 'Ya, Hapus', danger: true },
        `Tiket #${t.id} berhasil dihapus`, () => api(`/api/helpdesk/${t.id}`, { method: 'DELETE' }))) this.bump();
    },
    async exportXlsx() {
      if (!this.filter.start || !this.filter.end) return kb().toast('Isi tanggal mulai dan akhir untuk export', 'error');
      if (!(await kb().ask({ title: 'Export ke Excel?', message: `Data tiket ${this.filter.start} s/d ${this.filter.end} akan diunduh.`, confirmText: 'Ya, Export' }))) return;
      kb().toast('Export dimulai, file akan segera terunduh');
      const p = new URLSearchParams({ start: this.filter.start, end: this.filter.end });
      if (this.filter.kategori_id) p.set('kategori_ids', this.filter.kategori_id);
      window.location.href = `/api/helpdesk/export?${p}`;
    },
  }));

  Alpine.data('hdHistory', (id) => ({
    rows: null,
    init() {
      api(`/api/helpdesk/${id}`)
        .then((d) => (this.rows = d.history.map((h) => ({ ...h, changes: typeof h.changes === 'string' ? JSON.parse(h.changes) : h.changes }))))
        .catch(() => (this.rows = []));
    },
    when(iso) { return new Date(iso).toLocaleString('id-ID'); },
  }));

  // Admin TD only (tab hidden otherwise; PATCH/POST are also guarded server-side)
  Alpine.data('hdMaster', () => ({
    TYPES: [['kategori', 'Kategori'], ['unit-kerja', 'Unit Kerja'], ['pic', 'PIC']],
    type: 'kategori',
    rows: [],
    nama: '',
    q: '',
    error: null,

    init() {
      this.$watch('type', () => this.load());
      this.load();
    },
    load() { master(this.type, true).then((r) => (this.rows = r)); },
    get shown() { return this.rows.filter((r) => r.nama.toLowerCase().includes(this.q.toLowerCase())); },
    async run(opts, okMsg, fn) {
      this.error = null;
      if (await kb().act(opts, okMsg, fn)) {
        this.load();
        this.bump();
      }
    },
    url() { return `/api/helpdesk/master/${this.type}`; },
    add() {
      const nama = this.nama.trim();
      this.run({ title: 'Tambah data master?', message: `"${nama}" akan ditambahkan.`, confirmText: 'Ya, Tambah' }, `"${nama}" berhasil ditambahkan`,
        () => api(this.url(), { method: 'POST', body: JSON.stringify({ nama }) }).then(() => (this.nama = '')));
    },
    async rename(r) {
      const n = await kb().ask({ title: 'Ubah nama', message: `Nama baru untuk "${r.nama}":`, confirmText: 'Simpan', input: r.nama });
      if (n && n.trim() && n.trim() !== r.nama) this.run(null, `Nama diubah menjadi "${n.trim()}"`, () => api(this.url(), { method: 'PATCH', body: JSON.stringify({ id: r.id, nama: n.trim() }) }));
    },
    toggle(r) {
      const verb = r.aktif ? 'Nonaktifkan' : 'Aktifkan';
      this.run({ title: `${verb} "${r.nama}"?`, message: r.aktif ? 'Data tidak akan muncul di pilihan form.' : 'Data akan kembali muncul di pilihan form.', confirmText: `Ya, ${verb}`, danger: r.aktif },
        `"${r.nama}" berhasil di${verb.toLowerCase()}`, () => api(this.url(), { method: 'PATCH', body: JSON.stringify({ id: r.id, aktif: !r.aktif }) }));
    },
  }));

  Alpine.data('pengajuanView', () => ({
    ...fmt,
    kategori: [],
    unitKerja: [],
    tickets: null,
    form: { kategori_id: '', unit_kerja: '', isi_helpdesk: '' },
    error: null,
    notice: null,
    saving: false,
    unsub: null,

    init() {
      master('kategori').then((r) => (this.kategori = r));
      master('unit-kerja').then((r) => (this.unitKerja = r));
      this.load();
      // Status changes by TD arrive as notifications for the requester
      this.unsub = subscribeRealtime({ notification: () => this.load() });
    },
    destroy() { this.unsub?.(); },
    load() {
      api('/api/helpdesk/pengajuan').then((d) => (this.tickets = d.tickets)).catch((e) => (this.error = e.message));
    },
    async submit() {
      this.error = null;
      this.notice = null;
      const uk = byName(this.unitKerja, this.form.unit_kerja);
      if (!uk) return (this.error = 'Lokasi / unit kerja tidak ditemukan, pilih dari daftar');
      if (!(await kb().ask({ title: 'Kirim pengajuan?', message: 'Pengajuan helpdesk akan dikirim ke tim TD.', confirmText: 'Ya, Kirim' }))) return;
      this.saving = true;
      try {
        const { id } = await api('/api/helpdesk/pengajuan', {
          method: 'POST',
          body: JSON.stringify({ kategori_id: Number(this.form.kategori_id), unit_kerja_id: uk.id, isi_helpdesk: this.form.isi_helpdesk }),
        });
        this.form = { kategori_id: '', unit_kerja: '', isi_helpdesk: '' };
        this.notice = `Pengajuan #${id} terkirim. Tim TD akan segera menindaklanjuti.`;
        kb().toast(`Pengajuan #${id} berhasil dikirim`);
        this.load();
      } catch (err) {
        this.error = err.message;
        kb().toast(err.message, 'error');
      } finally {
        this.saving = false;
      }
    },
    async remove(t) {
      if (await kb().act({ title: `Hapus pengajuan #${t.id}?`, message: 'Pengajuan yang dihapus tidak bisa dikembalikan.', confirmText: 'Ya, Hapus', danger: true },
        `Pengajuan #${t.id} berhasil dihapus`, () => api(`/api/helpdesk/pengajuan?id=${t.id}`, { method: 'DELETE' }))) this.load();
    },
  }));
};
