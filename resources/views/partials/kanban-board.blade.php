{{-- Port of components/kanban/KanbanBoard.tsx + BoardColumn.tsx (logic: resources/js/alpine/kanban.js) --}}
<div x-data="kanbanBoard" class="flex-1 flex flex-col h-full overflow-hidden">
  {{-- Unit & board context bar --}}
  <div class="px-4 sm:px-6 py-2.5 bg-white dark:bg-[#111726] border-b border-[#cbd5e1] dark:border-[#1e2a3f] flex items-center justify-between gap-3 shrink-0">
    <div class="flex items-center gap-2 text-xs flex-wrap">
      <button type="button" @click="$store.kb.setActiveView('hub')" title="Kembali ke Level 1 (Kanban Hub)"
        class="flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold text-[#034694] dark:text-blue-400 hover:bg-[#e0edfb] dark:hover:bg-[#1a263d] transition-colors border border-blue-200 dark:border-blue-900/60">
        <x-icon name="layers" class="w-3.5 h-3.5" />
        <span>← Papan Hub (Level 1)</span>
      </button>
      <span class="text-[#cbd5e1] dark:text-[#2b3a52]">/</span>
      <span class="flex items-center gap-1 px-2 py-0.5 rounded text-[length:calc(11px+var(--fs-up))] font-extrabold bg-[#e0edfb] dark:bg-[#1e293b] text-[#034694] dark:text-blue-300 border border-blue-200 dark:border-blue-900/60">
        <x-icon name="tag" class="w-3 h-3 text-[#034694] dark:text-blue-400" />
        <span x-text="'Tag: #' + ($store.kb.currentProject?.unit_tag || 'IT')"></span>
      </span>
      <span class="font-extrabold text-[#0f172a] dark:text-white truncate max-w-xs" x-text="$store.kb.currentProject?.name"></span>
      <span class="text-[#cbd5e1] dark:text-[#2b3a52] hidden sm:inline">•</span>
      <span class="text-[#64748b] dark:text-[#94a3b8] font-medium hidden sm:inline" x-text="$store.kb.columns.length + ' Kolom Child'"></span>
      <span class="text-[#cbd5e1] dark:text-[#2b3a52] hidden sm:inline">•</span>
      <span class="text-[#64748b] dark:text-[#94a3b8] font-medium hidden sm:inline" x-text="$store.kb.allTasks.length + ' Tugas & Subtask'"></span>
    </div>
    <div class="flex items-center gap-2">
      <button type="button" @click="$store.kb.setActiveView('timeline')"
        class="flex items-center gap-1.5 px-3 py-1 bg-[#f1f5f9] dark:bg-[#162032] hover:bg-[#e2e8f0] dark:hover:bg-[#1f2d45] border border-[#cbd5e1] dark:border-[#2b3b55] rounded-lg text-xs font-bold text-[#0f172a] dark:text-white transition-colors">
        <x-icon name="clock" class="w-3.5 h-3.5 text-[#034694] dark:text-blue-400" />
        <span class="hidden sm:inline">Buka Timeline</span>
      </button>
    </div>
  </div>

  <div class="flex-1 flex gap-4 sm:gap-5 overflow-x-auto p-3 sm:p-5 md:p-6 items-start h-full select-none touch-pan-x scroll-smooth">
    {{-- Columns (BoardColumn) --}}
    <template x-for="column in $store.kb.columns" :key="column.id">
      <div class="w-[82vw] sm:w-80 shrink-0 flex flex-col max-h-full bg-[#e6ebf2] dark:bg-[#101726] rounded-xl border border-[#cbd5e1] dark:border-[#1e2a3f] select-none shadow-xs transition-colors">
        <div class="p-3.5 border-b border-[#cbd5e1] dark:border-[#1e2a3f] flex items-center justify-between bg-[#edf2f7] dark:bg-[#131b2e] rounded-t-xl">
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-[#034694] dark:bg-blue-400"></span>
            <h3 class="font-bold text-xs text-[#0f172a] dark:text-[#f8fafc] tracking-wide uppercase" x-text="column.title"></h3>
            <span class="px-2 py-0.5 rounded-full text-[length:calc(10px+var(--fs-up))] font-bold bg-[#cbd5e1] dark:bg-[#1e2a3f] text-[#334155] dark:text-[#cbd5e1]" x-text="tasksOf(column).length"></span>
          </div>
          <div class="flex items-center gap-1">
            <button type="button" @click="$store.kb.openCreateModal(column.id)" title="Tambah tugas ke kolom ini"
              class="p-1 rounded hover:bg-[#cbd5e1] dark:hover:bg-[#1e2a3f] text-[#475569] dark:text-[#94a3b8] hover:text-[#0f172a] dark:hover:text-white transition-colors">
              <x-icon name="plus" class="w-4 h-4" />
            </button>
          </div>
        </div>

        <div :data-col="column.id" x-init="$nextTick(() => sort($el))"
          class="flex-1 p-2.5 space-y-2.5 overflow-y-auto min-h-[160px] transition-colors rounded-b-xl">
          <template x-for="task in tasksOf(column)" :key="task.id">
            @include('partials.task-card')
          </template>
          <template x-if="tasksOf(column).length === 0">
            <div @click="$store.kb.openCreateModal(column.id)"
              class="h-28 border-2 border-dashed border-[#94a3b8] dark:border-[#2b3a52] rounded-lg flex flex-col items-center justify-center text-xs text-[#64748b] dark:text-[#94a3b8] hover:border-[#034694] dark:hover:border-blue-400 hover:text-[#034694] dark:hover:text-blue-400 cursor-pointer transition-colors bg-white/40 dark:bg-[#162032]/40">
              <x-icon name="plus" class="w-4 h-4 mb-1" />
              <span class="font-semibold">Tarik kartu ke sini / Tambah</span>
            </div>
          </template>
        </div>
      </div>
    </template>

    {{-- Add column --}}
    <div class="w-[82vw] sm:w-80 shrink-0">
      <template x-if="isAddingColumn">
        <form @submit.prevent="addColumn()" class="bg-white dark:bg-[#162032] border border-[#cbd5e1] dark:border-[#2b3b55] rounded-xl p-4 shadow-sm space-y-3">
          <input type="text" x-model="newColTitle" placeholder="Judul kolom baru..." x-init="$nextTick(() => $el.focus())"
            class="w-full px-3 py-2 border border-[#cbd5e1] dark:border-[#334460] rounded-lg text-xs bg-white dark:bg-[#101726] text-[#0f172a] dark:text-white focus:outline-none focus:border-[#034694]" />
          <div class="flex items-center gap-2">
            <button type="submit" class="px-3 py-1.5 bg-[#034694] hover:bg-[#023470] text-white text-xs font-bold rounded-md">Tambah Kolom</button>
            <button type="button" @click="isAddingColumn = false" class="px-3 py-1.5 text-xs text-[#64748b] dark:text-[#94a3b8] hover:text-[#0f172a] dark:hover:text-white">Batal</button>
          </div>
        </form>
      </template>
      <template x-if="!isAddingColumn">
        <button type="button" @click="isAddingColumn = true"
          class="w-full flex items-center justify-center gap-2 p-3.5 bg-white/70 dark:bg-[#101726]/60 hover:bg-white dark:hover:bg-[#162032] border-2 border-dashed border-[#94a3b8] dark:border-[#24334b] hover:border-[#034694] dark:hover:border-blue-400 rounded-xl text-xs font-bold text-[#475569] dark:text-[#cbd5e1] hover:text-[#034694] dark:hover:text-blue-400 transition-colors shadow-2xs">
          <x-icon name="plus" class="w-4 h-4" />
          <span>Tambah Kolom Baru</span>
        </button>
      </template>
    </div>
  </div>
</div>
