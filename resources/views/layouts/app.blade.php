<!DOCTYPE html>
<html lang="en" class="h-full antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PM Kanban | Modular Project Management</title>
    <meta name="description" content="Enterprise Modular Kanban & Timeline Platform powered by Laravel and MySQL">
    <link rel="icon" href="/favicon.ico">
    {{-- Theme before first paint (was initTheme in React) --}}
    <script>try{var s=localStorage.getItem('theme');if(s==='dark'||(!s&&matchMedia('(prefers-color-scheme: dark)').matches))document.documentElement.classList.add('dark')}catch(e){}</script>
    <script>window.__USER__ = @json($user ?? null);</script>
    <style>[x-cloak]{display:none!important}</style>
    @vite('resources/js/app.js')
</head>
<body class="min-h-full flex flex-col">
    @yield('body')
</body>
</html>
