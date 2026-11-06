// Port of components/helpdesk/ui.tsx + the client half of lib/helpdesk.ts (the .ts stays for scripts/check_helpdesk.mjs)

export const HELPDESK_UNIT_ID = 'unit_it'; // unit with code "TD"
export const STATUS = ['Open', 'Proses', 'Selesai', 'Batal'];
export const JENIS = ['Pelayanan', 'Non Pelayanan'];
// By unit_id, not unit_name (same rule as App\Support\Auth::canHelpdeskMaster)
export const canManageHelpdeskMaster = (u) => u?.unit_id === HELPDESK_UNIT_ID && u?.role === 'admin';

export async function api(url, init) {
  const res = await fetch(url, {
    ...init,
    headers: init?.body ? { 'Content-Type': 'application/json', ...init.headers } : init?.headers,
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || `HTTP ${res.status}`);
  return data;
}

export const today = () => new Date().toLocaleDateString('en-CA'); // YYYY-MM-DD, local time

export const master = (type, all = false) =>
  api(`/api/helpdesk/master/${type}${all ? '?all=1' : ''}`).then((d) => d.rows).catch(() => []);

const STATUS_STYLE = {
  Open: 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800',
  Proses: 'bg-blue-100 text-blue-800 border-blue-300 dark:bg-blue-950/50 dark:text-blue-300 dark:border-blue-800',
  Selesai: 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800',
  Batal: 'bg-slate-100 text-slate-600 border-slate-300 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-600',
};

const BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

// Spread into each helpdesk Alpine.data so templates can call them directly
export const fmt = {
  rupiah: (n) => `Rp ${Number(n || 0).toLocaleString('id-ID')}`,
  fmtDate: (iso) => {
    const [y, m, d] = String(iso).split('-');
    return `${d}/${m}/${y}`;
  },
  statusCls: (s) => STATUS_STYLE[s] || '',
  ymLabel: (ym) => `${BULAN[Number(ym.slice(5, 7)) - 1]} ${ym.slice(2, 4)}`,
};
