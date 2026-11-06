{{-- Port of components/chat/MiniChatWidget.tsx (logic: alpine/chat.js 'miniChat') --}}
@php($dot = 'w-1.5 h-1.5 rounded-full bg-[#034694] dark:bg-blue-400 animate-bounce')
<div x-data="miniChat" class="contents">
  <button x-show="!$store.kb.isMiniChatOpen" type="button" @click="openFab()" title="Buka Chat Tim"
    class="fixed bottom-6 right-6 z-50 w-14 h-14 rounded-full bg-[#034694] hover:bg-[#023470] text-white shadow-xl hover:shadow-2xl flex items-center justify-center transition-transform hover:scale-105 active:scale-95 cursor-pointer shrink-0">
    <x-icon name="message-square" class="w-6 h-6 text-white" />
    <span x-show="$store.kb.unreadMessagesCount > 0" x-cloak x-text="$store.kb.unreadMessagesCount > 99 ? '99+' : $store.kb.unreadMessagesCount"
      class="absolute -top-1 -right-1 min-w-[22px] h-[22px] px-1 bg-rose-500 text-white text-[length:calc(11px+var(--fs-up))] font-black rounded-full flex items-center justify-center ring-2 ring-white dark:ring-[#111726] shadow-md animate-pulse pointer-events-none"></span>
  </button>

  <div x-show="$store.kb.isMiniChatOpen" x-cloak
    class="fixed bottom-6 right-6 z-50 w-md max-w-[calc(100vw-3rem)] bg-white dark:bg-[#111726] rounded-xl border border-[#e2e8f0] dark:border-[#1e2a3f] shadow-2xl flex flex-col overflow-hidden transition-all duration-200"
    :class="isMinimized ? 'h-12' : 'h-96'">
    <div class="h-12 px-3.5 bg-[#034694] text-white flex items-center justify-between shrink-0 select-none">
      <div class="flex items-center gap-2 truncate">
        <div class="relative">
          <img :src="$store.kb.miniChatUser?.avatar_url || ''" :alt="$store.kb.miniChatUser?.name" class="w-7 h-7 rounded-full object-cover ring-1 ring-white/50">
          <span class="absolute bottom-0 right-0 w-2 h-2 rounded-full bg-emerald-400 ring-1 ring-[#034694]"></span>
        </div>
        <div class="truncate">
          <div class="text-xs font-bold truncate leading-tight" x-text="$store.kb.miniChatUser?.name"></div>
          <div class="text-[length:calc(10px+var(--fs-up))] text-blue-200 truncate leading-tight">
            <span x-show="partnerTyping" class="text-amber-300 font-semibold animate-pulse">Sedang mengetik...</span>
            <span x-show="!partnerTyping">Direct Message</span>
          </div>
        </div>
      </div>
      <div class="flex items-center gap-1">
        <button @click="isMinimized = !isMinimized" class="p-1 hover:bg-white/10 rounded text-blue-200 hover:text-white" :title="isMinimized ? 'Expand' : 'Minimize'">
          <span x-show="isMinimized" class="contents"><x-icon name="chevron-up" class="w-4 h-4" /></span>
          <span x-show="!isMinimized" class="contents"><x-icon name="minus" class="w-4 h-4" /></span>
        </button>
        <button @click="$store.kb.closeMiniChat()" class="p-1 hover:bg-white/10 rounded text-blue-200 hover:text-white" title="Close">
          <x-icon name="x" class="w-4 h-4" />
        </button>
      </div>
    </div>

    <template x-if="!isMinimized">
      <div class="contents">
        <div class="px-3 py-1.5 bg-[#f8fafc] dark:bg-[#162032] border-b border-[#e2e8f0] dark:border-[#1e2a3f] flex items-center gap-1.5 overflow-x-auto text-[length:calc(11px+var(--fs-up))]">
          <span class="text-[#94a3b8] font-medium shrink-0">Chat with:</span>
          <template x-for="u in otherUsers" :key="u.id">
            <button @click="$store.kb.openMiniChatWith(u)" x-text="u.name.split(' ')[0]" class="px-2 py-0.5 rounded text-[length:calc(10px+var(--fs-up))] font-semibold transition-colors shrink-0"
              :class="$store.kb.miniChatUser?.id === u.id ? 'bg-[#034694] text-white' : 'bg-white dark:bg-[#1e2a3f] text-[#475569] dark:text-[#cbd5e1] border border-[#e2e8f0] dark:border-[#2b3a52] hover:bg-[#e9f2fc] dark:hover:bg-[#27354d]'"></button>
          </template>
        </div>

        <div class="flex-1 p-3 overflow-y-auto space-y-2 bg-[#f8fafc]/50 dark:bg-[#0c1220] text-xs">
          <div x-show="!messages.length" class="h-full flex items-center justify-center text-[#94a3b8] text-[length:calc(11px+var(--fs-up))] text-center p-4"
            x-text="'Send a message to start collaborating with ' + ($store.kb.miniChatUser?.name ?? '') + '.'"></div>
          <template x-for="m in messages" :key="m.id">
            <div class="flex flex-col" :class="m.sender_id === $store.kb.activeUser?.id ? 'items-end' : 'items-start'">
              <div class="max-w-[80%] rounded-md px-3 py-1.5 text-xs leading-relaxed shadow-2xs"
                :class="m.sender_id === $store.kb.activeUser?.id ? 'bg-[#034694] text-white rounded-br-none' : 'bg-white dark:bg-[#1a2436] border border-[#e2e8f0] dark:border-[#2b3b55] text-[#0f172a] dark:text-white rounded-bl-none'">
                <template x-if="m.image_url">
                  <div class="mb-1 rounded overflow-hidden max-w-[200px]">
                    <a :href="m.image_url" target="_blank" rel="noopener noreferrer">
                      <img :src="m.image_url" alt="Lampiran pesan" class="w-full max-h-36 object-cover hover:opacity-90 transition-opacity cursor-pointer rounded">
                    </a>
                  </div>
                </template>
                <template x-if="m.content && m.content !== '[Gambar]'"><p x-text="m.content"></p></template>
              </div>
              <span class="text-[length:calc(10px+var(--fs-up))] text-[#94a3b8] mt-0.5 px-1 flex items-center gap-1">
                <span x-text="time(m)"></span>
                <span x-show="m.sender_id === $store.kb.activeUser?.id" :title="m.is_read ? 'Sudah dibaca' : 'Terkirim (belum dibaca)'" class="inline-flex"
                  :class="m.is_read ? 'text-[#38bdf8]' : 'text-slate-400 dark:text-slate-500'">
                  <x-icon name="check-check" class="w-3 h-3" />
                </span>
              </span>
            </div>
          </template>
          <div x-show="partnerTyping" class="flex items-center gap-1.5 text-[length:calc(11px+var(--fs-up))] text-[#64748b] dark:text-[#94a3b8] italic px-1 py-1 animate-pulse">
            <span class="font-semibold text-[#034694] dark:text-blue-400 not-italic" x-text="$store.kb.miniChatUser?.name"></span>
            <span>sedang mengetik</span>
            <span class="inline-flex gap-0.5 ml-0.5">
              <span class="{{ $dot }}" style="animation-delay: 0ms"></span>
              <span class="{{ $dot }}" style="animation-delay: 150ms"></span>
              <span class="{{ $dot }}" style="animation-delay: 300ms"></span>
            </span>
          </div>
          <div x-ref="end"></div>
        </div>

        <div class="border-t border-[#e2e8f0] dark:border-[#1e2a3f] bg-white dark:bg-[#111726] p-2">
          <div x-show="image" class="flex items-center gap-1.5 mb-1.5 p-1 bg-[#f1f5f9] dark:bg-[#162032] rounded border border-[#e2e8f0] dark:border-[#2b3b55] w-fit">
            <img :src="image?.preview" alt="Pratinjau" class="w-7 h-7 object-cover rounded">
            <span class="text-[length:calc(10px+var(--fs-up))] text-[#475569] dark:text-[#cbd5e1] max-w-[120px] truncate" x-text="image?.file.name"></span>
            <button type="button" @click="clearImage()" class="text-rose-500 hover:text-rose-700 text-xs px-1 font-bold cursor-pointer">×</button>
          </div>
          <form @submit.prevent="send()" class="flex items-center gap-1.5">
            <input x-ref="file" type="file" accept=".png,.jpg,.jpeg,.webp,.gif" class="hidden" @change="selectImage($event)">
            <button type="button" @click="$refs.file.click()" title="Kirim Gambar"
              class="p-1.5 text-[#64748b] dark:text-[#94a3b8] hover:text-[#034694] dark:hover:text-blue-400 hover:bg-[#f1f5f9] dark:hover:bg-[#1e2a3f] rounded border border-[#e2e8f0] dark:border-[#2b3a52] transition-colors cursor-pointer shrink-0">
              <x-icon name="image" class="w-3.5 h-3.5" />
            </button>
            <input type="text" :value="inputText" @input="onInput($event.target.value)" placeholder="Tulis pesan..."
              class="flex-1 px-2.5 py-1.5 border border-[#e2e8f0] dark:border-[#2b3a52] rounded-md text-xs bg-white dark:bg-[#162032] text-[#0f172a] dark:text-white placeholder-[#94a3b8] focus:outline-none focus:border-[#034694] dark:focus:border-blue-500 font-medium">
            <button type="submit" :disabled="(!inputText.trim() && !image) || uploading"
              class="p-1.5 bg-[#034694] hover:bg-[#023470] disabled:bg-[#cbd5e1] dark:disabled:bg-[#1e2a3f] text-white rounded-md transition-colors cursor-pointer shrink-0">
              <span x-show="uploading" class="contents"><x-icon name="loader-2" class="w-3.5 h-3.5 animate-spin" /></span>
              <span x-show="!uploading" class="contents"><x-icon name="send" class="w-3.5 h-3.5" /></span>
            </button>
          </form>
        </div>
      </div>
    </template>
  </div>
</div>
