<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description"
        content="TechStorm - Votre boutique e-commerce futuriste spécialisée dans les technologies innovantes." />
    <meta name="author" content="Override" />

    <title>@yield('title', 'TechStorm - Site de E-commerce')</title>

    <!-- LIAISON DES CSS ET JS AVEC vite -->
    <!-- Vite va lire resources/js/app.js qui lui-même importe tous les CSS -->
    <!-- LIAISON DES CSS ET JS AVEC vite (Optimisé) -->

    @vite(['resources/js/auth-guard.js', 'resources/css/Acceuil_css/header-footer.css', 'resources/js/app.js'])

    @stack('styles')

    <!-- LIAISON DU FAVICON AVEC asset() (le favicon est un asset public) -->
    <link rel="icon" href="{{ asset('favicon.ico') }}" />
</head>

<body>
    <!-- HEADER -->
    <header class="header">
        <a href="{{ url('/') }}" class="logo">TechStorm</a>

        <!-- Bouton Menu Mobile -->
        <button class="menu-toggle" aria-label="Menu">
            ☰
        </button>

        <nav>
            <ul class="nav-links">
                <li><a href="{{ url('/') }}">Acceuil</a></li>
                <li><a href="{{ url('/about') }}">À propos</a></li>
                <li><a href="{{ url('/catalogue') }}">Catalogue</a></li>
                <li><a href="{{ url('/contact') }}">Contact</a></li>
            </ul>
        </nav>
        <div class="buttons">
            <button class="connexion-button" onclick="window.location.href='{{ url('/connexion') }}'">Se
                Connecter</button>
            <button class="pay-button">Acheter</button>
        </div>
    </header>

    <!-- CONTENU PRINCIPAL -->
    <main>
        @yield('content')
    </main>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="footer-top">
            <div class="footer-left">
                <h2 class="footer-logo">TechStorm</h2>
                <p class="footer-slogan">Le futur entre vos mains</p>
            </div>
            <div class="footer-links-socials">
                <div class="footer-links">
                    <a href="{{ url('/') }}">Accueil</a>
                    <a href="{{ url('/about') }}">À propos</a>
                    <a href="{{ url('/catalogue') }}">Catalogue</a>
                    <a href="{{ url('/contact') }}">Contact</a>
                </div>
                <div class="footer-socials">
                    <!-- LIAISON DES ICONES AVEC asset() -->
                    <a href="#"><img src="{{ asset('images/icones/facebook.svg') }}" alt="Facebook" /></a>
                    <a href="#"><img src="{{ asset('images/icones/instagram.svg') }}" alt="Instagram" /></a>
                    <a href="#"><img src="{{ asset('images/icones/linkedin.svg') }}" alt="LinkedIn" /></a>
                </div>
            </div>
            <div class="footer-newsletter">
                <p class="newsletter-text">Abonnez-vous à notre newsletter</p>
                <div class="newsletter-form">
                    <input type="email" placeholder="Votre email" class="newsletter-input" />
                    <button class="newsletter-button">S'abonner</button>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2025 TechStorm – Tous droits réservés.</p>
        </div>
    </footer>

    <!-- Script pour le menu mobile -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.querySelector('.menu-toggle');
            const nav = document.querySelector('nav');

            if (menuToggle && nav) {
                menuToggle.addEventListener('click', function() {
                    nav.classList.toggle('active');
                    menuToggle.innerHTML = nav.classList.contains('active') ? '✕' : '☰';
                });
            }
        });
    </script>

</body>

</html>
