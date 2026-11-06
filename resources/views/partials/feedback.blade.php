{{-- Global confirm dialog + toast stack, driven by $store.kb.ask() / toast() (store.js).
     Both live in the browser top layer (<dialog> / popover) so they also sit above helpdesk <dialog> modals. --}}
<dialog x-data x-effect="$store.kb.confirm ? ($el.open || $el.showModal()) : ($el.open && $el.close())" @close="$store.kb.answer(false)"
  aria-labelledby="kb-confirm-title"
  class="m-auto w-[calc(100%-2rem)] max-w-sm rounded-2xl border border-[#cbd5e1] dark:border-[#1e2a3f] bg-white dark:bg-[#111726] text-[#0f172a] dark:text-[#f8fafc] p-0 shadow-2xl backdrop:bg-slate-950/70 backdrop:backdrop-blur-xs">
  <form method="dialog" @submit.prevent="$store.kb.answer(true)" class="p-6 text-center space-y-4">
    <div class="w-12 h-12 rounded-full mx-auto flex items-center justify-center"
      :class="$store.kb.confirm?.danger ? 'bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400' : 'bg-amber-50 dark:bg-amber-950/40 text-amber-500'">
      <x-icon name="alert-triangle" class="w-6 h-6" />
    </div>
    <div>
      <h4 id="kb-confirm-title" class="text-sm font-extrabold text-[#0f172a] dark:text-white" x-text="$store.kb.confirm?.title"></h4>
      <p class="text-xs text-[#475569] dark:text-[#94a3b8] mt-1.5 leading-relaxed" x-text="$store.kb.confirm?.message"></p>
    </div>
    <template x-if="$store.kb.confirm && $store.kb.confirm.input !== undefined">
      <input type="text" x-model="$store.kb.confirm.value" x-init="$nextTick(() => $el.select())" required aria-label="Nilai baru"
        class="w-full px-3 py-2 text-xs rounded-xl border border-[#cbd5e1] dark:border-[#2b3b55] bg-white dark:bg-[#162032] focus:outline-none focus:border-[#034694]">
    </template>
    <div class="flex items-center gap-2 pt-1">
      <button type="button" @click="$store.kb.answer(false)" class="w-1/2 px-3 py-2 border border-[#cbd5e1] dark:border-[#2b3b55] text-[#475569] dark:text-[#cbd5e1] hover:bg-[#f1f5f9] dark:hover:bg-[#162032] rounded-xl text-xs font-bold transition-colors cursor-pointer">Batal</button>
      <button type="submit" class="w-1/2 px-3 py-2 text-white rounded-xl text-xs font-bold transition-colors shadow-xs cursor-pointer"
        :class="$store.kb.confirm?.danger ? 'bg-rose-600 hover:bg-rose-700' : 'bg-[#034694] hover:bg-[#023470]'" x-text="$store.kb.confirm?.confirmText"></button>
    </div>
  </form>
</dialog>

<div x-data popover="manual" aria-live="polite"
  x-effect="$el.matches(':popover-open') && $el.hidePopover(); $store.kb.toasts.length && $el.showPopover()"
  class="fixed top-20 right-4 left-auto bottom-auto m-0 p-0 border-0 bg-transparent overflow-visible w-[calc(100vw-2rem)] max-w-sm space-y-2">
  <template x-for="t in $store.kb.toasts" :key="t.id">
    <div class="bg-white dark:bg-[#162032] border rounded-xl shadow-2xl p-3.5 flex items-start gap-3 animate-in slide-in-from-top-3 fade-in duration-200"
      :class="t.type === 'error' ? 'border-rose-300 dark:border-rose-900/60' : 'border-emerald-300 dark:border-emerald-900/60'"
      :role="t.type === 'error' ? 'alert' : 'status'">
      <div class="p-1.5 rounded-lg shrink-0" :class="t.type === 'error' ? 'bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400' : 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400'">
        <span x-show="t.type === 'error'" class="contents"><x-icon name="alert-circle" class="w-4 h-4" /></span>
        <span x-show="t.type !== 'error'" class="contents"><x-icon name="check-circle-2" class="w-4 h-4" /></span>
      </div>
      <div class="flex-1 min-w-0 text-xs font-semibold text-[#0f172a] dark:text-white leading-relaxed pt-0.5" x-text="t.message"></div>
      <button type="button" @click="$store.kb.toasts = $store.kb.toasts.filter((x) => x.id !== t.id)" aria-label="Tutup notifikasi"
        class="text-[#94a3b8] hover:text-[#0f172a] dark:hover:text-white p-1 cursor-pointer"><x-icon name="x" class="w-3.5 h-3.5" /></button>
    </div>
  </template>
</div>
