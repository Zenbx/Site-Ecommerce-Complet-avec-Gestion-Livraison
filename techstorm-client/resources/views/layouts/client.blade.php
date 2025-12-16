<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="TechStorm - Votre compte client" />
    <meta name="author" content="Zenbx" />
    <title>@yield('title') - TechStorm</title>
    @vite(['ressources/js/client', 'resources/css/Client_css/commande.css', 'resources/css/Client_css/global.css', 'resources/css/Client_css/header-footer-cc.css', 'resources/css/Client_css/infos_perso.css', 'resources/css/Client_css/profil-client.css', 'resources/css/Client_css/settings.css'])
</head>

<body>
    <!-- HEADER -->
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
            <button class="connexion-button" onclick="window.location.href='{{ url('/connexion') }}'">Se
                Connecter</button>
            <button class="pay-button">Acheter</button>
        </div>
    </header>

    <!-- CONTENU PRINCIPAL -->
    <main class="client-informations">
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
                    <a href="#">Accueil</a>
                    <a href="#">À propos</a>
                    <a href="#">Catalogue</a>
                    <a href="#">Contact</a>
                </div>
                <div class="footer-socials">
                    <a href="{{ url('www.facebook.com') }}"><img src="{{ asset('icones/facebook.svg') }}"
                            alt="Facebook" /></a>
                    <a href="{{ url('www.instagram.com') }}"><img src="{{ asset('icones/instagram.svg') }}"
                            alt="Instagram" /></a>
                    <a href="{{ url('www.linkedin.com') }}"><img src="{{ asset('icones/linkedin.svg') }}"
                            alt="LinkedIn" /></a>
                </div>
            </div>

            <div class="footer-newsletter">
                <p class="newsletter-text">Abonnez-vous à notre newsletter</p>
                <div class="newsletter-form">
                    <input type="email" placeholder="Votre email" class="newsletter-input" />
                    <button class="newsletter-button">S’abonner</button>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2025 TechStorm – Tous droits réservés.</p>
        </div>
    </footer>
    <script>
        const toggleBtn = document.querySelector(".menu-toggle");
        const nav = document.querySelector("header nav");

        toggleBtn.addEventListener("click", () => {
            nav.classList.toggle("active");
        });
    </script>

</body>

</html>
