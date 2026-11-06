@extends('layouts.shell')

@section('bg', 'bg-[#f5f6f8] dark:bg-[#090d16]')
@section('init', '$store.kb.fetchBoardData();')

@section('main')
<main class="flex-1 flex overflow-hidden relative">
  <template x-if="$store.kb.activeView === 'hub'">@include('partials.boards-hub')</template>
  <template x-if="$store.kb.activeView === 'timeline'">@include('partials.timeline')</template>
  <template x-if="$store.kb.activeView === 'board'">@include('partials.kanban-board')</template>
</main>
@endsection

@section('extra')
  @include('partials.task-detail-sheet')
  @include('partials.create-task-modal')
  @include('partials.mini-chat')
@endsection
