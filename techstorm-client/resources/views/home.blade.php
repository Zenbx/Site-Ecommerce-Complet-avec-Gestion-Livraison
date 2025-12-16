@extends('layouts.app')

@section('title', 'TechStorm - Page d\'accueil')

<!-- POUSSER le style spécifique à la page -->
@push('styles')
    <!-- On utilise vite pour générer le lien vers le fichier css -->
    @vite(['resources/css/Acceuil_css/style1.css', 'resources/css/Acceuil_css/section-produits.css', 'resources/css/Acceuil_css/section-nouveautes.css', 'resources/css/Acceuil_css/section-action.css'])
@endpush


@section('content')

    <!-- Section Hero -->
    <section class="hero">
        <!-- LIAISON DES IMAGES AVEC asset() -->
        <img src="{{ asset('images/Home-images/Casque.png') }}" alt="Casque TechStorm" class="hero-image" />
        <div class="container">
            <h2 class="hero-subtitle gradient-text">La Technologie de demain</h2>
            <h2 class="hero-subtitle gradient-text">Aujourd'hui</h2>
            <p class="hero-description">Découvrez nos appareils high tech et innovants</p>
            <div class="hero-button-container">
                <a href="{{ url('/catalogue') }}" class="btn-explorer">Explorer</a>
                <a href="#" class="btn-savoir-plus">En savoir plus</a>
            </div>
        </div>
    </section>

    <!-- Section Produits -->
    <section class="products-section">
        <div class="container">
            <h2 class="section-title scroll-reveal">Nos Produits</h2>
            <p class="section-subtitle scroll-reveal">L'excellence technologique à portée de main</p>
            <div class="products-grid">
                <div class="product-card scroll-reveal-left">
                    <div class="product-badge">NOUVEAU</div>
                    <div class="product-image">
                        <img src="{{ asset('images/Home-images/image.png') }}" alt="Airpods 1">
                    </div>
                    <div class="product-content">
                        <h3>Airpods 1</h3>
                        <p class="product-description">Son cristallin et design épuré pour une expérience audio
                            exceptionnelle</p>
                        <span class="product-price">25 000 FCFA</span>
                        <a href="#" class="btn-panier">Ajouter au panier</a>
                    </div>
                </div>

                <div class="product-card scroll-reveal-scale">
                    <div class="product-badge">POPULAIRE</div>
                    <div class="product-image">
                        <img src="{{ asset('images/Home-images/image2.jpg') }}" alt="Airpods 2">
                    </div>
                    <div class="product-content">
                        <h3>Airpods 2</h3>
                        <a href="#" class="btn-panier">Ajouter au panier</a>
                    </div>
                </div>

                <div class="product-card scroll-reveal-right">
                    <div class="product-badge">PRO</div>
                    <div class="product-image">
                        <img src="{{ asset('images/Home-images/image3.jpg') }}" alt="Airpods Pro">
                    </div>
                    <div class="product-content">
                        <h3>Airpods Pro</h3>
                        <p class="product-description">Réduction de bruit active et mode transparence pour les
                            professionnels</p>
                        <span class="product-price">25 000 FCFA</span>
                        <a href="#" class="btn-panier">Ajouter au panier</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section Nouveautés -->
    <section class="new-section">
        <div class="container scroll-reveal">
            <div class="glitch-effect">
                <h2 class="new-title-R">NOUVEAUTÉS 2025</h2>
                <h2 class="new-title-V">NOUVEAUTÉS 2025</h2>
                <h2 class="new-title-B">NOUVEAUTÉS 2025</h2>
            </div>
        </div>
    </section>

    <!-- Section Call to Action -->
    <section class="cta-section">
        <div class="container">
            <!-- ... -->
            <div class="cta-container scroll-reveal">
                <h2 class="cta-title">Prêt à acheter ?</h2>
                <p class="cta-subtitle">
                    Rejoignez des milliers de clients satisfaits et découvrez l'avenir de la technologie dès aujourd'hui
                </p>
                <div class="cta-buttons">
                    <a href="{{ url('/commander') }}" class="cta-btn-primary">Commander maintenant</a>
                    <a href="{{ url('/catalogue') }}" class="cta-btn-secondary">Voir le catalogue</a>
                </div>

                <div class="cta-features scroll-reveal">
                    <div class="cta-feature">
                        <!-- LIAISON DES ICONES AVEC asset() -->
                        <div class="cta-feature-icon"><img src="{{ asset('images/icones/rocket.png') }}" alt="rocket">
                        </div>
                        <h4>Livraison Rapide</h4>
                        <p>Recevez vos produits en 48h maximum</p>
                    </div>
                    <div class="cta-feature">
                        <div class="cta-feature-icon"><img src="{{ asset('images/icones/lock.png') }}" alt="rocket"></div>
                        <h4>Paiement Sécurisé</h4>
                        <p>Transactions 100% protégées</p>
                    </div>
                    <div class="cta-feature">
                        <div class="cta-feature-icon"><img src="{{ asset('images/icones/diamond.png') }}" alt="rocket">
                        </div>
                        <h4>Garantie Premium</h4>
                        <p>2 ans de garantie constructeur</p>
                    </div>
                    <div class="cta-feature">
                        <div class="cta-feature-icon"><img src="{{ asset('images/icones/headphone-symbol.png') }}"
                                alt="rocket"></div>
                        <h4>Support 24/7</h4>
                        <p>Assistance disponible à tout moment</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
