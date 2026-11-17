<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Manajemen Akademik') }}</title>
        <script>
            if (localStorage.getItem('theme') === 'dark') document.documentElement.classList.add('dark');
            window.academicAppUser = {{ Illuminate\Support\Js::from(auth()->user()->only(['id', 'name', 'email', 'role', 'program_studi'])) }};
        </script>
        @vite(['resources/css/app.css', 'resources/js/spa.jsx'])
    </head>
    <body class="font-sans antialiased">
        <div id="academic-app"></div>
    </body>
</html>
