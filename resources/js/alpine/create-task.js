// Port of components/task/CreateTaskModal.tsx (markup in partials/create-task-modal.blade.php)
import { debounce } from '../lib/utils';
import { api } from '../lib/hd';

// Port of lib/unit-similarity.ts (also used by task-detail.js)
export function checkUnitSimilarity(input, masterUnits) {
  const clean = input.trim().toUpperCase().replace(/^#/, '');
  if (!clean || clean.length < 2) return null;
  const cleanLower = clean.toLowerCase();

  const exact = masterUnits.find((u) => u.code.toUpperCase() === clean || u.name.trim().toLowerCase() === cleanLower);
  if (exact) return { unit: exact, matchType: 'exact', message: `Unit "#${exact.code}" (${exact.name}) sudah ada di database.` };

  const sub = masterUnits.find((u) => {
    const c = u.code.toLowerCase();
    const n = u.name.toLowerCase();
    return c.includes(cleanLower) || cleanLower.includes(c) || n.includes(cleanLower) || cleanLower.includes(n);
  });
  if (sub) return { unit: sub, matchType: 'similar', message: `Ditemukan unit yang mirip: "#${sub.code}" (${sub.name}).` };

  for (const u of masterUnits) {
    const c = u.code.toLowerCase();
    if (Math.abs(c.length - cleanLower.length) <= 2) {
      let diff = 0;
      for (let i = 0; i < Math.min(c.length, cleanLower.length); i++) if (c[i] !== cleanLower[i]) diff++;
      diff += Math.abs(c.length - cleanLower.length);
      if (diff <= 2) return { unit: u, matchType: 'similar', message: `Mungkin maksud Anda unit: "#${u.code}" (${u.name})?` };
    }
  }
  return null;
}

// Shared by create-task + task-detail: adds a unit tag, auto-saving unknown units to the master table.
export async function addUnitTag(ctx, tags, tagToAdd, description, noticeText) {
  const clean = (tagToAdd || ctx.unitInput).trim().toUpperCase().replace(/^#/, '');
  if (!clean) return;
  const kb = window.Alpine.store('kb');
  const exists = kb.masterUnits.find((u) => u.code.toUpperCase() === clean || u.name.toUpperCase() === clean);
  if (!exists) {
    try {
      const res = await fetch('/api/master-units', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ code: clean, name: clean, description }),
      });
      if (res.ok) {
        await kb.fetchMasterUnits();
        ctx.unitNotice = noticeText(clean);
        setTimeout(() => (ctx.unitNotice = null), 4000);
      }
    } catch (err) {
      console.error('Failed to auto-save unit to master table', err);
    }
  }
  if (!ctx[tags].includes(clean)) ctx[tags] = [...ctx[tags], clean];
  ctx.unitInput = '';
}

export const filterUsers = (users, q) => {
  q = q.trim().toLowerCase();
  if (!q) return users;
  return users.filter((u) => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q) || (u.unit_name || '').toLowerCase().includes(q));
};

export const toggle = (arr, id) => (arr.includes(id) ? arr.filter((x) => x !== id) : [...arr, id]);

export default (Alpine) => {
  const kb = () => Alpine.store('kb');

  Alpine.data('createTask', () => ({
    title: '', description: '', customer: '', unitInput: '', selectedUnitTags: ['IT'], unitNotice: null,
    priority: 'Medium', columnId: '', startDate: '', dueDate: '',
    assigneeSearch: '', q: '', selectedAssignees: [], selectedLabels: [], isSubmitting: false,

    init() {
      const setQ = debounce((v) => (this.q = v), 200);
      this.$watch('assigneeSearch', setQ);
      // React kept state across opens (component stayed mounted); a fresh form per open is the intended UX.
      this.$watch('$store.kb.isCreateModalOpen', (open) => {
        if (!open) return;
        Object.assign(this, {
          title: '', description: '', customer: '', unitInput: '', selectedUnitTags: ['IT'], unitNotice: null,
          priority: 'Medium', columnId: kb().createModalColumnId || kb().columns[0]?.id || '', startDate: '', dueDate: '',
          assigneeSearch: '', q: '', selectedAssignees: [], selectedLabels: [], isSubmitting: false,
        });
      });
    },

    get isSubTask() { return Boolean(kb().createModalParentId); },
    get similar() { return this.unitInput.trim() ? checkUnitSimilarity(this.unitInput, kb().masterUnits) : null; },
    get filteredUsers() { return filterUsers(kb().allUsers, this.q); },
    get chosenUsers() { return this.selectedAssignees.map((id) => kb().allUsers.find((u) => u.id === id)).filter(Boolean); },

    addUnitTag(tag) {
      return addUnitTag(this, 'selectedUnitTags', tag, 'Auto-created from task creation', (c) => `Unit baru #${c} berhasil disimpan ke Master Table Units.`);
    },
    removeUnitTag(tag) { this.selectedUnitTags = this.selectedUnitTags.filter((t) => t !== tag); },
    toggleAssignee(id) { this.selectedAssignees = toggle(this.selectedAssignees, id); },
    toggleLabel(id) { this.selectedLabels = toggle(this.selectedLabels, id); },

    async submit() {
      const s = kb();
      if (!this.title.trim() || !s.currentProject || !s.activeUser || this.isSubmitting) return;
      const what = this.isSubTask ? 'sub-task' : 'tugas';
      if (!(await s.ask({ title: `Buat ${what}?`, message: `"${this.title.trim()}" akan ditambahkan ke ${s.currentProject.name}.`, confirmText: 'Ya, Buat' }))) return;
      this.isSubmitting = true;
      try {
        await api('/api/tasks', {
          method: 'POST',
          body: JSON.stringify({
            projectId: s.currentProject.id,
            columnId: this.columnId || s.columns[0]?.id,
            parentId: s.createModalParentId || null,
            title: this.title.trim(),
            description: this.description.trim(),
            customer: this.customer.trim() || null,
            unit_tags: this.selectedUnitTags,
            priority: this.priority,
            startDate: this.startDate || null,
            dueDate: this.dueDate || null,
            assigneeIds: this.selectedAssignees,
            labelIds: this.selectedLabels,
          }),
        });
        s.toast(`${what[0].toUpperCase() + what.slice(1)} "${this.title.trim()}" berhasil dibuat`);
        s.closeCreateModal();
        s.fetchBoardData();
      } catch (err) {
        s.toast(err.message || 'Gagal membuat tugas', 'error');
      } finally {
        this.isSubmitting = false;
      }
    },
  }));
};
