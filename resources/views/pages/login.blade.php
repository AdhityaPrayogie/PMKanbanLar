@extends('layouts.app')

@section('body')
@php($input = 'w-full pl-9 pr-3 py-2 border border-[#e2e8f0] rounded-md text-xs text-[#0f172a] focus:outline-none focus:border-[#034694] focus:ring-1 focus:ring-[#034694]')
@php($tabCls = 'flex-1 py-3 text-xs font-bold flex items-center justify-center gap-2 border-b-2 transition-all')
@php($btn = 'w-full py-2.5 bg-[#034694] hover:bg-[#023470] disabled:bg-[#94a3b8] text-white font-semibold text-xs rounded-md shadow-xs transition-colors flex items-center justify-center gap-2')
<div x-data="loginPage" class="min-h-screen bg-[#f5f6f8] flex flex-col items-center justify-center p-4">
  <div class="w-full max-w-md bg-white rounded-xl border border-[#e2e8f0] shadow-xl overflow-hidden">
    <div class="bg-[#034694] p-6 text-white text-center">
      <div class="w-12 h-12 bg-white/10 rounded-lg flex items-center justify-center mx-auto mb-3 backdrop-blur-xs ring-1 ring-white/20">
        <x-icon name="kanban" class="w-6 h-6 text-white" />
      </div>
      <h1 class="text-xl font-bold tracking-tight">PM Kanban Platform</h1>
      <p class="text-xs text-blue-100 mt-1">Enterprise Modular Kanban &amp; Team Collaboration</p>
    </div>

    <div class="flex border-b border-[#e2e8f0] bg-[#f8fafc]">
      <button @click="tab = 'login'; errorMsg = ''" class="{{ $tabCls }}"
        :class="tab === 'login' ? 'border-[#034694] text-[#034694] bg-white' : 'border-transparent text-[#64748b] hover:text-[#0f172a]'">
        <x-icon name="log-in" class="w-4 h-4" /><span>Masuk</span>
      </button>
      <button @click="tab = 'register'; errorMsg = ''" class="{{ $tabCls }}"
        :class="tab === 'register' ? 'border-[#034694] text-[#034694] bg-white' : 'border-transparent text-[#64748b] hover:text-[#0f172a]'">
        <x-icon name="user-plus" class="w-4 h-4" /><span>Daftar Akun</span>
      </button>
    </div>

    <div class="p-6 pb-0">
      <div x-show="errorMsg" x-cloak class="p-3 mb-4 rounded-md bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-start gap-2.5">
        <x-icon name="alert-circle" class="w-4 h-4 shrink-0 mt-0.5" /><span x-text="errorMsg"></span>
      </div>
      <div x-show="successMsg" x-cloak class="p-3 mb-4 rounded-md bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-start gap-2.5">
        <x-icon name="check-circle-2" class="w-4 h-4 shrink-0 mt-0.5 text-emerald-600" /><span x-text="successMsg"></span>
      </div>
    </div>

    <div class="p-6 pt-2">
      <form x-show="tab === 'login'" @submit.prevent="login" class="space-y-4">
        <div>
          <label class="block text-xs font-semibold text-[#0f172a] mb-1.5">Alamat Email</label>
          <div class="relative">
            <x-icon name="mail" class="w-4 h-4 text-[#94a3b8] absolute left-3 top-1/2 -translate-y-1/2" />
            <input type="email" required x-model="email" placeholder="nama@perusahaan.com" class="{{ $input }}">
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold text-[#0f172a] mb-1.5">Password</label>
          <div class="relative">
            <x-icon name="lock" class="w-4 h-4 text-[#94a3b8] absolute left-3 top-1/2 -translate-y-1/2" />
            <input type="password" required x-model="password" placeholder="••••••••" class="{{ $input }}">
          </div>
        </div>
        <button type="submit" :disabled="loading" class="{{ $btn }}">
          <x-icon name="log-in" class="w-4 h-4" /><span x-text="loading ? 'Memverifikasi...' : 'Masuk ke Workspace'"></span>
        </button>

        <div class="pt-4 border-t border-[#e2e8f0] text-xs">
          <span class="text-[length:calc(11px+var(--fs-up))] font-bold text-[#64748b] uppercase tracking-wider block mb-2">Akun Demo (Klik untuk Isi):</span>
          <div class="grid grid-cols-2 gap-2">
            <button type="button" @click="fill('alex@example.com', 'admin123')" class="p-2 border border-[#e2e8f0] rounded bg-[#f8fafc] hover:bg-[#e9f2fc] text-left transition-colors">
              <div class="font-bold text-[#034694] text-[length:calc(11px+var(--fs-up))] flex items-center gap-1"><x-icon name="shield-check" class="w-3.5 h-3.5" />Admin (Alex)</div>
              <div class="text-[length:calc(10px+var(--fs-up))] text-[#64748b]">admin123</div>
            </button>
            <button type="button" @click="fill('sarah@example.com', 'user123')" class="p-2 border border-[#e2e8f0] rounded bg-[#f8fafc] hover:bg-[#e9f2fc] text-left transition-colors">
              <div class="font-bold text-[#475569] text-[length:calc(11px+var(--fs-up))] flex items-center gap-1"><x-icon name="user" class="w-3.5 h-3.5" />User (Sarah)</div>
              <div class="text-[length:calc(10px+var(--fs-up))] text-[#64748b]">user123</div>
            </button>
          </div>
        </div>
      </form>

      <form x-show="tab === 'register'" x-cloak @submit.prevent="register" class="space-y-4">
        <div>
          <label class="block text-xs font-semibold text-[#0f172a] mb-1.5">Nama Lengkap</label>
          <div class="relative">
            <x-icon name="user" class="w-4 h-4 text-[#94a3b8] absolute left-3 top-1/2 -translate-y-1/2" />
            <input type="text" required x-model="name" placeholder="Contoh: Pratama Wicaksana" class="{{ $input }}">
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold text-[#0f172a] mb-1.5">Alamat Email</label>
          <div class="relative">
            <x-icon name="mail" class="w-4 h-4 text-[#94a3b8] absolute left-3 top-1/2 -translate-y-1/2" />
            <input type="email" required x-model="email" placeholder="nama@perusahaan.com" class="{{ $input }}">
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold text-[#0f172a] mb-1.5">Password</label>
          <div class="relative">
            <x-icon name="lock" class="w-4 h-4 text-[#94a3b8] absolute left-3 top-1/2 -translate-y-1/2" />
            <input type="password" required minlength="6" x-model="password" placeholder="Minimal 6 karakter" class="{{ $input }}">
          </div>
        </div>
        <div class="p-3 rounded bg-amber-50 border border-amber-200 text-[length:calc(11px+var(--fs-up))] text-amber-800 leading-relaxed">
          <span class="font-bold">Perhatian:</span> Akun yang baru didaftarkan akan berstatus <strong>Menunggu Persetujuan (Pending ACC)</strong> dari Administrator sebelum dapat masuk.
        </div>
        <button type="submit" :disabled="loading" class="{{ $btn }}">
          <x-icon name="user-plus" class="w-4 h-4" /><span x-text="loading ? 'Mendaftarkan...' : 'Daftar Sekarang'"></span>
        </button>
      </form>
    </div>
  </div>
</div>
@endsection
