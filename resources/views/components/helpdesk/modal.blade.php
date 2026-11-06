{{-- Native <dialog> (focus trap, Esc, backdrop from the browser). Use inside <template x-if>.
     title/close are Alpine expressions; extra attributes (e.g. x-data) land on the <dialog>. --}}
@props(['title', 'close', 'wide' => false])
<dialog {{ $attributes }} x-init="$el.showModal()" @close="{{ $close }}" :aria-label="{{ $title }}"
  class="m-auto w-[calc(100%-2rem)] rounded-xl border border-[#cbd5e1] dark:border-[#1e2a3f] bg-white dark:bg-[#111726] text-[#0f172a] dark:text-[#f8fafc] p-0 shadow-xl backdrop:bg-slate-950/50 {{ $wide ? 'max-w-3xl' : 'max-w-lg' }}">
  <div class="flex items-center justify-between px-5 py-3 border-b border-[#cbd5e1] dark:border-[#1e2a3f]">
    <h2 class="text-base font-bold" x-text="{{ $title }}"></h2>
    <button type="button" @click="$el.closest('dialog').close()" class="p-1 rounded hover:bg-[#f1f5f9] dark:hover:bg-[#1e2a3f]" aria-label="Tutup">
      <x-icon name="x" class="w-4 h-4" />
    </button>
  </div>
  <div class="p-5 max-h-[75vh] overflow-y-auto">{{ $slot }}</div>
</dialog>
