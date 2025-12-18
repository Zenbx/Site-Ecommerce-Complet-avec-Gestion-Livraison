<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="TechStorm - Authentification" />
    <title>@yield('title', 'TechStorm')</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Only include app.js if needed for global scripts, but avoid global styles if they conflict.
         However, typically we want the basic reset/fonts.
         For now, I'll keep app.js but rely on the specific page styles pushed below. -->
    @vite(['resources/js/app.js'])
    @stack('styles')

    <link rel="icon" href="{{ asset('favicon.ico') }}" />
</head>

<body>
    <!-- Main content only, no header/footer -->
    <main style="height: 100vh; width: 100vw;">
        @yield('content')
    </main>
    @stack('scripts')
</body>

</html>
