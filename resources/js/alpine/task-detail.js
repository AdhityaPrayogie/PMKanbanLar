// Port of components/task/TaskDetailSheet.tsx (markup in partials/task-detail-sheet.blade.php)
import { debounce } from '../lib/utils';
import { checkUnitSimilarity, addUnitTag, filterUsers, toggle } from './create-task';
import { api } from '../lib/hd';

const ALLOWED_EXTENSIONS = ['.png', '.jpg', '.jpeg', '.webp', '.gif', '.doc', '.docx', '.pdf', '.md', '.markdown', '.xls', '.xlsx', '.csv', '.zip', '.rar', '.7z', '.tar', '.gz'];

const BADGES = {
  image: { label: 'IMG', color: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border-emerald-300 dark:border-emerald-800' },
  doc: { label: 'DOC', color: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border-blue-300 dark:border-blue-800' },
  pdf: { label: 'PDF', color: 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 border-rose-300 dark:border-rose-800' },
  md: { label: 'MD', color: 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border-purple-300 dark:border-purple-800' },
  excel: { label: 'XLS', color: 'bg-teal-100 text-teal-800 dark:bg-teal-950 dark:text-teal-300 border-teal-300 dark:border-teal-800' },
  zip: { label: 'ZIP', color: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border-amber-300 dark:border-amber-800' },
};
const FILE_BADGE = { label: 'FILE', color: 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border-slate-300' };

const formatFileSize = (b) => (b < 1024 ? `${b} B` : b < 1024 * 1024 ? `${(b / 1024).toFixed(1)} KB` : `${(b / (1024 * 1024)).toFixed(1)} MB`);

const parseTags = (raw) => {
  if (!raw) return [];
  if (Array.isArray(raw)) return raw;
  if (typeof raw === 'string') {
    try {
      const p = JSON.parse(raw);
      if (Array.isArray(p)) return p;
    } catch {
      return raw.split(',').map((s) => s.trim()).filter(Boolean);
    }
  }
  return [];
};

const day = (d) => (d ? d.slice(0, 10) : '');
const sortedJson = (a) => JSON.stringify(a.slice().sort());

// Draft fields derived from a task (load + reset)
const draftsOf = (t) => ({
  draftTitle: t.title || '',
  draftDesc: t.description || '',
  draftCustomer: t.customer || '',
  draftUnitTags: parseTags(t.unit_tags),
  draftAssigneeIds: (t.assignees || []).map((a) => a.id),
  draftColumnId: t.column_id || '',
  draftPriority: t.priority || 'Medium',
  draftStartDate: day(t.start_date),
  draftDueDate: day(t.due_date),
});

const EMPTY = () => ({
  task: null, comments: [], activities: [], attachments: [],
  isConfirmModalOpen: false, uploadError: null, uploadSuccess: null,
});

export default (Alpine) => {
  const kb = () => Alpine.store('kb');

  Alpine.data('taskDetail', () => ({
    ...EMPTY(),
    activeLogTab: 'activities',
    newComment: '', commentImage: null, isUploadingCommentImage: false, isSubmittingComment: false,
    loading: false,
    draftTitle: '', draftDesc: '', draftCustomer: '', draftUnitTags: [], unitInput: '', unitNotice: null,
    assigneeSearch: '', q: '', draftAssigneeIds: [],
    draftColumnId: '', draftPriority: 'Medium', draftStartDate: '', draftDueDate: '',
    isSaving: false, saveSuccessMessage: false,
    isUploading: false,
    formatFileSize,
    badge: (t) => BADGES[t] || FILE_BADGE,

    init() {
      this.$watch('assigneeSearch', debounce((v) => (this.q = v), 200));
      this.$watch('open', () => this.load()); // one watch: openTaskSheet() flips both store fields
    },

    get open() { return kb().isTaskSheetOpen && kb().selectedTaskId; },

    async load() {
      const id = kb().selectedTaskId;
      if (!id || !kb().isTaskSheetOpen) return Object.assign(this, EMPTY());
      this.loading = true;
      try {
        const data = await (await fetch(`/api/tasks/${id}`)).json();
        if (data.task && id === kb().selectedTaskId) {
          const t = data.task;
          Object.assign(this, draftsOf(t), {
            task: t,
            comments: data.comments || [],
            attachments: t.attachments || [],
            activities: data.activities || [],
          });
        }
      } catch (err) {
        console.error('Failed to load task details', err);
      } finally {
        this.loading = false;
      }
    },

    get similar() { return this.unitInput.trim() ? checkUnitSimilarity(this.unitInput, kb().masterUnits) : null; },
    get filteredUsers() { return filterUsers(kb().allUsers, this.q); },
    get chosenUsers() { return this.draftAssigneeIds.map((id) => kb().allUsers.find((u) => u.id === id)).filter(Boolean); },

    get isDirty() {
      const t = this.task;
      return Boolean(t && (
        this.draftTitle !== t.title ||
        this.draftDesc !== (t.description || '') ||
        this.draftCustomer !== (t.customer || '') ||
        sortedJson(this.draftUnitTags) !== sortedJson(parseTags(t.unit_tags)) ||
        sortedJson(this.draftAssigneeIds) !== sortedJson((t.assignees || []).map((a) => a.id)) ||
        this.draftColumnId !== t.column_id ||
        this.draftPriority !== t.priority ||
        this.draftStartDate !== day(t.start_date) ||
        this.draftDueDate !== day(t.due_date)
      ));
    },

    async close() {
      if (this.isDirty && !(await kb().ask({ title: 'Buang perubahan?', message: 'Ada perubahan yang belum disimpan. Yakin ingin keluar?', confirmText: 'Ya, Keluar', danger: true }))) return;
      kb().closeTaskSheet();
    },

    resetDraft() { if (this.task) Object.assign(this, draftsOf(this.task)); },

    addUnitTag(tag) {
      return addUnitTag(this, 'draftUnitTags', tag, `Auto-created from task #${this.task?.id}`, (c) => `Unit baru #${c} otomatis tersimpan ke Master Table Units.`);
    },
    removeUnitTag(tag) { this.draftUnitTags = this.draftUnitTags.filter((t) => t !== tag); },
    toggleAssignee(id) { this.draftAssigneeIds = toggle(this.draftAssigneeIds, id); },

    openConfirmSave() {
      if (!this.isDirty || !this.draftTitle.trim()) return;
      this.isConfirmModalOpen = true;
    },

    async confirmSave() {
      const task = this.task;
      if (!task) return;
      this.isSaving = true;
      const fields = {
        title: this.draftTitle.trim(),
        description: this.draftDesc.trim() || null,
        customer: this.draftCustomer.trim() || null,
        unit_tags: this.draftUnitTags,
        column_id: this.draftColumnId,
        priority: this.draftPriority,
        start_date: this.draftStartDate || null,
        due_date: this.draftDueDate || null,
      };
      try {
        const res = await fetch('/api/tasks', {
          method: 'PATCH',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: task.id, ...fields, assignee_ids: this.draftAssigneeIds }),
        });
        if (!res.ok) return kb().toast('Gagal menyimpan perubahan tugas', 'error');
        this.task = { ...task, ...fields, assignees: kb().allUsers.filter((u) => this.draftAssigneeIds.includes(u.id)) };
        this.isConfirmModalOpen = false;
        this.saveSuccessMessage = true;
        setTimeout(() => (this.saveSuccessMessage = false), 3000);
        kb().toast('Perubahan tugas berhasil disimpan');
        await kb().fetchBoardData();
        try {
          const d = await (await fetch(`/api/tasks/${task.id}`)).json();
          if (d.activities) this.activities = d.activities;
        } catch (e) {
          console.error('Failed to reload activities', e);
        }
      } catch (err) {
        console.error('Failed to update task', err);
        kb().toast('Terjadi kesalahan saat menyimpan perubahan', 'error');
      } finally {
        this.isSaving = false;
      }
    },

    async deleteTask() {
      if (!this.task) return;
      await kb().act({ title: 'Hapus tugas?', message: `Tugas "${this.task.title}" akan dihapus permanen.`, confirmText: 'Ya, Hapus', danger: true },
        'Tugas berhasil dihapus', async () => {
          await api(`/api/tasks?id=${this.task.id}`, { method: 'DELETE' });
          kb().closeTaskSheet();
          kb().fetchBoardData();
        });
    },

    async uploadFile(e) {
      const file = e.target.files?.[0];
      const id = kb().selectedTaskId;
      if (!file || !id) return;
      this.uploadError = null;
      this.uploadSuccess = null;
      const reset = () => (this.$refs.fileInput.value = '');

      const ext = '.' + (file.name.toLowerCase().split('.').pop() || '');
      if (!ALLOWED_EXTENSIONS.includes(ext)) {
        this.uploadError = 'Tipe berkas tidak didukung! Hanya diperbolehkan upload Gambar (IMG: .png, .jpg, .webp, .gif), Dokumen (.doc, .docx), PDF (.pdf), Markdown (.md), Excel (.xls, .xlsx, .csv), dan Arsip (.zip, .rar, .7z).';
        return reset();
      }
      if (file.size > 50 * 1024 * 1024) {
        this.uploadError = 'Ukuran berkas melebihi batas 50 MB.';
        return reset();
      }

      if (!(await kb().ask({ title: 'Unggah lampiran?', message: `Berkas "${file.name}" (${formatFileSize(file.size)}) akan diunggah ke tugas ini.`, confirmText: 'Ya, Unggah' }))) return reset();
      const fd = new FormData();
      fd.append('file', file);
      this.isUploading = true;
      try {
        const res = await fetch(`/api/tasks/${id}/attachments`, { method: 'POST', body: fd });
        const data = await res.json();
        if (!res.ok || data.error) {
          this.uploadError = data.error || 'Gagal mengunggah berkas.';
          kb().toast(this.uploadError, 'error');
        } else if (data.attachment) {
          this.attachments = [data.attachment, ...this.attachments];
          const c = data.compression;
          this.uploadSuccess = c?.wasCompressed
            ? `Berkas "${file.name}" berhasil diunggah & dikompresi (${formatFileSize(c.originalSize)} → ${formatFileSize(c.finalSize)}, hemat ${c.savedPercent}%).`
            : `Berkas "${file.name}" berhasil diunggah (${formatFileSize(file.size)}).`;
          setTimeout(() => (this.uploadSuccess = null), 4000);
          kb().toast(`Berkas "${file.name}" berhasil diunggah`);
        }
      } catch (err) {
        this.uploadError = err.message || 'Terjadi kesalahan saat upload.';
        kb().toast(this.uploadError, 'error');
      } finally {
        this.isUploading = false;
        reset();
      }
    },

    async deleteAttachment(attId) {
      const id = kb().selectedTaskId;
      if (!id) return;
      await kb().act({ title: 'Hapus lampiran?', message: 'Berkas lampiran ini akan dihapus permanen.', confirmText: 'Ya, Hapus', danger: true },
        'Lampiran berhasil dihapus', async () => {
          await api(`/api/tasks/${id}/attachments?attachmentId=${attId}`, { method: 'DELETE' });
          this.attachments = this.attachments.filter((a) => a.id !== attId);
        });
    },

    selectCommentImage(e) {
      const file = e.target.files?.[0];
      if (!file) return;
      if (!file.type.startsWith('image/')) {
        kb().toast('Hanya berkas gambar (PNG, JPG, WEBP, GIF, SVG) yang diperbolehkan di diskusi!', 'error');
        this.$refs.commentImage.value = '';
        return;
      }
      this.commentImage = { file, preview: URL.createObjectURL(file) };
    },

    clearCommentImage() {
      this.commentImage = null;
      this.$refs.commentImage.value = '';
    },

    async addComment() {
      if ((!this.newComment.trim() && !this.commentImage) || !this.task || !kb().activeUser || this.isSubmittingComment) return;
      this.isSubmittingComment = true;
      try {
        let imageUrl = null;
        if (this.commentImage) {
          this.isUploadingCommentImage = true;
          const fd = new FormData();
          fd.append('file', this.commentImage.file);
          const up = await fetch('/api/upload/image', { method: 'POST', body: fd });
          const upData = await up.json();
          if (!up.ok || upData.error) return kb().toast(upData.error || 'Gagal mengunggah gambar', 'error');
          imageUrl = upData.url;
        }
        const data = await (await fetch(`/api/tasks/${this.task.id}/comments`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ content: this.newComment.trim(), imageUrl }),
        })).json();
        if (data.comment) {
          this.comments = [...this.comments, data.comment];
          this.newComment = '';
          this.clearCommentImage();
          kb().fetchBoardData();
          kb().toast('Komentar terkirim');
        } else kb().toast(data.error || 'Gagal mengirim komentar', 'error');
      } catch (err) {
        console.error('Failed to add comment', err);
        kb().toast('Gagal mengirim komentar', 'error');
      } finally {
        this.isSubmittingComment = false;
        this.isUploadingCommentImage = false;
      }
    },

    actKind(a) {
      return { create_task: 'create', create_board: 'board', move_task: 'move', upload_attachment: 'upload', delete_attachment: 'delete' }[a.action] || 'other';
    },
    actTitle(a) {
      const k = this.actKind(a);
      return k === 'create' ? `Dibuat oleh ${a.user?.name || 'Admin'}`
        : k === 'board' ? `Papan Kerja Dibuat oleh ${a.user?.name || 'Admin'}`
        : `Diubah oleh ${a.user?.name || 'Pengguna'}`;
    },
  }));
};
