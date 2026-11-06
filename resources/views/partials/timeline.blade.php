{{-- Port of components/timeline/TimelineView.tsx (logic: resources/js/alpine/timeline.js) --}}
@php
  $dayShade = '<div class="absolute inset-0 grid divide-x divide-[#f1f5f9] dark:divide-[#162238] pointer-events-none" :style="gridCols">'
    . '<template x-for="d in days" :key="d.dayNum"><div class="h-full" :class="{ \'bg-slate-50/60 dark:bg-[#0c1220]/50\': d.isWeekend, \'bg-blue-50/50 dark:bg-blue-950/30\': d.isToday }"></div></template>'
    . '</div>';
@endphp
<div x-data="timeline" class="flex-1 flex flex-col h-full bg-[#f1f5f9] dark:bg-[#090e17] overflow-hidden p-3 sm:p-5 md:p-6 transition-colors">
  {{-- Header & breadcrumb --}}
  <div class="flex flex-col lg:flex-row lg:items-center justify-between pb-3.5 sm:pb-4 gap-3 shrink-0">
    <div>
      <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-[#64748b] dark:text-[#94a3b8] mb-1.5 flex-wrap">
        <button type="button" @click="$store.kb.setActiveView('hub')" class="hover:text-[#034694] dark:hover:text-blue-400 font-semibold flex items-center gap-1 transition-colors cursor-pointer">
          <x-icon name="layers" class="w-3.5 h-3.5" />
          <span>Project Hub</span>
        </button>
        <span class="text-[#cbd5e1] dark:text-[#334155]">/</span>
        <span class="font-semibold text-[#334155] dark:text-[#e2e8f0] truncate max-w-[200px]" x-text="$store.kb.currentProject?.name || 'Board'"></span>
        <template x-if="$store.kb.currentProject?.unit_tag">
          <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[length:calc(10px+var(--fs-up))] font-bold bg-[#e0edfb] dark:bg-blue-950/60 text-[#034694] dark:text-blue-300 border border-blue-200/80 dark:border-blue-900/40">
            <x-icon name="tag" class="w-2.5 h-2.5" />
            <span x-text="'#' + $store.kb.currentProject.unit_tag"></span>
          </span>
        </template>
      </nav>
      <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-gradient-to-br from-[#034694] to-[#023470] text-white flex items-center justify-center shadow-xs shrink-0">
          <x-icon name="clock" class="w-4 h-4 sm:w-4.5 sm:h-4.5" />
        </div>
        <h1 class="text-base sm:text-lg md:text-xl font-extrabold text-[#0f172a] dark:text-white tracking-tight leading-none">Timeline &amp; Gantt Schedule</h1>
      </div>
    </div>

    <div class="flex items-center gap-2 flex-wrap">
      <div class="flex items-center h-9 bg-white dark:bg-[#162032] border border-[#cbd5e1] dark:border-[#2b3b55] rounded-xl p-0.5 shadow-2xs">
        <button type="button" @click="prevMonth()" title="Bulan sebelumnya" class="w-8 h-8 rounded-lg flex items-center justify-center text-[#64748b] hover:text-[#0f172a] dark:text-[#94a3b8] dark:hover:text-white hover:bg-[#f1f5f9] dark:hover:bg-[#1e2a3f] transition-colors cursor-pointer">
          <x-icon name="chevron-left" class="w-4 h-4" />
        </button>
        <div class="flex items-center gap-1.5 px-2.5 text-xs font-bold text-[#0f172a] dark:text-white capitalize min-w-[125px] justify-center select-none">
          <x-icon name="calendar-days" class="w-3.5 h-3.5 text-[#034694] dark:text-blue-400 shrink-0" />
          <span x-text="monthLabel"></span>
        </div>
        <button type="button" @click="nextMonth()" title="Bulan berikutnya" class="w-8 h-8 rounded-lg flex items-center justify-center text-[#64748b] hover:text-[#0f172a] dark:text-[#94a3b8] dark:hover:text-white hover:bg-[#f1f5f9] dark:hover:bg-[#1e2a3f] transition-colors cursor-pointer">
          <x-icon name="chevron-right" class="w-4 h-4" />
        </button>
        <button type="button" @click="thisMonth()" title="Kembali ke bulan ini" class="h-7 px-2.5 ml-0.5 text-[length:calc(11px+var(--fs-up))] font-bold rounded-lg bg-[#e0edfb] hover:bg-[#d0e5fb] dark:bg-blue-950/60 dark:hover:bg-blue-900/60 text-[#034694] dark:text-blue-300 transition-colors cursor-pointer">Bulan Ini</button>
      </div>

      <div class="hidden xl:flex items-center h-9 gap-3 text-[length:calc(11px+var(--fs-up))] font-medium text-[#475569] dark:text-[#94a3b8] bg-white dark:bg-[#162032] px-3 rounded-xl border border-[#cbd5e1] dark:border-[#2b3b55] shadow-2xs select-none">
        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-rose-600"></span> Urgent</span>
        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-600"></span> High</span>
        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-[#034694] dark:bg-blue-500"></span> Medium</span>
        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-slate-400"></span> Low</span>
      </div>

      <button type="button" @click="$store.kb.setActiveView('board')" title="Kembali ke Kanban Board" class="h-9 flex items-center gap-1.5 px-3 bg-white dark:bg-[#162032] hover:bg-[#f1f5f9] dark:hover:bg-[#1e2a3f] text-[#0f172a] dark:text-white border border-[#cbd5e1] dark:border-[#2b3b55] text-xs font-semibold rounded-xl shadow-2xs transition-all cursor-pointer active:scale-95">
        <x-icon name="kanban" class="w-3.5 h-3.5 text-[#034694] dark:text-blue-400" />
        <span class="hidden sm:inline">Kanban Board</span>
      </button>
      <button type="button" @click="$store.kb.openCreateModal()" class="h-9 flex items-center gap-1.5 px-3.5 bg-[#034694] hover:bg-[#023470] text-white text-xs font-bold rounded-xl shadow-xs hover:shadow-md transition-all cursor-pointer active:scale-95">
        <x-icon name="plus" class="w-4 h-4" />
        <span>Tambah Tugas</span>
      </button>
    </div>
  </div>

  {{-- Gantt calendar --}}
  <div class="isolate flex-1 overflow-x-auto overflow-y-auto touch-pan-x scroll-smooth bg-white dark:bg-[#111726] border border-[#cbd5e1] dark:border-[#1e2a3f] rounded-2xl shadow-xs flex flex-col min-h-0">
    <div class="flex-1 flex flex-col" :style="minWidth">
      {{-- Sticky header --}}
      <div class="flex border-b border-[#cbd5e1] dark:border-[#1e2a3f] bg-[#f8fafc] dark:bg-[#131b2e] text-xs font-bold text-[#334155] dark:text-[#cbd5e1] sticky top-0 z-30 select-none">
        <div class="w-[260px] sm:w-[320px] md:w-[360px] p-3.5 border-r border-[#cbd5e1] dark:border-[#1e2a3f] shrink-0 flex items-center gap-2 sticky left-0 z-40 bg-[#f8fafc] dark:bg-[#131b2e] shadow-xs">
          <x-icon name="calendar" class="w-4 h-4 text-[#034694] dark:text-blue-400" />
          <span>Hierarki Tugas &amp; Sub-task</span>
        </div>
        <div class="w-24 sm:w-28 p-3.5 border-r border-[#cbd5e1] dark:border-[#1e2a3f] shrink-0 text-center">Status</div>
        <div class="w-28 sm:w-36 p-3.5 border-r border-[#cbd5e1] dark:border-[#1e2a3f] shrink-0 text-center">Durasi</div>
        <div class="flex-1 grid text-center divide-x divide-[#cbd5e1] dark:divide-[#1e2a3f]" :style="gridCols">
          <template x-for="d in days" :key="d.dayNum">
            <div class="pt-3.5 pb-2 px-0.5 relative flex flex-col items-center justify-center transition-colors"
              :class="{ 'bg-slate-100/70 dark:bg-[#0c1220]/70 text-[#64748b] dark:text-[#94a3b8]': d.isWeekend, 'bg-blue-100/90 dark:bg-blue-950/80 text-[#034694] dark:text-blue-300 font-black': d.isToday }">
              <div class="text-[length:calc(10px+var(--fs-up))] uppercase font-bold tracking-wider opacity-75 mb-0.5" x-text="d.label"></div>
              <div class="text-xs font-extrabold leading-none" :class="d.isToday && 'text-[#034694] dark:text-blue-400'" x-text="d.dayNum"></div>
              <template x-if="d.isToday">
                <span class="absolute top-0 inset-x-0 mx-auto w-fit text-[length:calc(8px+var(--fs-up))] px-1 py-px rounded bg-[#034694] text-white font-extrabold leading-tight shadow-2xs">Kini</span>
              </template>
            </div>
          </template>
        </div>
      </div>

      {{-- Rows --}}
      <div class="divide-y divide-[#e2e8f0] dark:divide-[#1e2a3f] flex-1">
        <template x-if="parentTasks.length === 0">
          <div class="p-12 text-center text-xs text-[#94a3b8] italic"
            x-text="$store.kb.searchQuery.trim() ? `Tidak ada tugas atau timeline yang cocok dengan pencarian &quot;${$store.kb.searchQuery}&quot;.` : 'Belum ada tugas pada board ini. Klik &quot;Tambah Tugas&quot; untuk membuat tugas baru.'"></div>
        </template>

        <template x-for="task in parentTasks" :key="task.id">
          <div x-data="{ get b() { return bar(task.start_date, task.due_date) }, get dur() { return duration(task.start_date, task.due_date) }, get subs() { return task.sub_tasks || [] } }">
            {{-- Parent row --}}
            <div @click="$store.kb.openTaskSheet(task.id)" class="flex items-center hover:bg-[#f8fafc] dark:hover:bg-[#162032] cursor-pointer transition-colors group text-xs min-h-[58px] bg-white dark:bg-[#111726]">
              <div class="w-[260px] sm:w-[320px] md:w-[360px] p-3.5 pl-4 border-r border-[#cbd5e1] dark:border-[#1e2a3f] shrink-0 flex items-center gap-2 sticky left-0 z-20 bg-white dark:bg-[#111726] group-hover:bg-[#f8fafc] dark:group-hover:bg-[#162032] shadow-xs">
                <template x-if="subs.length > 0">
                  <button type="button" @click.stop="toggle(task.id)" :title="expanded[task.id] ? 'Tutup sub-tasks' : 'Buka sub-tasks'" class="p-1 hover:bg-[#cbd5e1] dark:hover:bg-[#1e2a3f] rounded-md text-[#475569] dark:text-[#94a3b8] transition-colors">
                    <span x-show="expanded[task.id]" class="contents"><x-icon name="chevron-down" class="w-4 h-4 text-[#034694] dark:text-blue-400" /></span>
                    <span x-show="!expanded[task.id]" class="contents"><x-icon name="chevron-right" class="w-4 h-4" /></span>
                  </button>
                </template>
                <template x-if="subs.length === 0"><div class="w-6"></div></template>
                <div class="font-extrabold text-[#0f172a] dark:text-white truncate flex-1 text-xs sm:text-[length:calc(13px+var(--fs-up))]" x-text="task.title"></div>
                <template x-if="subs.length > 0">
                  <span class="px-1.5 py-0.5 rounded-full text-[length:calc(10px+var(--fs-up))] bg-blue-100 dark:bg-blue-950 text-[#034694] dark:text-blue-300 font-extrabold shrink-0 border border-blue-200 dark:border-blue-900/60" x-text="subs.length + ' sub'"></span>
                </template>
                <button type="button" @click.stop="$store.kb.openCreateModal(task.column_id, task.id)" title="Tambah sub-task ke tugas ini" class="opacity-0 group-hover:opacity-100 p-1 hover:bg-[#e2e8f0] dark:hover:bg-[#1e2a3f] rounded-md text-[#034694] dark:text-blue-400 transition-opacity">
                  <x-icon name="plus" class="w-3.5 h-3.5" />
                </button>
              </div>

              <div class="w-24 sm:w-28 p-2.5 border-r border-[#cbd5e1] dark:border-[#1e2a3f] shrink-0 text-center">
                <span class="px-2 py-0.5 rounded-md text-[length:calc(10px+var(--fs-up))] font-bold bg-[#f1f5f9] dark:bg-[#1e2a3f] text-[#334155] dark:text-[#cbd5e1] border border-[#cbd5e1] dark:border-[#2b3a52] inline-block truncate max-w-full" x-text="status(task)"></span>
              </div>

              <div class="w-28 sm:w-36 p-2.5 border-r border-[#cbd5e1] dark:border-[#1e2a3f] shrink-0 text-center text-xs">
                <div class="font-bold text-[#0f172a] dark:text-white text-[length:calc(11px+var(--fs-up))]" x-text="dur ? dur + ' Hari' : '-'"></div>
                <div class="text-[length:calc(10px+var(--fs-up))] text-[#64748b] dark:text-[#94a3b8] mt-0.5" x-text="$u.formatDate(task.start_date) + ' - ' + $u.formatDate(task.due_date)"></div>
              </div>

              <div class="flex-1 h-full relative py-2 px-0 flex items-center min-h-[58px]">
                {!! $dayShade !!}
                <template x-if="b.isVisible">
                  <div class="h-9 rounded-lg shadow-sm flex items-center px-2.5 text-white text-[length:calc(11px+var(--fs-up))] font-bold truncate transition-all z-[1] border border-white/25 hover:scale-[1.01] select-none absolute"
                    :class="priorityColors[task.priority] || 'bg-[#034694]'" :style="b.style"
                    :title="`${task.title} (${task.priority}) • ${$u.formatDate(task.start_date)} - ${$u.formatDate(task.due_date)}`">
                    <template x-if="b.startsBefore"><span class="mr-1 text-[length:calc(10px+var(--fs-up))] opacity-80 shrink-0 font-mono">◀</span></template>
                    <span class="truncate flex items-center gap-1.5 flex-1">
                      <span class="truncate" x-text="task.title"></span>
                      <template x-if="dur"><span class="text-[length:calc(10px+var(--fs-up))] opacity-85 font-normal shrink-0" x-text="'(' + dur + 'h)'"></span></template>
                    </span>
                    <template x-if="b.endsAfter"><span class="ml-1 text-[length:calc(10px+var(--fs-up))] opacity-80 shrink-0 font-mono">▶</span></template>
                  </div>
                </template>
                <template x-if="!b.isVisible">
                  <div class="px-3 text-[length:calc(11px+var(--fs-up))] italic text-[#94a3b8] dark:text-[#64748b] z-[1]" x-text="'(Di luar bulan ' + monthLabel + ')'"></div>
                </template>
              </div>
            </div>

            {{-- Sub-task rows --}}
            <template x-for="sub in (expanded[task.id] ? subs : [])" :key="sub.id">
              <div x-data="{ get sb() { return bar(sub.start_date, sub.due_date) }, get sdur() { return duration(sub.start_date, sub.due_date) } }"
                @click="$store.kb.openTaskSheet(sub.id)"
                class="flex items-center bg-[#f8fafc]/85 dark:bg-[#0c1220]/75 hover:bg-[#edf2f7] dark:hover:bg-[#162032] cursor-pointer transition-colors text-xs border-t border-[#e2e8f0] dark:border-[#1a2538] min-h-[50px] group">
                <div class="w-[260px] sm:w-[320px] md:w-[360px] p-2.5 pl-10 border-r border-[#cbd5e1] dark:border-[#1e2a3f] shrink-0 flex items-center gap-2 sticky left-0 z-20 bg-[#f8fafc] dark:bg-[#0c1220] group-hover:bg-[#edf2f7] dark:group-hover:bg-[#162032] shadow-xs">
                  <x-icon name="corner-down-right" class="w-3.5 h-3.5 text-[#94a3b8] shrink-0" />
                  <span class="text-[#334155] dark:text-[#cbd5e1] font-bold truncate flex-1 text-xs" x-text="sub.title"></span>
                  <span class="text-[length:calc(10px+var(--fs-up))] px-1.5 py-px rounded font-extrabold bg-white dark:bg-[#162032] border border-[#cbd5e1] dark:border-[#2b3a52] text-[#64748b] dark:text-[#94a3b8] shrink-0">Sub</span>
                </div>
                <div class="w-24 sm:w-28 p-2 border-r border-[#cbd5e1] dark:border-[#1e2a3f] shrink-0 text-center">
                  <span class="text-[length:calc(10px+var(--fs-up))] font-semibold text-[#64748b] dark:text-[#94a3b8] truncate block" x-text="status(sub)"></span>
                </div>
                <div class="w-28 sm:w-36 p-2 border-r border-[#cbd5e1] dark:border-[#1e2a3f] shrink-0 text-center text-[length:calc(10px+var(--fs-up))] text-[#64748b] dark:text-[#94a3b8]">
                  <span class="font-semibold text-[#0f172a] dark:text-white" x-text="sdur ? sdur + ' Hari' : '-'"></span>
                </div>
                <div class="flex-1 h-full relative py-1.5 px-0 flex items-center min-h-[50px]">
                  {!! $dayShade !!}
                  <template x-if="sb.isVisible">
                    <div class="h-7 rounded-md opacity-95 border border-white/40 flex items-center px-2 text-white text-[length:calc(10px+var(--fs-up))] font-semibold truncate shadow-2xs z-[1] select-none absolute"
                      :class="priorityColors[sub.priority] || 'bg-[#034694]'" :style="sb.style" :title="`${sub.title} (${sub.priority})`">
                      <template x-if="sb.startsBefore"><span class="mr-1 text-[length:calc(10px+var(--fs-up))] opacity-80 shrink-0 font-mono">◀</span></template>
                      <span class="truncate flex-1" x-text="sub.title"></span>
                      <template x-if="sdur"><span class="text-[length:calc(10px+var(--fs-up))] opacity-85 ml-1 font-normal shrink-0" x-text="'(' + sdur + 'h)'"></span></template>
                      <template x-if="sb.endsAfter"><span class="ml-1 text-[length:calc(10px+var(--fs-up))] opacity-80 shrink-0 font-mono">▶</span></template>
                    </div>
                  </template>
                  <template x-if="!sb.isVisible">
                    <div class="px-3 text-[length:calc(10px+var(--fs-up))] italic text-[#94a3b8] dark:text-[#64748b] z-[1]" x-text="'(Di luar bulan ' + monthLabel + ')'"></div>
                  </template>
                </div>
              </div>
            </template>
          </div>
        </template>
      </div>
    </div>
  </div>
</div>
