@extends('layouts.client')

@section('title', 'TechStorm - Mes commandes')

@section('content')
    <div class="left-side-bar">
        <div class="user-picture-name">
            <div class="user-picture">
                <img src="{{ asset('images/ERGO PROXY.jpg') }}" alt="photo de profil" />
            </div>
            <div class="username">Utilisateur</div>
        </div>

        <div class="info">
            <a class="profile" href="{{ url('/account') }}">Informations personnelles</a>
            <hr class="separator" />

            <a class="orders active" hre f="{{ url('/commandes') }}">Mes commandes</a>
            <hr class="separator" />

            <a class="preferences" href="#">Mes favoris</a>
            <hr class="separator" />

            <a class="Parameters" href="{{ url('/settings') }}">Paramètres</a>
        </div>
        <div class="logout">Déconnexion</div>
    </div>

    <!-- SECTION COMMANDES -->
    <div class="main-section">
        <h1>Mes commandes</h1>

        <!-- Barre de recherche et filtre -->
        <div class="order-search-bar">
            <input type="text" placeholder="Rechercher une commande..." class="order-search-input" />
            <select class="order-filter">
                <option value="all">Toutes</option>
                <option value="en_cours">En cours</option>
                <option value="livrée">Livrées</option>
                <option value="annulée">Annulées</option>
            </select>
        </div>

        <!-- Liste des commandes -->
        <div class="orders-list">

            <!-- Commande 1 -->
            <div class="order-card">
                <div class="order-header">
                    <h3>Commande #KSKSIO29292K</h3>
                    <span class="order-status status-en-cours">En cours</span>
                </div>
                <p class="order-date">Passée le : 15 octobre 2025</p>
                <p class="order-total">
                    Montant total : <strong>299,99 €</strong>
                </p>

                <div class="order-products">
                    <div class="product-item">
                        <img src="{{ asset('images/casque.jpg') }}" alt="Casque VR TechStorm X" />
                        <div class="product-info">
                            <h4>Casque VR TechStorm X</h4>
                            <p>Quantité : 1</p>
                        </div>
                    </div>
                    <div class="product-item">
                        <img src="images/gants.jpg" alt="Gants haptiques Pro" />
                        <div class="product-info">
                            <h4>Gants haptiques Pro</h4>
                            <p>Quantité : 1</p>
                        </div>
                    </div>
                </div>

                <div class="order-actions">
                    <button class="btn-view-details">Voir les détails</button>
                    <button class="btn-track-order">Suivre la commande</button>
                    <button class="btn-download-invoice">
                        Télécharger la facture
                    </button>
                </div>
            </div>

            <!-- Commande 2 -->
            <div class="order-card">
                <div class="order-header">
                    <h3>Commande #KSDQKQKQKQ</h3>
                    <span class="order-status status-livrée">Livrée</span>
                </div>
                <p class="order-date">Passée le : 1 octobre 2025</p>
                <p class="order-total">
                    Montant total : <strong>149,00 €</strong>
                </p>

                <div class="order-products">
                    <div class="product-item">
                        <img src="images/drone.png" alt="Mini Drone AI Storm" />
                        <div class="product-info">
                            <h4>Mini Drone AI Storm</h4>
                            <p>Quantité : 1</p>
                        </div>
                    </div>
                </div>

                <div class="order-actions">
                    <button class="btn-view-details">Voir les détails</button>
                    <button class="btn-leave-review">Laisser un avis</button>
                </div>
            </div>

            <div class="order-card">
                <div class="order-header">
                    <h3>Commande #KSDQKQKQKQ</h3>
                    <span class="order-status status-annulée">Annulée</span>
                </div>
                <p class="order-date">Passée le : 1 octobre 2025</p>
                <p class="order-total">
                    Montant total : <strong>149,00 €</strong>
                </p>

                <div class="order-products">
                    <div class="product-item">
                        <img src="images/drone.png" alt="Mini Drone AI Storm" />
                        <div class="product-info">
                            <h4>Mini Drone AI Storm</h4>
                            <p>Quantité : 1</p>
                        </div>
                    </div>
                </div>

                <div class="order-actions">
                    <button class="btn-view-details">Voir les détails</button>
                    <button class="btn-leave-review">Laisser un avis</button>
                </div>
            </div>

            <div class="order-card">
                <div class="order-header">
                    <h3>Commande #KSDQKQKQKQ</h3>
                    <span class="order-status status-livrée">Livrée</span>
                </div>
                <p class="order-date">Passée le : 1 octobre 2025</p>
                <p class="order-total">
                    Montant total : <strong>149,00 €</strong>
                </p>

                <div class="order-products">
                    <div class="product-item">
                        <img src="images/drone.png" alt="Mini Drone AI Storm" />
                        <div class="product-info">
                            <h4>Mini Drone AI Storm</h4>
                            <p>Quantité : 1</p>
                        </div>
                    </div>
                </div>

                <div class="order-actions">
                    <button class="btn-view-details">Voir les détails</button>
                    <button class="btn-leave-review">Laisser un avis</button>
                </div>
            </div>

            <div class="order-card">
                <div class="order-header">
                    <h3>Commande #KSDQKQKQKQ</h3>
                    <span class="order-status status-livrée">Livrée</span>
                </div>
                <p class="order-date">Passée le : 1 octobre 2025</p>
                <p class="order-total">
                    Montant total : <strong>149,00 €</strong>
                </p>

                <div class="order-products">
                    <div class="product-item">
                        <img src="images/drone.png" alt="Mini Drone AI Storm" />
                        <div class="product-info">
                            <h4>Mini Drone AI Storm</h4>
                            <p>Quantité : 1</p>
                        </div>
                    </div>
                </div>

                <div class="order-actions">
                    <button class="btn-view-details">Voir les détails</button>
                    <button class="btn-leave-review">Laisser un avis</button>
                </div>
            </div>

        </div>
    </div>
@endsection
