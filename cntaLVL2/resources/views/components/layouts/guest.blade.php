<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Contact Manager' }}</title>
    <!-- Fonts -->
    <!-- Flag Icons CSS (SVG-based flags) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icons/7.5.0/css/flag-icons.min.css"
        integrity="sha512-+WVTaUIzUw5LFzqIqXOT3JVAc5SrMuvHm230I9QAZa6s+QRk8NDPswbHo2miIZj3yiFyV9lAgzO1wVrjdoO4tw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    @livewireStyles
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body class="font-sans antialiased bg-[#021420]">

    <!-- This is where your Livewire component will be rendered -->
    <main>

        {{ $slot }}
    </main>

    @livewireScripts
    @stack('scripts')

<script>
    // Wait for the entire page content to be loaded before running the script
    document.addEventListener('DOMContentLoaded', function() {

        // 1. Select all input elements with the type "text"
        const textInputs = document.querySelectorAll('input[type="text"]');

        // 2. Loop through each selected input
        textInputs.forEach(function(input) {

            // 3. Add a 'blur' event listener to each one
            input.addEventListener('blur', function(event) {
                
                // 4. When the event fires, get the element and trim its value
                const element = event.target;
                element.value = element.value.trim();
            });
        });
    });
</script>
</body>

</html>
