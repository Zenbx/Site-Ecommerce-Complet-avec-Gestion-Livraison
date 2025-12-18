@extends('layouts.guest')

@section('title', 'TechStorm - Inscription')

@push('styles')
    <!-- On utilise vite pour générer le lien vers le fichier css -->
    @vite(['resources/css/Authentification_css/inscription.css'])
@endpush

@section('content')
    <div class="auth-container">
        <!-- Left Side: Logo -->
        <div class="auth-left">
            <div class="auth-logo">Techstorm</div>
        </div>

        <!-- Right Side: Registration Card -->
        <div class="auth-right">
            <div class="login-card">
                <h1 id="CreerUnCompte">Créer un compte</h1>

                <button id="google-btn">
                    <img src="https://www.svgrepo.com/show/355037/google.svg" alt="Google" />
                    <span>S'inscrire avec Google</span>
                </button>

                <div id="ou">
                    <hr class="line"> <span>OU</span>
                    <hr class="line">
                </div>

                <form class="form" id="register-form">
                    @csrf
                    <div>
                        <input type="text" id="register-pseudo" name="pseudo" placeholder="Pseudo" required>
                    </div>
                    <div>
                        <input type="email" id="register-email" name="email" placeholder="E-mail" required />
                    </div>
                    <div>
                        <input type="password" id="register-password" name="password" placeholder="Mot de passe" required />
                    </div>
                    <div>
                        <input type="password" id="register-password-confirmation" name="password_confirmation"
                            placeholder="Confirmer le mot de passe" required />
                    </div>
                    <div>
                        <input type="text" id="register-address" name="address" placeholder="Adresse" required />
                    </div>

                    <button type="submit" id="signUp-btn">S'INSCRIRE</button>
                </form>

                <p class="register">
                    Déjà un compte? <a href="{{ url('/connexion') }}" id="ConnectezVous">Connectez Vous</a>
                </p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/auth.js'])
@endpush
