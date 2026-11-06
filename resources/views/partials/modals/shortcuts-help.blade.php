{{-- Port of components/help/ShortcutsHelpModal.tsx --}}
@php
  $shortcuts = [
    ['Ctrl + K  atau  /', 'Fokus pada pencarian cepat (Debounce Search)', 'search'],
    ['N', 'Buka modal untuk membuat Tugas Baru', 'plus'],
    ['B', 'Beralih ke Tampilan Papan Kanban', 'kanban'],
    ['T', 'Beralih ke Tampilan Timeline & Sub-tasks (Gantt)', 'clock'],
    ['M', 'Buka Obrolan Tim (Private Chat)', 'message-square'],
    ['D', 'Ganti Mode Tampilan (Light / Dark Mode)', 'sparkles'],
    ['?', 'Buka Bantuan & Panduan Shortcut ini', 'help-circle'],
    ['Esc', 'Tutup panel atau modal yang sedang aktif', 'x'],
  ];
  $card = 'p-3 rounded-lg border border-[#e2e8f0] dark:border-[#1e293b] bg-white dark:bg-[#111726]';
  $h4 = 'text-xs font-bold uppercase tracking-wider text-[#034694] dark:text-blue-400 mb-3 flex items-center gap-1.5';
@endphp
<div x-data x-show="$store.kb.isHelpOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs animate-in fade-in">
  <div class="bg-white dark:bg-[#111726] rounded-xl border border-[#e2e8f0] dark:border-[#1e293b] shadow-2xl w-full max-w-2xl overflow-hidden animate-in zoom-in-95 duration-150 flex flex-col max-h-[85vh]">
    <div class="px-6 py-4 border-b border-[#e2e8f0] dark:border-[#1e293b] flex items-center justify-between bg-[#f8fafc] dark:bg-[#0d1322]">
      <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-md bg-[#034694] text-white flex items-center justify-center">
          <x-icon name="keyboard" class="w-4 h-4" />
        </div>
        <div>
          <h3 class="text-sm font-bold text-[#0f172a] dark:text-white">Panduan Pintasan Keyboard &amp; Tutorial</h3>
          <p class="text-xs text-[#64748b] dark:text-[#94a3b8]">Bekerja lebih cepat menggunakan navigasi keyboard dan fitur modular</p>
        </div>
      </div>
      <button @click="$store.kb.closeHelp()" class="p-1.5 text-[#64748b] hover:text-[#0f172a] dark:hover:text-white rounded hover:bg-[#e2e8f0] dark:hover:bg-[#1e293b]">
        <x-icon name="x" class="w-4 h-4" />
      </button>
    </div>

    <div class="flex-1 overflow-y-auto p-6 space-y-6">
      <div>
        <h4 class="{{ $h4 }}"><x-icon name="keyboard" class="w-3.5 h-3.5" />Pintasan Keyboard Utama</h4>
        <div class="grid grid-cols-2 gap-2.5">
          @foreach ($shortcuts as [$key, $desc, $icon])
            <div class="p-2.5 rounded-lg border border-[#e2e8f0] dark:border-[#1e293b] bg-[#f8fafc] dark:bg-[#0d1322] flex items-center justify-between text-xs">
              <div class="flex items-center gap-2 truncate pr-2">
                <span class="text-[#034694] dark:text-blue-400 shrink-0"><x-icon :name="$icon" class="w-3.5 h-3.5" /></span>
                <span class="text-[#334155] dark:text-[#cbd5e1] truncate">{{ $desc }}</span>
              </div>
              <kbd class="px-2 py-0.5 rounded font-mono text-[length:calc(10px+var(--fs-up))] font-bold bg-white dark:bg-[#1e293b] border border-[#cbd5e1] dark:border-[#334155] text-[#0f172a] dark:text-white shrink-0 shadow-2xs">{{ $key }}</kbd>
            </div>
          @endforeach
        </div>
      </div>

      <div class="border-t border-[#e2e8f0] dark:border-[#1e293b] pt-4">
        <h4 class="{{ $h4 }}"><x-icon name="layers" class="w-3.5 h-3.5" />Tutorial Alur Kerja Sistem</h4>
        <div class="space-y-3 text-xs text-[#475569] dark:text-[#94a3b8] leading-relaxed">
          <div class="{{ $card }}">
            <div class="font-bold text-[#0f172a] dark:text-white mb-1">1. Drag-and-Drop &amp; Manajemen Kolom</div>
            Tarik kartu tugas ke kolom lain (To Do, In Progress, Review, Done) untuk memperbarui status secara otomatis. Sistem akan langsung mengirimkan notifikasi perubahan ke pemilik tugas.
          </div>
          <div class="{{ $card }}">
            <div class="font-bold text-[#0f172a] dark:text-white mb-1">2. Hierarki Sub-tasks pada Timeline</div>
            Pecah tugas besar menjadi sub-tasks. Buka tampilan Timeline untuk melihat visualisasi Gantt bar dan lakukan expand/collapse baris sub-task yang menjorok secara hierarkis.
          </div>
          <div class="{{ $card }}">
            <div class="font-bold text-[#0f172a] dark:text-white mb-1">3. Otomatisasi Persetujuan Admin (Approval System)</div>
            Setiap pengguna baru yang mendaftar harus disetujui (ACC) oleh Administrator melalui menu <strong>Kelola Pengguna</strong> sebelum dapat login ke dalam aplikasi.
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
