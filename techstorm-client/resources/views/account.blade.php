@extends('layouts.client')

@section('title', 'TechStorm - Mon Compte')

@section('content')
    <!-- Sidebar -->
    <div class="left-side-bar">
        <div class="user-picture-name">
            <div class="user-picture">
                <img src="{{ asset('images/default-avatar.png') }}" alt="photo de profil" id="user-avatar" />
            </div>
            <div class="username" id="sidebar-username">Chargement...</div>
        </div>

        <div class="info">
            <a href="{{ url('/account') }}">Informations personnelles</a>
            <hr class="separator" />

            <a href="{{ url('/commandes') }}">Mes commandes</a>
            <hr class="separator" />

            <a href="{{ url('/favoris') }}">Mes favoris</a>
            <hr class="separator" />

            <a href="{{ url('/settings') }}">Paramètres</a>
        </div>
        <div class="logout">Déconnexion</div>
    </div>

    <!-- Contenu onglet "Informations personnelles" -->
    <div class="details-personnal-info">
        <h1>Mes informations personnelles</h1>
        <div class="info-card">
            <div class="info-row"><span class="label">Nom :</span> <span class="value" id="user-name">Chargement...</span>
            </div>
            <div class="info-row"><span class="label">Email :</span> <span class="value"
                    id="user-email">Chargement...</span></div>
            <div class="info-row"><span class="label">Téléphone :</span> <span class="value" id="user-phone">-</span>
            </div>
            <div class="info-row"><span class="label">Adresse :</span> <span class="value" id="user-address">-</span>
            </div>
            <div class="info-row"><span class="label">Date d'inscription :</span> <span class="value"
                    id="user-date">-</span></div>
        </div>
        <button type="button" class="btn-edit-info" onclick="window.location.href='{{ url('/settings') }}'">
            Modifier mes informations
        </button>
    </div>
@endsection
