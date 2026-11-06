// Port of components/kanban/BoardsHub.tsx (markup in partials/boards-hub.blade.php). dnd-kit -> SortableJS.
import { makeSortable } from '../lib/sortable';
import { debounce } from '../lib/utils';

const STAGES = ['Planning', 'Active', 'Review', 'Completed'];

export const getTag = (p) => {
  if (p.unit_tag) return p.unit_tag;
  return ['IT', 'Markom', 'MFK', 'PPSDM'].find((t) => p.name.includes(t)) || 'Umum';
};
export const getStage = (p) => (STAGES.includes(p.stage) ? p.stage : 'Active');

export default (Alpine) => {
  const kb = () => Alpine.store('kb');

  Alpine.data('boardsHub', () => ({
    stageColumns: [
      { id: 'Planning', title: 'Planning', dot: 'bg-amber-500' },
      { id: 'Active', title: 'In Progress / Active', dot: 'bg-[#034694] dark:bg-blue-400' },
      { id: 'Review', title: 'Review / Evaluasi', dot: 'bg-purple-500' },
      { id: 'Completed', title: 'Completed', dot: 'bg-emerald-500' },
    ],
    stageBadge: {
      Planning: 'bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300 border-amber-300 dark:border-amber-800',
      Active: 'bg-blue-100 text-[#034694] dark:bg-blue-950/80 dark:text-blue-300 border-blue-300 dark:border-blue-800',
      Review: 'bg-purple-100 text-purple-800 dark:bg-purple-950/80 dark:text-purple-300 border-purple-300 dark:border-purple-800',
      Completed: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border-emerald-300 dark:border-emerald-800',
    },
    localSearch: kb().searchQuery,
    debouncedSearch: kb().searchQuery,
    selectedTag: 'All',
    sortables: [],

    init() {
      this.selectedTag = this.isAdmin ? 'All' : this.userUnit;
      const push = debounce((v) => { this.debouncedSearch = v; kb().setSearchQuery(v); }, 250);
      this.$watch('localSearch', push);
      this.$watch('$store.kb.searchQuery', (v) => { this.localSearch = v; this.debouncedSearch = v; });
    },
    destroy() { this.sortables.forEach((s) => s.destroy()); },

    get isAdmin() { return kb().activeUser?.role === 'admin'; },
    get userUnit() { return (kb().activeUser?.unit_name || 'IT').trim(); },
    get unitTags() { return this.isAdmin ? ['All', 'IT', 'Markom', 'MFK', 'PPSDM'] : [this.userUnit]; },
    get accessible() {
      if (this.isAdmin) return kb().projects;
      const u = this.userUnit.toLowerCase();
      return kb().projects.filter((p) => (p.unit_tag || getTag(p)).trim().toLowerCase() === u || p.name.toLowerCase().includes(u));
    },
    get filtered() {
      const q = (this.debouncedSearch || kb().searchQuery).trim().toLowerCase();
      return this.accessible.filter((p) => {
        if (this.selectedTag !== 'All' && getTag(p) !== this.selectedTag) return false;
        if (!q) return true;
        return p.name.toLowerCase().includes(q) || (p.description || '').toLowerCase().includes(q) || (p.unit_tag || '').toLowerCase().includes(q);
      });
    },
    tagCount(tag) { return this.accessible.filter((p) => tag === 'All' || getTag(p) === tag).length; },
    boardsIn(stage) { return this.filtered.filter((p) => getStage(p) === stage); },
    getTag,
    getStage,

    sort(el) {
      this.sortables.push(makeSortable(el, {
        group: 'boards',
        handle: '[data-handle]',
        delay: 200,
        onMove: (id, to) => {
          const p = kb().projects.find((x) => String(x.id) === id);
          if (p && to.dataset.stage !== getStage(p)) kb().moveBoardStage(p.id, to.dataset.stage);
        },
      }));
    },
    async open(id, view) {
      await kb().selectProject(id);
      kb().setActiveView(view);
    },
    add(stage) { kb().openCreateUnitModal(stage, this.selectedTag !== 'All' ? this.selectedTag : this.userUnit); },
  }));
};
