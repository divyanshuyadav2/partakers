<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Authentication Error</title>
    @vite(['resources/css/app.css'])
</head>

<body class="bg-[#021420] text-white min-h-screen flex items-center justify-center">
    <div class="max-w-md mx-auto text-center p-8">


        <div class="mt-8">
            <h1 class="text-3xl font-bold mb-4">Session Closed</h1>
            <p class="mb-6">Now you can close this window by clicking button.</p>
            <button onclick="handleClose()" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-md mr-4">
                Close Window
            </button>

            <script>
                function handleClose() {
                    // Clear everything
                    sessionStorage.clear();
                    localStorage.clear();

                    // Try close first
                    window.open('', '_self');
                    window.close();

                    // If that didn't work, go to blank
                    setTimeout(() => {
                        if (!window.closed) {
                            window.location.replace('about:blank');
                        }
                    }, 100);
                }
            </script>

</body>

</html>
