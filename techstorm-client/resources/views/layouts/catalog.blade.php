<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description"
        content="TechStorm - Votre boutique e-commerce futuriste spécialisée dans les technologies innovantes." />
    <meta name="author" content="Zenbx" />
    <title>@yield('title', 'TechStorm')</title>

    @vite(['ressources/js/app.js', 'resources/css/Cathalogue_css/header-footer.css', 'resources/css/Cathalogue_css/produit.css', 'resources/css/Cathalogue_css/quick-view.css', 'resources/css/Cathalogue_css/catalogue.css'])

    @stack('styles')

    <link rel="icon" href="favicon.ico" />
</head>

<body>

    <header class="header">
        <a href="{{ url('/') }}" class="logo">TechStorm</a>

        <button class="menu-toggle" aria-label="Ouvrir le menu">
            ☰
        </button>

        <nav>
            <ul class="nav-links">
                <li><a href="{{ url('/') }}">Accueil</a></li>
                <li><a href="{{ url('/about') }}">À propos</a></li>
                <li><a href="{{ url('/catalogue') }}">Catalogue</a></li>
                <li><a href="{{ url('/contact') }}">Contact</a></li>
            </ul>
        </nav>

        <div class="buttons">
            <button class="connexion-button" onclick="window.location.href='{{ url('/account') }}'">Mon Compte</button>
            <button class="pay-button" onclick="window.location.href='{{ url('/panier') }}'">Panier <span
                    id="cart-count" aria-live="polite" class="cart-count">0</span></button>
        </div>
    </header>

    <!-- CONTENU CATALOGUE -->
    <main class="hero">
        @yield('content')
    </main>

    <!-- FOOTER  -->
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

    <!-- Quick view modal -->
    <div id="quickview" class="modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="modal-content" role="document">
            <button class="modal-close" aria-label="Fermer la fenêtre">✕</button>
            <div class="modal-body" id="quickview-body">
                <!-- contenu dynamique -->
            </div>
        </div>
    </div>


    <!-- SCRIPT: données + fonctionnalité catalogue -->
    @stack('scripts')
    <!--script src="{{ asset('js/catalogue.js') }}" ></script>-->
</body>

</html>
