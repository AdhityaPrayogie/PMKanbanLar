{{-- One series, one hue, columns anchored to the baseline. Hover/focus shows the value; click opens detail.
     $cols: name of a getter in the parent scope; $aria: Alpine expression; $pick: statement run with `c`; $tick: label every N. --}}
<figure role="group" :aria-label="{{ $aria }}"
  x-data="{ get cs() { return this.{{ $cols }} }, get max() { return Math.max(1, ...this.cs.map((c) => c.value)) } }">
  <div class="flex gap-2">
    <div class="flex flex-col justify-between text-[length:calc(11px+var(--fs-up))] text-[#64748b] h-36 pb-0.5 text-right w-5" aria-hidden="true">
      <span x-text="max"></span><span>0</span>
    </div>
    <div class="flex-1 min-w-0">
      <div class="relative h-36 flex items-end gap-0.5 border-b border-[#cbd5e1] dark:border-[#24334a]">
        <div class="absolute inset-x-0 top-0 border-t border-dashed border-[#e2e8f0] dark:border-[#1e2a3f]" aria-hidden="true"></div>
        <template x-for="c in cs" :key="c.key">
          <div class="contents">
            <template x-if="!c.value"><div class="flex-1"></div></template>
            <template x-if="c.value">
              <button type="button" @click="{{ $pick }}" :aria-label="c.tip + '. Lihat detail'"
                class="group relative flex-1 h-full flex items-end justify-center cursor-pointer rounded-t outline-none focus-visible:ring-2 focus-visible:ring-[#034694]">
                <div class="w-full max-w-8 rounded-t bg-[#034694] dark:bg-blue-400 group-hover:opacity-80 group-focus-visible:opacity-80"
                  :style="{ height: (c.value / max) * 100 + '%' }"></div>
                <div role="tooltip" class="pointer-events-none absolute bottom-full mb-1 hidden group-hover:block group-focus-visible:block whitespace-nowrap rounded-md bg-[#0f172a] text-white text-[length:calc(11px+var(--fs-up))] px-2 py-1 z-10"
                  x-text="c.tip + ' · klik untuk detail'"></div>
              </button>
            </template>
          </div>
        </template>
      </div>
      <div class="flex gap-0.5 text-[length:calc(11px+var(--fs-up))] text-[#64748b] mt-1" aria-hidden="true">
        <template x-for="(c, i) in cs" :key="c.key">
          <span class="flex-1 text-center truncate" x-text="i === 0 || (i + 1) % {{ $tick ?? 1 }} === 0 ? c.label : ''"></span>
        </template>
      </div>
    </div>
  </div>
</figure>
