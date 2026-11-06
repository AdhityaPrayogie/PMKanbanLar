{{-- Port of components/kanban/TaskCard.tsx. Used inside x-for="task in ..." within x-data="kanbanBoard". --}}
<div :data-id="task.id" @click="$store.kb.openTaskSheet(task.id)"
  class="group relative bg-white dark:bg-[#162032] border border-[#cbd5e1] dark:border-[#2b3b55] rounded-lg p-3.5 shadow-xs hover:shadow-md hover:border-[#034694] dark:hover:border-blue-500 transition-all cursor-grab active:cursor-grabbing select-none">
  {{-- Top: unit tags, priority, labels, quick move --}}
  <div class="flex items-center justify-between gap-2 mb-2">
    <div class="flex flex-wrap items-center gap-1.5 overflow-hidden">
      <template x-for="tg in tagsOf(task)" :key="tg">
        <span class="flex items-center gap-1 px-1.5 py-0.5 rounded text-[length:calc(10px+var(--fs-up))] font-bold bg-[#e0edfb] dark:bg-[#1e293b] text-[#034694] dark:text-blue-300 border border-blue-200 dark:border-blue-900/60 shadow-2xs">
          <x-icon name="tag" class="w-2.5 h-2.5 text-[#034694] dark:text-blue-400" />
          <span x-text="'#' + tg"></span>
        </span>
      </template>
      <span class="px-2 py-0.5 rounded text-[length:calc(10px+var(--fs-up))] font-bold border tracking-wide"
        :class="priorityStyles[task.priority] || priorityStyles.Medium" x-text="'Label: ' + task.priority"></span>
      <template x-for="lbl in (task.labels || [])" :key="lbl.id">
        <span class="px-1.5 py-0.5 rounded text-[length:calc(10px+var(--fs-up))] font-bold text-white tracking-wide shadow-2xs"
          :style="{ backgroundColor: lbl.color || '#034694' }" x-text="lbl.name"></span>
      </template>
    </div>

    <div class="flex items-center gap-1" @click.stop>
      <div class="relative group/shift">
        <select aria-label="Pindahkan status tugas" @change="quickMove(task, $event.target.value, $event.target)"
          class="text-[length:calc(10px+var(--fs-up))] font-semibold bg-[#f1f5f9] dark:bg-[#1e2a3f] text-[#475569] dark:text-[#94a3b8] border border-[#cbd5e1] dark:border-[#334460] rounded px-1.5 py-0.5 focus:outline-none focus:border-[#034694] cursor-pointer hover:bg-white dark:hover:bg-[#25354e]">
          <template x-for="c in $store.kb.columns" :key="c.id">
            <option :value="c.id" :selected="c.id === task.column_id" x-text="c.title"></option>
          </template>
        </select>
      </div>
      <div class="text-[#94a3b8] dark:text-[#64748b] hover:text-[#0f172a] dark:hover:text-white p-0.5 rounded cursor-grab" title="Tarik kartu untuk memindahkan kolom">
        <x-icon name="grip-vertical" class="w-3.5 h-3.5" />
      </div>
    </div>
  </div>

  <template x-if="task.customer">
    <div class="flex items-center gap-1.5 text-[length:calc(10px+var(--fs-up))] text-[#034694] dark:text-blue-300 font-bold mb-1.5 truncate bg-blue-50/80 dark:bg-blue-950/40 px-2 py-0.5 rounded border border-blue-200 dark:border-blue-900/40">
      <x-icon name="briefcase" class="w-3 h-3 text-[#034694] dark:text-blue-400 shrink-0" />
      <span class="truncate" x-text="task.customer"></span>
    </div>
  </template>

  <h4 class="text-xs font-bold text-[#0f172a] dark:text-[#f8fafc] leading-snug line-clamp-2 mb-1.5 group-hover:text-[#034694] dark:group-hover:text-blue-400 transition-colors" x-text="task.title"></h4>

  <template x-if="task.description">
    <p class="text-[length:calc(11px+var(--fs-up))] text-[#475569] dark:text-[#94a3b8] line-clamp-2 mb-3 leading-relaxed" x-text="task.description"></p>
  </template>

  <template x-if="(task.sub_tasks?.length || 0) > 0">
    <div class="mb-2.5 flex items-center gap-1.5 text-[length:calc(11px+var(--fs-up))] font-medium text-[#475569] dark:text-[#cbd5e1] bg-[#f1f5f9] dark:bg-[#101726] px-2 py-1 rounded border border-[#cbd5e1]/60 dark:border-[#223147]">
      <x-icon name="check-square" class="w-3.5 h-3.5 text-[#034694] dark:text-blue-400" />
      <span x-text="task.sub_tasks.length + ' sub-task'"></span>
    </div>
  </template>

  {{-- Bottom: due date, comments, attachments, assignees --}}
  <div class="flex items-center justify-between pt-2 border-t border-[#e2e8f0] dark:border-[#223147] mt-2 text-[length:calc(11px+var(--fs-up))] text-[#64748b] dark:text-[#94a3b8]">
    <div class="flex items-center gap-2.5">
      <template x-if="task.due_date">
        <div class="flex items-center gap-1 font-semibold text-[length:calc(10px+var(--fs-up))] px-1.5 py-0.5 rounded border"
          :class="$u.isOverdue(task.due_date) ? 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border-rose-300 dark:border-rose-800' : 'bg-[#f8fafc] dark:bg-[#101726] text-[#475569] dark:text-[#cbd5e1] border-[#cbd5e1] dark:border-[#24334b]'"
          :title="$u.isOverdue(task.due_date) ? 'Tugas terlambat!' : 'Tenggat Waktu'">
          <span x-show="$u.isOverdue(task.due_date)" class="contents"><x-icon name="alert-circle" class="w-3 h-3 text-rose-600 dark:text-rose-400" /></span>
          <span x-show="!$u.isOverdue(task.due_date)" class="contents"><x-icon name="calendar" class="w-3 h-3 text-[#64748b] dark:text-[#94a3b8]" /></span>
          <span x-text="$u.formatDate(task.due_date)"></span>
        </div>
      </template>

      <template x-if="(task.comments_count || 0) > 0">
        <div class="flex items-center gap-1 font-medium text-[#475569] dark:text-[#cbd5e1]" :title="task.comments_count + ' Komentar'">
          <x-icon name="message-square" class="w-3.5 h-3.5 text-[#94a3b8] dark:text-[#64748b]" />
          <span x-text="task.comments_count"></span>
        </div>
      </template>

      <template x-if="attachCount(task) > 0">
        <div class="flex items-center gap-1 font-medium text-[#475569] dark:text-[#cbd5e1]" :title="attachCount(task) + ' Lampiran berkas'">
          <x-icon name="paperclip" class="w-3 h-3 text-[#94a3b8] dark:text-[#64748b]" />
          <span x-text="attachCount(task)"></span>
        </div>
      </template>
    </div>

    <div class="flex items-center -space-x-1.5 overflow-hidden">
      <template x-for="a in (task.assignees || [])" :key="a.id">
        <img :src="a.avatar_url || ''" :alt="a.name" :title="a.name" class="w-5 h-5 rounded-full ring-2 ring-white dark:ring-[#162032] object-cover shadow-2xs" />
      </template>
    </div>
  </div>
</div>
