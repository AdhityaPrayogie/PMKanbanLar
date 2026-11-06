// Port of components/kanban/KanbanBoard.tsx + BoardColumn.tsx + TaskCard.tsx
// (markup in partials/kanban-board.blade.php + partials/task-card.blade.php). dnd-kit -> SortableJS.
import { makeSortable } from '../lib/sortable';
import { api } from '../lib/hd';

export default (Alpine) => {
  const kb = () => Alpine.store('kb');

  Alpine.data('kanbanBoard', () => ({
    isAddingColumn: false,
    newColTitle: '',
    sortables: [],
    priorityStyles: {
      Low: 'bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700',
      Medium: 'bg-blue-100 dark:bg-blue-950/80 text-[#034694] dark:text-blue-300 border-blue-300 dark:border-blue-800',
      High: 'bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 border-amber-300 dark:border-amber-800',
      Urgent: 'bg-rose-100 dark:bg-rose-950/80 text-rose-800 dark:text-rose-300 border-rose-300 dark:border-rose-800',
    },

    destroy() { this.sortables.forEach((s) => s.destroy()); },

    sort(el) {
      this.sortables.push(makeSortable(el, {
        group: 'tasks',
        delay: 200,
        filter: 'select',
        preventOnFilter: false,
        onMove: (id, to) => {
          const s = kb();
          const task = s.allTasks.find((t) => String(t.id) === id);
          const col = s.columns.find((c) => String(c.id) === to.dataset.col);
          if (!task || !col) return;
          const colTasks = (col.tasks || []).filter((t) => t.id !== task.id);
          s.moveTask(task.id, col.id, (colTasks.length + 1) * 1000);
        },
      }));
    },

    filterTask(task) {
      const s = kb();
      if (s.searchQuery.trim()) {
        const q = s.searchQuery.toLowerCase();
        const hit = task.title.toLowerCase().includes(q)
          || task.description?.toLowerCase().includes(q)
          || task.sub_tasks?.some((st) => st.title.toLowerCase().includes(q));
        if (!hit) return false;
      }
      if (s.priorityFilter !== 'All' && task.priority !== s.priorityFilter) return false;
      if (s.assigneeFilter !== 'All' && !task.assignees?.some((a) => a.id === s.assigneeFilter)) return false;
      return true;
    },
    tasksOf(col) { return (col.tasks || []).filter((t) => this.filterTask(t)); },

    async addColumn() {
      const s = kb();
      const title = this.newColTitle.trim();
      if (!title || !s.currentProject) return;
      await s.act({ title: 'Tambah kolom?', message: `Kolom "${title}" akan ditambahkan ke board.`, confirmText: 'Ya, Tambah' }, `Kolom "${title}" berhasil ditambahkan`, async () => {
        await api('/api/columns', { method: 'POST', body: JSON.stringify({ projectId: s.currentProject.id, title }) });
        this.newColTitle = '';
        this.isAddingColumn = false;
        s.fetchBoardData();
      });
    },

    // --- TaskCard helpers ---
    get unitTag() {
      const n = kb().currentProject?.name;
      if (!n) return 'Umum';
      return ['IT', 'Markom', 'MFK', 'PPSDM'].find((t) => n.includes(t)) || n.split(' ')[0];
    },
    tagsOf(task) {
      const u = task.unit_tags;
      if (Array.isArray(u) && u.length) return u;
      if (typeof u === 'string' && u.trim()) {
        try {
          const p = JSON.parse(u);
          return Array.isArray(p) && p.length ? p : [this.unitTag];
        } catch {
          const p = u.split(',').map((x) => x.trim()).filter(Boolean);
          return p.length ? p : [this.unitTag];
        }
      }
      return [this.unitTag];
    },
    attachCount(task) { return task.attachments_count || task.attachments?.length || 0; },
    async quickMove(task, value, el) {
      const col = kb().columns.find((c) => String(c.id) === value);
      // cancel/failure: put the <select> back on the current column
      if (col && col.id !== task.column_id && !(await kb().moveTask(task.id, col.id, (task.position || 0) + 1000)) && el) el.value = task.column_id;
    },
  }));
};
