<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description"
        content="TechStorm - Votre boutique e-commerce futuriste spécialisée dans les technologies innovantes." />
    <meta name="author" content="Zenbx" />
    <title>@yield('title', 'TechStorm')</title>
    @vite(['resources/js/app.js', 'resources/css/Panier_css/main.css', 'resources/css/Panier_css/resume.css', 'resources/css/Panier_css/style.css'])
    <link rel="icon" href="favicon.ico" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <!-- HEADER -->
    <header class="header">
        <a href="{{ url('/') }}" class="logo">TechStorm</a>
        <span class="compteur">(3)</span>
        <div class="buttons">
            <label class="location" for="locationDialog">
                <i class="fas fa-location-dot"></i>
                <input type="checkbox" id="locationDialog">
                <div class="dialog-overlay">
                    <div class="dialog location-dialog">
                        <label for="locationDialog" class="dialog-close">x</label>
                        <h3 class="dialog-title">Où souhaiteriez vous vous faire livrer?</h3>
                        <div class="dialog-section">
                            <label>Pays</label>
                            <select class="location-select">
                                <option value="">selectionnez un Pays</option>
                                <option value="cameroun"> Cameroun</option>
                                <option value="France">France</option>
                                <option value="senegal">Senegal</option>
                                <option value="cote-ivoire">Côte d'ivoire</option>
                                <option value="gabon">Gabon</option>
                                <option value="buurkina">Burkina Faso</option>
                                <option value="mali">Mali</option>
                                <option value="benin">Bénin</option>
                                <option value="togo">Togo</option>
                            </select>
                        </div>
                        <div class="dialog-section">
                            <label> ville </label>
                            <select class="location-select">
                                <option value="">Sélectionnez une ville</option>
                                <option value="yaounde">Yaoundé</option>
                                <option value="douala">Douala</option>
                                <option value="paris">Paris</option>
                                <option value="dakar">Dakar</option>
                                <option value="abidjan">Abidjan</option>
                                <option value="libreville">Libre-ville</option>
                                <option value="ouagadougou">Ouagadougou</option>
                                <option value="bamako">Bamako</option>
                                <option value="cotonou">Cotonou</option>
                                <option value="lome">Lomé</option>
                            </select>
                        </div>
                        <div class="dialog-actions">
                            <label for="locationDialog" class="dialog-btn cancel">Annuler</label>
                            <label for="locationDialog" class="dialog-btn confirm">Confirmer</label>
                        </div>
                    </div>
                </div>
            </label>
            <button class="favorite">
                <i class="fas fa-heart"></i>
            </button>
            <a href="{{ url('/catalogue') }}" class="Catalogue">
                <i class="fas fa-arrow-left"></i> Catalogue
            </a>
        </div>
    </header>

    <!-- CONTENU PRINCIPAL -->
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
    @stack('scripts')
</body>

</html>
