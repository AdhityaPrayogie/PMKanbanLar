{{-- Port of pages/Messages.tsx split-pane chat (logic: alpine/chat.js 'messagesPage') --}}
@php($dot = 'w-1.5 h-1.5 rounded-full bg-[#034694] dark:bg-blue-400 animate-bounce')
@php($amberDot = 'w-1.5 h-1.5 rounded-full bg-amber-500 animate-bounce')
<div x-data="messagesPage" class="flex-1 flex overflow-hidden p-3 sm:p-6 gap-4 sm:gap-6">
  <div class="w-full md:w-80 bg-white dark:bg-[#111726] border border-[#cbd5e1] dark:border-[#1e2a3f] rounded-xl shadow-xs flex-col overflow-hidden transition-colors"
    :class="showMobileChat ? 'hidden md:flex' : 'flex'">
    <div class="p-4 border-b border-[#cbd5e1] dark:border-[#1e2a3f] bg-[#f8fafc] dark:bg-[#131b2e]">
      <h2 class="font-extrabold text-sm text-[#0f172a] dark:text-white mb-2.5 flex items-center gap-2">
        <x-icon name="message-square" class="w-4 h-4 text-[#034694] dark:text-blue-400" />
        Obrolan Tim &amp; Status Aktif
      </h2>
      <div class="relative">
        <x-icon name="search" class="w-3.5 h-3.5 text-[#94a3b8] absolute left-3 top-1/2 -translate-y-1/2" />
        <input type="text" x-model.debounce.200ms="contactSearch" placeholder="Cari rekan kerja..."
          class="w-full pl-8 pr-3 py-1.5 bg-white dark:bg-[#192336] border border-[#cbd5e1] dark:border-[#2b3b55] rounded-lg text-xs text-[#0f172a] dark:text-white placeholder-[#94a3b8] focus:outline-none focus:border-[#034694]">
      </div>
    </div>

    <div class="flex-1 overflow-y-auto divide-y divide-[#e2e8f0] dark:divide-[#1e2a3f]">
      <template x-for="contact in filteredContacts" :key="contact.id">
        <div @click="pick(contact)"
          class="p-3.5 flex items-center gap-3 cursor-pointer transition-colors"
          :class="partner?.id === contact.id ? 'bg-[#e0edfb] dark:bg-[#1e293b] border-l-4 border-[#034694] dark:border-blue-500' : 'hover:bg-[#f8fafc] dark:hover:bg-[#162032]'">
          <div class="relative shrink-0">
            <img :src="contact.avatar_url || ''" :alt="contact.name" class="w-10 h-10 rounded-full object-cover ring-1 ring-[#cbd5e1] dark:ring-[#2b3b55]">
            <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full ring-2 ring-white dark:ring-[#111726]" :title="status(contact).text"
              :class="status(contact).isOnline ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400 dark:bg-slate-600'"></span>
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between mb-0.5">
              <span class="font-bold text-xs text-[#0f172a] dark:text-white truncate" x-text="contact.name"></span>
            </div>
            <div class="flex items-center gap-1.5 text-[length:calc(11px+var(--fs-up))] truncate">
              <span x-show="status(contact).isOnline" class="text-emerald-600 dark:text-emerald-400 font-extrabold flex items-center gap-1">
                <x-icon name="circle" class="w-2 h-2 fill-emerald-500" />
                Sedang Online
              </span>
              <span x-show="!status(contact).isOnline" class="text-[#64748b] dark:text-[#94a3b8] flex items-center gap-1">
                <x-icon name="clock" class="w-2.5 h-2.5" />
                <span x-text="status(contact).text"></span>
              </span>
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>

  <div class="flex-1 bg-white dark:bg-[#111726] border border-[#cbd5e1] dark:border-[#1e2a3f] rounded-xl shadow-xs flex-col overflow-hidden transition-colors"
    :class="!showMobileChat ? 'hidden md:flex' : 'flex'">
    <template x-if="partner">
      <div class="contents">
        <div class="h-16 px-4 sm:px-6 border-b border-[#cbd5e1] dark:border-[#1e2a3f] flex items-center justify-between bg-[#f8fafc] dark:bg-[#131b2e] shrink-0">
          <div class="flex items-center gap-3">
            <button @click="showMobileChat = false" class="md:hidden p-1.5 rounded-lg text-[#475569] dark:text-[#94a3b8] hover:bg-[#e2e8f0] dark:hover:bg-[#1e293b]" title="Kembali ke kontak">
              <x-icon name="arrow-left" class="w-5 h-5" />
            </button>
            <div class="relative shrink-0">
              <img :src="partner.avatar_url || ''" :alt="partner.name" class="w-10 h-10 rounded-full object-cover ring-2 ring-[#034694]/20 dark:ring-blue-500/30">
              <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full ring-2 ring-white dark:ring-[#131b2e]"
                :class="partnerStatus?.isOnline ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400 dark:bg-slate-600'"></span>
            </div>
            <div>
              <h3 class="font-extrabold text-sm text-[#0f172a] dark:text-white leading-tight" x-text="partner.name"></h3>
              <div class="text-xs leading-tight mt-0.5">
                <span x-show="partnerTyping" class="text-amber-500 dark:text-amber-400 font-bold flex items-center gap-1.5 text-[length:calc(11px+var(--fs-up))] animate-pulse">
                  <span class="inline-flex gap-0.5">
                    <span class="{{ $amberDot }}" style="animation-delay: 0ms"></span>
                    <span class="{{ $amberDot }}" style="animation-delay: 150ms"></span>
                    <span class="{{ $amberDot }}" style="animation-delay: 300ms"></span>
                  </span>
                  Sedang mengetik...
                </span>
                <span x-show="!partnerTyping && partnerStatus?.isOnline" class="text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1 text-[length:calc(11px+var(--fs-up))]">
                  <x-icon name="circle" class="w-2 h-2 fill-emerald-500 animate-pulse" />
                  Sedang Online (Buka Aplikasi)
                </span>
                <span x-show="!partnerTyping && !partnerStatus?.isOnline" class="text-[#64748b] dark:text-[#94a3b8] flex items-center gap-1 text-[length:calc(11px+var(--fs-up))]">
                  <x-icon name="clock" class="w-3 h-3 text-[#94a3b8]" />
                  <span x-text="'Terakhir aktif: ' + (partnerStatus?.text ?? '')"></span>
                </span>
              </div>
            </div>
          </div>
          <div class="hidden sm:flex items-center gap-2">
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-blue-100 dark:bg-blue-950 text-[#034694] dark:text-blue-300 border border-blue-200 dark:border-blue-900">P2P Chat Terenkripsi</span>
          </div>
        </div>

        <div class="flex-1 p-4 sm:p-6 overflow-y-auto space-y-3 bg-[#f8fafc]/60 dark:bg-[#0d1424]/40">
          <div x-show="!messages.length" class="h-full flex flex-col items-center justify-center text-[#94a3b8] text-xs">
            <x-icon name="message-square" class="w-10 h-10 text-[#cbd5e1] dark:text-[#2b3a52] mb-2" />
            <span x-text="'Belum ada percakapan dengan ' + partner.name + '. Mulai kolaborasi sekarang!'"></span>
          </div>
          <template x-for="m in messages" :key="m.id">
            <div class="flex flex-col" :class="m.sender_id === $store.kb.activeUser?.id ? 'items-end' : 'items-start'">
              <div class="max-w-[85%] sm:max-w-[70%] rounded-xl px-4 py-2.5 text-xs leading-relaxed shadow-xs font-medium"
                :class="m.sender_id === $store.kb.activeUser?.id ? 'bg-[#034694] dark:bg-blue-600 text-white rounded-br-none' : 'bg-white dark:bg-[#162032] border border-[#cbd5e1] dark:border-[#2b3b55] text-[#0f172a] dark:text-white rounded-bl-none'">
                <template x-if="m.image_url">
                  <div class="mb-2 rounded-lg overflow-hidden border border-black/10 dark:border-white/10 max-w-xs sm:max-w-sm">
                    <a :href="m.image_url" target="_blank" rel="noopener noreferrer">
                      <img :src="m.image_url" alt="Lampiran pesan" class="w-full max-h-64 object-cover hover:opacity-90 transition-opacity cursor-pointer rounded-lg bg-black/5 dark:bg-white/5">
                    </a>
                  </div>
                </template>
                <template x-if="m.content && m.content !== '[Gambar]'"><p x-text="m.content"></p></template>
              </div>
              <span class="text-[length:calc(10px+var(--fs-up))] text-[#64748b] dark:text-[#94a3b8] mt-1 px-1 flex items-center gap-1">
                <span x-text="time(m)"></span>
                <span x-show="m.sender_id === $store.kb.activeUser?.id" :title="m.is_read ? 'Sudah dibaca' : 'Terkirim (belum dibaca)'" class="inline-flex"
                  :class="m.is_read ? 'text-[#38bdf8]' : 'text-slate-400 dark:text-slate-500'">
                  <x-icon name="check-check" class="w-3.5 h-3.5" />
                </span>
              </span>
            </div>
          </template>
          <div x-show="partnerTyping" class="flex items-center gap-2 text-xs text-[#64748b] dark:text-[#94a3b8] italic px-2 py-1 animate-pulse">
            <span class="font-semibold text-[#034694] dark:text-blue-400 not-italic" x-text="partner.name"></span>
            <span>sedang mengetik</span>
            <span class="inline-flex gap-0.5 ml-0.5">
              <span class="{{ $dot }}" style="animation-delay: 0ms"></span>
              <span class="{{ $dot }}" style="animation-delay: 150ms"></span>
              <span class="{{ $dot }}" style="animation-delay: 300ms"></span>
            </span>
          </div>
          <div x-ref="end"></div>
        </div>

        <div class="border-t border-[#cbd5e1] dark:border-[#1e2a3f] bg-white dark:bg-[#111726] p-3 sm:p-4">
          <div x-show="image" class="flex items-center gap-2 mb-2 p-1.5 bg-[#f1f5f9] dark:bg-[#162032] rounded-lg border border-[#cbd5e1] dark:border-[#2b3b55] w-fit">
            <img :src="image?.preview" alt="Pratinjau gambar" class="w-10 h-10 object-cover rounded-md border border-white/20">
            <div class="text-[length:calc(10px+var(--fs-up))] text-[#475569] dark:text-[#cbd5e1] max-w-[150px] truncate" x-text="image?.file.name"></div>
            <button type="button" @click="clearImage()" class="text-rose-500 hover:text-rose-700 text-xs px-1 font-bold cursor-pointer" title="Batalkan gambar">×</button>
          </div>
          <form @submit.prevent="send()" class="flex items-center gap-2 sm:gap-3">
            <input x-ref="file" type="file" accept=".png,.jpg,.jpeg,.webp,.gif" class="hidden" @change="selectImage($event)">
            <button type="button" @click="$refs.file.click()" title="Kirim Gambar"
              class="p-2.5 border border-[#cbd5e1] dark:border-[#2b3b55] hover:border-[#034694] dark:hover:border-blue-500 text-[#64748b] hover:text-[#034694] dark:text-[#94a3b8] dark:hover:text-blue-400 bg-[#f8fafc] dark:bg-[#162032] rounded-xl text-xs flex items-center justify-center transition-colors cursor-pointer shrink-0">
              <x-icon name="image" class="w-4 h-4" />
            </button>
            <input type="text" :value="inputText" @input="onInput($event.target.value)" :placeholder="'Kirim pesan atau gambar ke ' + partner.name + '...'"
              class="flex-1 px-4 py-2.5 border border-[#cbd5e1] dark:border-[#2b3b55] rounded-xl text-xs bg-[#f8fafc] dark:bg-[#162032] text-[#0f172a] dark:text-white placeholder-[#94a3b8] focus:outline-none focus:border-[#034694] dark:focus:border-blue-500 font-medium">
            <button type="submit" :disabled="(!inputText.trim() && !image) || uploading"
              class="px-4 py-2.5 bg-[#034694] hover:bg-[#023470] disabled:bg-[#cbd5e1] dark:disabled:bg-[#1e2a3f] text-white rounded-xl text-xs font-bold flex items-center gap-2 shadow-xs transition-colors shrink-0 cursor-pointer">
              <span x-show="uploading" class="contents"><x-icon name="loader-2" class="w-4 h-4 animate-spin" /></span>
              <span x-show="!uploading" class="contents"><x-icon name="send" class="w-4 h-4" /></span>
              <span class="hidden sm:inline">Kirim</span>
            </button>
          </form>
        </div>
      </div>
    </template>
    <div x-show="!partner" class="flex-1 flex items-center justify-center text-xs text-[#94a3b8]">
      Pilih salah satu kontak di sisi kiri untuk memulai percakapan.
    </div>
  </div>
</div>
