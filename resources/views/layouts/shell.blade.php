{{-- Page chrome: sidebar + header + global modals (was AppShell / Home / Messages wrappers) --}}
@extends('layouts.app')

@section('body')
<div x-data x-init="$store.kb.fetchUsers(); $store.kb.fetchNotifications(); @yield('init')"
  class="flex h-screen @yield('bg', 'bg-[#f1f5f9] dark:bg-[#090e17]') text-[#0f172a] dark:text-[#f8fafc] overflow-hidden transition-colors">
  @include('partials.sidebar')
  <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
    @include('partials.header')
    @yield('main')
  </div>
  @yield('extra')
  @include('partials.modals.user-management')
  @include('partials.modals.settings')
  @include('partials.modals.shortcuts-help')
  @include('partials.modals.create-unit')
  @include('partials.modals.master-unit')
  @include('partials.feedback')
</div>
@endsection
