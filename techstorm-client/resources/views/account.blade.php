@extends('layouts.client')

@section('title', 'TechStorm - Mon Compte')

@section('content')
    <!-- Sidebar -->
    <div class="left-side-bar">
        <div class="user-picture-name">
            <div class="user-picture">
                <img src="{{ asset('images/ERGO PROXY.jpg') }}" alt="photo de profil" />
            </div>
            <div class="username">Isabelle MAGNE</div>
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
            <div class="info-row"><span class="label">Nom :</span> <span class="value">Isabelle</span></div>
            <div class="info-row"><span class="label">Prénom :</span> <span class="value">Magne</span></div>
            <div class="info-row"><span class="label">Numéro de téléphone :</span> <span class="value">+237
                    123456789</span></div>
            <div class="info-row"><span class="label">Email :</span> <span class="value">jo@gmail.com</span></div>
            <div class="info-row"><span class="label">Date d'inscription :</span> <span class="value">12/11/2025</span>
            </div>
        </div>
        <button type="button" class="btn-edit-info" onclick="window.location.href='{{ url('/settings') }}'">
            Modifier mes informations
        </button>

    </div>
@endsection
