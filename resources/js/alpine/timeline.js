// Port of components/timeline/TimelineView.tsx (markup in partials/timeline.blade.php)
export default (Alpine) => {
  const kb = () => Alpine.store('kb');
  const TODAY = () => new Date(2026, 9, 8); // original pins "today" to 8 Oct 2026

  // Parse YYYY-MM-DD as a local date (no timezone jump)
  const parseLocal = (s) => {
    if (!s) return null;
    const p = (s.includes('T') ? s.split('T')[0] : s).split('-');
    return p.length === 3 ? new Date(+p[0], +p[1] - 1, +p[2]) : new Date(s);
  };

  Alpine.data('timeline', () => ({
    expanded: { task1: true, task2: true, task3: true, it_task_1: true, markom_task_1: true, mfk_task_1: true },
    viewDate: TODAY(),
    priorityColors: {
      Low: 'bg-slate-600 hover:bg-slate-700',
      Medium: 'bg-[#034694] hover:bg-[#023470] dark:bg-blue-600',
      High: 'bg-amber-600 hover:bg-amber-700',
      Urgent: 'bg-rose-600 hover:bg-rose-700',
    },

    init() {
      // Auto-expand parents whose sub-tasks match the search
      const autoExpand = () => {
        const q = kb().searchQuery.trim().toLowerCase();
        if (!q) return;
        for (const t of kb().allTasks) {
          if (!t.parent_id && t.sub_tasks?.some((st) => st.title.toLowerCase().includes(q))) this.expanded[t.id] = true;
        }
      };
      autoExpand();
      this.$watch('$store.kb.searchQuery', autoExpand);
      this.$watch('$store.kb.allTasks', autoExpand);
    },

    get year() { return this.viewDate.getFullYear(); },
    get month() { return this.viewDate.getMonth(); },
    get totalDays() { return new Date(this.year, this.month + 1, 0).getDate(); },
    get monthLabel() { return this.viewDate.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' }); },
    get gridCols() { return `grid-template-columns: repeat(${this.totalDays}, minmax(36px, 1fr))`; },
    get minWidth() { return `min-width: ${Math.max(1600, this.totalDays * 44 + 480)}px`; },
    get days() {
      const t = TODAY();
      return Array.from({ length: this.totalDays }, (_, i) => {
        const d = new Date(this.year, this.month, i + 1);
        const dow = d.getDay();
        return {
          dayNum: i + 1,
          label: d.toLocaleDateString('id-ID', { weekday: 'short' }),
          isWeekend: dow === 0 || dow === 6,
          isToday: i + 1 === t.getDate() && this.month === t.getMonth() && this.year === t.getFullYear(),
        };
      });
    },
    get columnMap() { return new Map(kb().columns.map((c) => [c.id, c.title])); },
    get parentTasks() {
      const s = kb();
      const q = s.searchQuery.trim().toLowerCase();
      return s.allTasks.filter((t) => {
        if (t.parent_id) return false;
        if (q) {
          const hit = t.title.toLowerCase().includes(q)
            || (t.description || '').toLowerCase().includes(q)
            || (t.customer || '').toLowerCase().includes(q)
            || t.sub_tasks?.some((st) => st.title.toLowerCase().includes(q) || (st.description || '').toLowerCase().includes(q));
          if (!hit) return false;
        }
        return s.priorityFilter === 'All' || t.priority === s.priorityFilter;
      });
    },

    prevMonth() { this.viewDate = new Date(this.year, this.month - 1, 1); },
    nextMonth() { this.viewDate = new Date(this.year, this.month + 1, 1); },
    thisMonth() { this.viewDate = TODAY(); },
    toggle(id) { this.expanded[id] = !this.expanded[id]; },
    status(t) { return this.columnMap.get(t.column_id) || 'To Do'; },

    duration(startStr, dueStr) {
      if (!startStr || !dueStr) return null;
      const s = parseLocal(startStr), e = parseLocal(dueStr);
      if (!s || !e) return null;
      const diff = Math.round((e - s) / 86400000) + 1;
      return diff > 0 ? diff : 1;
    },
    bar(startStr, dueStr) {
      const s = parseLocal(startStr), e = parseLocal(dueStr);
      if (!s || !e) return { style: 'left:10%;width:18%', isVisible: true, startsBefore: false, endsAfter: false };
      const n = this.totalDays;
      const monthStart = new Date(this.year, this.month, 1, 0, 0, 0);
      const monthEnd = new Date(this.year, this.month, n, 23, 59, 59);
      if (e < monthStart || s > monthEnd) return { style: '', isVisible: false, startsBefore: false, endsAfter: false };
      const startsBefore = s < monthStart, endsAfter = e > monthEnd;
      const startDay = startsBefore ? 1 : s.getDate();
      const endDay = endsAfter ? n : e.getDate();
      const span = Math.max(startDay, endDay) - startDay + 1;
      const left = ((startDay - 1) / n) * 100;
      const width = Math.max((span / n) * 100, (1 / n) * 100);
      return { style: `left:${left.toFixed(2)}%;width:${width.toFixed(2)}%`, isVisible: true, startsBefore, endsAfter };
    },
  }));
};
