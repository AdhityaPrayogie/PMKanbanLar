// Port of components/unit/MasterUnitModal.tsx
const debounced = (ctx, src, dst) => {
  let t;
  ctx.$watch(src, (v) => {
    clearTimeout(t);
    t = setTimeout(() => (ctx[dst] = v), 250);
  });
};

export default (Alpine) =>
  Alpine.data('masterUnit', () => ({
    name: '',
    code: '',
    description: '',
    isSubmitting: false,
    selectedUnitCode: 'All',
    searchUnit: '',
    searchUser: '',
    qUnit: '',
    qUser: '',
    editingUnit: null,
    editName: '',
    editCode: '',
    editDescription: '',
    isEditingSubmitting: false,
    editError: null,

    init() {
      debounced(this, 'searchUnit', 'qUnit');
      debounced(this, 'searchUser', 'qUser');
    },

    get filteredUnits() {
      const q = this.qUnit.trim().toLowerCase();
      return this.$store.kb.masterUnits.filter(
        (u) => !q || u.name.toLowerCase().includes(q) || u.code.toLowerCase().includes(q) || (u.description || '').toLowerCase().includes(q)
      );
    },

    get filteredUsers() {
      const q = this.qUser.trim().toLowerCase();
      return this.$store.kb.allUsers.filter((u) => {
        if (this.selectedUnitCode !== 'All' && u.unit_name !== this.selectedUnitCode) return false;
        if (!q) return true;
        return [u.name, u.email, u.role, u.unit_name].some((s) => (s || '').toLowerCase().includes(q));
      });
    },

    async submit() {
      if (!this.name.trim() || !this.code.trim()) return;
      this.isSubmitting = true;
      const ok = await this.$store.kb.createMasterUnit(this.name.trim(), this.code.trim(), this.description.trim());
      this.isSubmitting = false;
      if (ok) this.name = this.code = this.description = '';
    },

    openEdit(unit) {
      this.editingUnit = unit;
      this.editName = unit.name;
      this.editCode = unit.code;
      this.editDescription = unit.description || '';
      this.editError = null;
    },

    async submitEdit() {
      if (!this.editingUnit || !this.editName.trim() || !this.editCode.trim()) return;
      this.isEditingSubmitting = true;
      this.editError = null;
      const prevCode = this.editingUnit.code;
      const newCode = this.editCode.trim().toUpperCase();
      const res = await this.$store.kb.updateMasterUnit(this.editingUnit.id, this.editName.trim(), newCode, this.editDescription.trim());
      this.isEditingSubmitting = false;
      if (res.cancelled) return;
      if (res.success) {
        if (this.selectedUnitCode === prevCode) this.selectedUnitCode = newCode;
        this.editingUnit = null;
      } else {
        this.editError = res.error || 'Gagal mengubah unit';
      }
    },

    async userUnitChange(userId, code, el) {
      const unit = this.$store.kb.masterUnits.find((u) => u.code === code);
      const kb = this.$store.kb;
      // cancel/failure: put the <select> back on the stored unit
      if (unit && !(await kb.assignUserUnit(userId, unit.id, unit.code)) && el) el.value = kb.allUsers.find((u) => u.id === userId)?.unit_name || 'IT';
    },
  }));
