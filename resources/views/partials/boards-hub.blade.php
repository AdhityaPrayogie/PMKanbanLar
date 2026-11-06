{{-- Port of components/kanban/BoardsHub.tsx (logic: resources/js/alpine/boards-hub.js) --}}
<div x-data="boardsHub" class="flex-1 flex flex-col h-full overflow-hidden select-none bg-[#f1f5f9] dark:bg-[#090e17]">
  {{-- Header bar --}}
  <div class="px-4 sm:px-6 py-3 bg-white dark:bg-[#111726] border-b border-[#cbd5e1] dark:border-[#1e2a3f] flex flex-col md:flex-row md:items-center justify-between gap-3 shrink-0">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#034694] to-[#023470] text-white flex items-center justify-center shadow-xs shrink-0">
        <x-icon name="layers" class="w-4.5 h-4.5" />
      </div>
      <div>
        <div class="flex items-center gap-2">
          <h1 class="text-base sm:text-lg font-extrabold text-[#0f172a] dark:text-white tracking-tight leading-none whitespace-nowrap">Project Hub</h1>
          <span class="px-2 py-0.5 rounded-full whitespace-nowrap text-[length:calc(11px+var(--fs-up))] font-bold bg-[#e0edfb] dark:bg-blue-950/60 text-[#034694] dark:text-blue-300 border border-blue-200/80 dark:border-blue-900/40" x-text="accessible.length + ' Papan'"></span>
        </div>
        <template x-if="!isAdmin">
          <span class="text-[length:calc(11px+var(--fs-up))] text-[#64748b] dark:text-[#94a3b8] mt-0.5 block">Unit Anda: <strong class="text-[#034694] dark:text-blue-400" x-text="'#' + userUnit"></strong></span>
        </template>
      </div>
    </div>

    <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap justify-between md:justify-end">
      {{-- Unit tag pills --}}
      <div class="flex items-center gap-1 bg-[#f1f5f9] dark:bg-[#162032] p-1 rounded-xl border border-[#cbd5e1] dark:border-[#2b3b55] overflow-x-auto scrollbar-none">
        <template x-for="tag in unitTags" :key="tag">
          <button type="button" @click="selectedTag = tag"
            class="h-7 px-2.5 rounded-lg text-xs font-bold transition-all shrink-0 flex items-center gap-1.5 cursor-pointer active:scale-95"
            :class="selectedTag === tag ? 'bg-white dark:bg-[#034694] text-[#034694] dark:text-white shadow-2xs' : 'text-[#64748b] dark:text-[#94a3b8] hover:text-[#0f172a] dark:hover:text-white'">
            <span x-text="tag === 'All' ? 'Semua Unit' : '#' + tag"></span>
            <span class="text-[length:calc(10px+var(--fs-up))] px-1.5 py-px rounded-full font-bold"
              :class="selectedTag === tag ? 'bg-[#e0edfb] dark:bg-white/20 text-[#034694] dark:text-white' : 'bg-[#e2e8f0] dark:bg-[#202e44] text-[#64748b] dark:text-[#94a3b8]'"
              x-text="tagCount(tag)"></span>
          </button>
        </template>
      </div>

      {{-- Search --}}
      <div class="relative w-36 sm:w-44 md:w-48 h-9 flex items-center shrink-0">
        <x-icon name="search" class="w-3.5 h-3.5 text-[#94a3b8] absolute left-3 pointer-events-none" />
        <input type="text" x-model="localSearch" placeholder="Cari board..."
          class="w-full h-full pl-8 pr-7 bg-white dark:bg-[#162032] border border-[#cbd5e1] dark:border-[#2b3b55] rounded-xl text-xs text-[#0f172a] dark:text-white placeholder-[#94a3b8] focus:outline-none focus:border-[#034694] transition-all shadow-2xs" />
        <button type="button" x-show="localSearch" @click="localSearch = ''"
          class="absolute right-2.5 text-xs text-[#94a3b8] hover:text-[#0f172a] dark:hover:text-white cursor-pointer font-bold">×</button>
      </div>

      <button type="button" @click="add('Active')"
        class="h-9 flex items-center justify-center gap-1.5 px-3.5 bg-[#034694] hover:bg-[#023470] text-white rounded-xl text-xs font-bold shadow-xs hover:shadow-md transition-all shrink-0 cursor-pointer active:scale-95">
        <x-icon name="plus" class="w-4 h-4" />
        <span>Tambah Board</span>
      </button>
    </div>
  </div>

  {{-- Stage columns --}}
  <div class="overflow-x-auto flex-1 p-3 sm:p-5 md:p-6 select-none bg-[#f1f5f9] dark:bg-[#090e17] touch-pan-x scroll-smooth">
    <div class="flex gap-4 sm:gap-5 h-full items-start">
      <template x-for="col in stageColumns" :key="col.id">
        <div class="w-[82vw] sm:w-80 shrink-0 flex flex-col max-h-full bg-[#e6ebf2] dark:bg-[#101726] rounded-2xl border border-[#cbd5e1] dark:border-[#1e2a3f] select-none shadow-xs transition-colors">
          <div class="p-3.5 border-b border-[#cbd5e1] dark:border-[#1e2a3f] flex items-center justify-between bg-[#edf2f7] dark:bg-[#131b2e] rounded-t-2xl">
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full" :class="col.dot"></span>
              <h3 class="font-bold text-xs text-[#0f172a] dark:text-[#f8fafc] tracking-wide uppercase" x-text="col.title"></h3>
              <span class="px-2 py-0.5 rounded-full text-[length:calc(10px+var(--fs-up))] font-bold bg-[#cbd5e1] dark:bg-[#1e2a3f] text-[#334155] dark:text-[#cbd5e1]" x-text="boardsIn(col.id).length"></span>
            </div>
            <button type="button" @click="add(col.id)" :title="'Tambah Board Baru di kolom ' + col.title"
              class="p-1 rounded-lg hover:bg-[#cbd5e1] dark:hover:bg-[#1e2a3f] text-[#475569] dark:text-[#94a3b8] hover:text-[#0f172a] dark:hover:text-white transition-colors cursor-pointer">
              <x-icon name="plus" class="w-4 h-4" />
            </button>
          </div>

          <div :data-stage="col.id" x-init="$nextTick(() => sort($el))"
            class="flex-1 p-2.5 space-y-2.5 overflow-y-auto min-h-[160px] transition-colors rounded-b-2xl">
            <template x-for="b in boardsIn(col.id)" :key="b.id">
              <div :data-id="b.id" @click="open(b.id, 'board')"
                class="group bg-white dark:bg-[#162032] rounded-2xl p-3.5 border transition-all duration-150 cursor-pointer select-none relative border-[#cbd5e1] dark:border-[#202e44] hover:border-[#034694] dark:hover:border-blue-400 hover:shadow-md shadow-2xs">
                <div class="flex items-center justify-between gap-1 mb-2">
                  <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[length:calc(10px+var(--fs-up))] font-bold bg-[#e0edfb] dark:bg-[#1e293b] text-[#034694] dark:text-blue-300 border border-blue-200 dark:border-blue-900/60 shadow-2xs">
                      <x-icon name="tag" class="w-2.5 h-2.5 text-[#034694] dark:text-blue-400" />
                      <span x-text="'#' + getTag(b)"></span>
                    </span>
                    <span class="px-2 py-0.5 rounded-md text-[length:calc(10px+var(--fs-up))] font-bold border tracking-wide" :class="stageBadge[getStage(b)]" x-text="getStage(b)"></span>
                  </div>
                  <div class="flex items-center gap-1" @click.stop>
                    <div class="relative group/shift">
                      <select aria-label="Pindahkan status board utama" :value="getStage(b)"
                        @change="$store.kb.moveBoardStage(b.id, $event.target.value).then((ok) => ok || ($event.target.value = getStage(b)))"
                        class="text-[length:calc(10px+var(--fs-up))] font-semibold bg-[#f1f5f9] dark:bg-[#1e2a3f] text-[#475569] dark:text-[#94a3b8] border border-[#cbd5e1] dark:border-[#334460] rounded-md px-1.5 py-0.5 focus:outline-none focus:border-[#034694] cursor-pointer hover:bg-white dark:hover:bg-[#25354e]">
                        <option value="Planning">Planning</option>
                        <option value="Active">Active</option>
                        <option value="Review">Review</option>
                        <option value="Completed">Completed</option>
                      </select>
                    </div>
                    <div data-handle title="Tarik kartu untuk memindahkan kolom"
                      class="text-[#94a3b8] dark:text-[#64748b] hover:text-[#0f172a] dark:hover:text-white p-0.5 rounded cursor-grab active:cursor-grabbing">
                      <x-icon name="grip-vertical" class="w-3.5 h-3.5" />
                    </div>
                  </div>
                </div>

                <h4 class="text-xs font-bold text-[#0f172a] dark:text-[#f8fafc] leading-snug line-clamp-2 mb-1.5 group-hover:text-[#034694] dark:group-hover:text-blue-400 transition-colors" x-text="b.name"></h4>
                <template x-if="b.description">
                  <p class="text-[length:calc(11px+var(--fs-up))] text-[#475569] dark:text-[#94a3b8] line-clamp-2 mb-2.5 leading-relaxed" x-text="b.description"></p>
                </template>

                <div class="mb-2.5 flex items-center gap-2 text-[length:calc(10px+var(--fs-up))] font-medium text-[#475569] dark:text-[#cbd5e1] bg-[#f8fafc] dark:bg-[#101726] px-2.5 py-1.5 rounded-lg border border-[#cbd5e1]/60 dark:border-[#223147]">
                  <x-icon name="folder-kanban" class="w-3.5 h-3.5 text-[#034694] dark:text-blue-400" />
                  <span x-text="(b.columns_count || 4) + ' Kolom Child'"></span>
                  <span>•</span>
                  <span x-text="(b.tasks_count || 0) + ' Tugas & Subtask'"></span>
                </div>

                <div class="flex items-center gap-2 pt-2 border-t border-[#e2e8f0] dark:border-[#223147] mt-1 text-[length:calc(11px+var(--fs-up))]">
                  <button type="button" @click.stop="open(b.id, 'board')" title="Buka papan kanban child"
                    class="flex-1 h-8.5 flex items-center justify-center gap-1.5 px-2.5 bg-[#034694] hover:bg-[#023470] text-white rounded-lg text-xs font-bold transition-all shadow-2xs cursor-pointer active:scale-95">
                    <x-icon name="kanban" class="w-3.5 h-3.5" />
                    <span>Buka Board</span>
                  </button>
                  <button type="button" @click.stop="open(b.id, 'timeline')" title="Buka timeline gantt chart"
                    class="flex-1 h-8.5 flex items-center justify-center gap-1.5 px-2.5 border border-[#cbd5e1] dark:border-[#2b3b55] bg-white dark:bg-[#121929] hover:bg-[#f1f5f9] dark:hover:bg-[#182236] text-[#0f172a] dark:text-white rounded-lg text-xs font-bold transition-colors cursor-pointer active:scale-95">
                    <x-icon name="clock" class="w-3.5 h-3.5 text-[#034694] dark:text-blue-400" />
                    <span>Timeline</span>
                  </button>
                </div>
              </div>
            </template>

            <template x-if="boardsIn(col.id).length === 0">
              <div class="p-6 text-center border border-dashed border-[#cbd5e1] dark:border-[#283850] rounded-xl text-xs text-[#94a3b8] space-y-2">
                <p>Belum ada board pada kolom ini</p>
                <p class="text-[length:calc(10px+var(--fs-up))] text-[#64748b] dark:text-[#94a3b8]">Drag kartu papan ke sini untuk mengubah status</p>
                <button type="button" @click="add(col.id)" class="text-[length:calc(11px+var(--fs-up))] font-bold text-[#034694] dark:text-blue-400 hover:underline flex items-center justify-center gap-1 mx-auto">
                  <x-icon name="plus" class="w-3 h-3" />
                  <span>Tambah Board di sini</span>
                </button>
              </div>
            </template>
          </div>
        </div>
      </template>
    </div>
  </div>
</div>
