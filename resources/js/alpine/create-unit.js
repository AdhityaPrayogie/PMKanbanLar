// Port of components/unit/CreateUnitModal.tsx
export default (Alpine) =>
  Alpine.data('createUnit', () => ({
    name: '',
    description: '',
    unitTag: 'IT',
    stage: 'Active',
    submitting: false,

    init() {
      // Re-apply defaults each time the modal opens (callers pass stage/tag)
      this.$watch('$store.kb.isCreateUnitModalOpen', (open) => {
        if (!open) return;
        this.unitTag = this.$store.kb.createUnitModalDefaultTag || 'IT';
        this.stage = this.$store.kb.createUnitModalDefaultStage || 'Active';
        this.$nextTick(() => this.$refs.name.focus());
      });
    },

    async submit() {
      if (!this.name.trim()) return;
      this.submitting = true;
      const ok = await this.$store.kb.createBoard(this.name.trim(), this.description.trim(), this.unitTag, this.stage);
      this.submitting = false;
      if (ok) {
        this.name = '';
        this.description = '';
        this.$store.kb.closeCreateUnitModal();
      }
    },
  }));
