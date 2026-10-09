<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>

<body>
    <main>
        {{ $slot }}
    </main>


    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        document.addEventListener('livewire:navigated', () => lucide.createIcons());

        document.addEventListener('livewire:init', () => {
            Livewire.hook('morphed', () => lucide.createIcons());
        });
    </script>

</body>

</html>