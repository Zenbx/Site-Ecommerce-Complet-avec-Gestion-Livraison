@extends('layouts.guest')

@section('title', 'TechStorm - Connexion')

@push('styles')
    <!-- On utilise vite pour générer le lien vers le fichier css -->
    @vite(['resources/css/Authentification_css/connexion.css'])
@endpush


@section('content')
    <div class="auth-container">
        <!-- Left Side: Logo -->
        <div class="auth-left">
            <div class="auth-logo">Techstorm</div>
        </div>

        <!-- Right Side: Login Card -->
        <div class="auth-right">
            <div class="login-card">
                <h1 id="connexion">Connexion</h1>

                <button id="google-btn">
                    <img src="https://www.svgrepo.com/show/355037/google.svg" alt="Google" />
                    <span>Se connecter avec Google</span>
                </button>

                <div id="ou">
                    <hr class="line"> <span>OU</span>
                    <hr class="line">
                </div>

                <form class="form" action="#" method="POST"> <!-- Added form tag for semantic correctness -->
                    @csrf <!-- Good practice to include CSRF token even if not yet fully functional -->
                    <div>
                        <input type="email" name="email" placeholder="E-mail" required />
                    </div>
                    <div>
                        <input type="password" name="password" placeholder="Mot de passe" required />
                    </div>

                    <div id="forgotDiv">
                        <a href="#" id="forgot">Mot de passe oublié?</a>
                    </div>

                    <button type="submit" id="login-btn">SE CONNECTER</button>
                </form>

                <p class="register">
                    Pas encore de compte? <a href="{{ url('/inscription') }}" id="CreerCompte">Créer un compte</a>
                </p>
            </div>
        </div>
    </div>
@endsection
