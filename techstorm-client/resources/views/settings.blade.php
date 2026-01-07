@extends('layouts.client')

@section('title', 'TechStorm - Paramètres')

@section('content')
    <div class="left-side-bar">
        <div class="user-picture-name">
            <div class="user-picture">
                <img src="{{ asset('images/default-avatar.png') }}" alt="photo de profil" id="sidebar-avatar" />
            </div>
            <div class="username">Utilisateur</div>
        </div>

        <div class="info">
            <a class="profile" href='{{ url('/account') }}'>Informations personnelles</a>
            <hr class="separator" />

            <a class="orders" href='{{ url('/commandes') }}'>Mes commandes</a>
            <hr class="separator" />

            <a class="preferences" href='{{ url('/favoris') }}'>Mes favoris</a>
            <hr class="separator" />

            <a class="Parameters active" href='{{ url('/settings') }}'>Paramètres</a>
        </div>
        <div class="logout">Déconnexion</div>
    </div>

    <!-- SECTION PARAMETRES -->
    <div class="main-section">
        <!-- SECTION PARAMÈTRES (remplace uniquement la div.main-section précédente) -->
        <div class="main-section settings-page" id="settingsPage" aria-live="polite">
            <h1>Paramètres du compte</h1>

            <!-- Compte utilisateur -->
            <section class="settings-section" id="accountSection" aria-labelledby="accountTitle">
                <div class="section-header">
                    <h2 id="accountTitle"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="currentColor"
                                d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-4.418 0-8 2.239-8 5v1h16v-1c0-2.761-3.582-5-8-5Z" />
                        </svg> Compte utilisateur</h2>
                    <p class="section-sub">Gère les informations de votre compte et la photo de profil.</p>
                </div>

                <div class="card">
                    <form class="form-grid" id="accountForm" autocomplete="off" novalidate>
                        <label class="form-group">
                            <span>Nom complet</span>
                            <input type="text" name="fullname" id="settings-fullname" value="Utilisateur Demo" />
                        </label>

                        <label class="form-group">
                            <span>Email</span>
                            <input type="email" name="email" id="settings-email" value="user@exemple.com" />
                        </label>

                        <label class="form-group">
                            <span>Adresse</span>
                            <input type="text" name="address" id="settings-address" placeholder="Votre adresse" />
                        </label>

                        <label class="form-group">
                            <span>Mot de passe actuel</span>
                            <input type="password" name="current_password" id="settings-current-password"
                                placeholder="Confirmez pour changer" />
                        </label>

                        <label class="form-group">
                            <span>Nouveau mot de passe</span>
                            <input type="password" name="new_password" id="settings-new-password"
                                placeholder="Laisser vide pour conserver" />
                        </label>

                        <label class="form-group">
                            <span>Confirmer le nouveau mot de passe</span>
                            <input type="password" name="new_password_confirmation" id="settings-confirm-password"
                                placeholder="Répétez le nouveau mot de passe" />
                        </label>

                        <label class="form-group file-uploader">
                            <span>Photo de profil</span>
                            <div class="uploader-row">
                                <div class="avatar-preview" id="avatarPreview">
                                    <img src="{{ asset('images/default-avatar.png') }}" alt="aperçu profil"
                                        id="settings-avatar-preview" />
                                </div>
                                <div class="uploader-actions">
                                    <input id="avatarInput" type="file" accept="image/*" />
                                    <button type="button" class="btn ghost" id="removeAvatarBtn">Supprimer</button>
                                </div>
                            </div>
                        </label>

                        <div class="form-actions">
                            <button type="submit" class="btn primary">Enregistrer</button>
                            <button type="button" class="btn danger" id="deleteAccountBtn">Supprimer le compte</button>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Notifications -->
            <section class="settings-section" id="notificationsSection" aria-labelledby="notificationsTitle">
                <div class="section-header">
                    <h2 id="notificationsTitle"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="currentColor"
                                d="M12 22a2 2 0 0 0 2-2H10a2 2 0 0 0 2 2Zm6-6V11c0-3.07-1.63-5.64-4.5-6.32V4a1.5 1.5 0 1 0-3 0v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2Z" />
                        </svg> Notifications</h2>
                    <p class="section-sub">Choisissez les notifications que vous souhaitez recevoir.</p>
                </div>

                <div class="card">
                    <div class="switch-row">
                        <label class="switch">
                            <input type="checkbox" id="promoEmails" checked>
                            <span class="slider"></span>
                        </label>
                        <div class="switch-info">
                            <strong>Emails promotionnels</strong>
                            <p class="muted">Offres et promos personnalisées.</p>
                        </div>
                    </div>

                    <div class="switch-row">
                        <label class="switch">
                            <input type="checkbox" id="pushNotifications" checked>
                            <span class="slider"></span>
                        </label>
                        <div class="switch-info">
                            <strong>Notifications push</strong>
                            <p class="muted">Notifications sur votre appareil mobile.</p>
                        </div>
                    </div>

                    <div class="switch-row">
                        <label class="switch">
                            <input type="checkbox" id="orderAlerts" checked>
                            <span class="slider"></span>
                        </label>
                        <div class="switch-info">
                            <strong>Alertes commandes / livraison</strong>
                            <p class="muted">Suivi et alertes d'expédition.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Langue & devise -->
            <section class="settings-section" id="localeSection" aria-labelledby="localeTitle">
                <div class="section-header">
                    <h2 id="localeTitle"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="currentColor"
                                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10c4.28 0 7.9-2.71 9.2-6.5H12V2z" />
                        </svg> Langue & devise</h2>
                    <p class="section-sub">Préférences régionales et de devise.</p>
                </div>

                <div class="card form-grid-2">
                    <label class="form-group">
                        <span>Langue</span>
                        <select id="langSelect">
                            <option value="fr">Français</option>
                            <option value="en">English</option>
                            <option value="es">Español</option>
                        </select>
                    </label>

                    <label class="form-group">
                        <span>Devise</span>
                        <select id="currencySelect">
                            <option value="EUR">EUR — €</option>
                            <option value="USD">USD — $</option>
                            <option value="CFA">XAF — FCFA</option>
                        </select>
                    </label>
                </div>
            </section>

            <!-- Adresse & livraison -->
            <section class="settings-section" id="addressSection" aria-labelledby="addressTitle">
                <div class="section-header">
                    <h2 id="addressTitle"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="currentColor"
                                d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" />
                        </svg> Adresse & livraison</h2>
                    <p class="section-sub">Gérez vos adresses de livraison enregistrées.</p>
                </div>

                <div class="card" id="addressesCard">
                    <ul class="address-list" id="addressList">
                        <!-- items ajoutés par JS -->
                        <li class="address-item">
                            <div>
                                <strong>Maison</strong>
                                <p class="muted">123 Rue Exemple, Douala, Cameroun</p>
                            </div>
                            <div class="item-actions">
                                <button class="btn small" data-edit-address>Modifier</button>
                                <button class="btn ghost small" data-delete-address>Supprimer</button>
                            </div>
                        </li>
                    </ul>

                    <div class="card-actions">
                        <button class="btn outline" id="addAddressBtn">+ Ajouter une adresse</button>
                    </div>
                </div>
            </section>

            <!-- Paiement -->
            <section class="settings-section" id="paymentSection" aria-labelledby="paymentTitle">
                <div class="section-header">
                    <h2 id="paymentTitle"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="currentColor"
                                d="M20 4H4a2 2 0 0 0-2 2v2h20V6a2 2 0 0 0-2-2zM2 10v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6H2zm4 4h4v2H6v-2z" />
                        </svg> Paiement</h2>
                    <p class="section-sub">Gérez vos méthodes de paiement sécurisées.</p>
                </div>

                <div class="card" id="paymentsCard">
                    <ul class="payment-list" id="paymentList">
                        <li class="payment-item">
                            <div>
                                <strong>Visa •••• 4242</strong>
                                <p class="muted">Exp : 12/26</p>
                            </div>
                            <div class="item-actions">
                                <button class="btn small" data-edit-card>Modifier</button>
                                <button class="btn ghost small" data-delete-card>Supprimer</button>
                            </div>
                        </li>
                    </ul>

                    <div class="card-actions">
                        <button class="btn outline" id="addPaymentBtn"
                            onclick="window.location.href='../Enreg-Carte/Enreg-carte.html'">Ajouter une méthode de
                            paiement</button>
                    </div>
                </div>
            </section>

            <!-- Sécurité -->
            <section class="settings-section" id="securitySection" aria-labelledby="securityTitle">
                <div class="section-header">
                    <h2 id="securityTitle"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="currentColor" d="M12 1 3 5v6c0 5 3.8 9.7 9 11 5.2-1.3 9-6 9-11V5l-9-4z" />
                        </svg> Sécurité</h2>
                    <p class="section-sub">Options de sécurité et sessions actives.</p>
                </div>

                <div class="card">
                    <div class="switch-row">
                        <label class="switch">
                            <input type="checkbox" id="twoFactorToggle">
                            <span class="slider"></span>
                        </label>
                        <div class="switch-info">
                            <strong>Activer la double authentification (2FA)</strong>
                            <p class="muted">Ajoute une couche de sécurité supplémentaire.</p>
                        </div>
                    </div>

                    <div class="sessions">
                        <h3 class="sessions-title">Sessions actives</h3>
                        <ul class="session-list" id="sessionList">
                            <li class="session-item">
                                <div>
                                    <strong>Ordinateur — Chrome</strong>
                                    <p class="muted">Douala — Aujourd'hui</p>
                                </div>
                                <button class="btn ghost small" data-logout-session>Déconnexion</button>
                            </li>
                            <li class="session-item">
                                <div>
                                    <strong>Téléphone — Android</strong>
                                    <p class="muted">Douala — Hier</p>
                                </div>
                                <button class="btn ghost small" data-logout-session>Déconnexion</button>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- Accessibilité -->
            <section class="settings-section" id="accessSection" aria-labelledby="accessTitle">
                <div class="section-header">
                    <h2 id="accessTitle"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="currentColor" d="M12 3a9 9 0 100 18 9 9 0 000-18zM7 12a5 5 0 015-5v5H7z" />
                        </svg> Accessibilité</h2>
                    <p class="section-sub">Ajustez l'affichage pour votre confort visuel.</p>
                </div>

                <div class="card form-grid-2">
                    <label class="form-group">
                        <span>Taille du texte</span>
                        <input type="range" id="fontSizeRange" min="14" max="22" value="18" />
                        <div class="range-value" id="fontSizeValue">18px</div>
                    </label>

                    <div class="form-group">
                        <span>Contraste élevé</span>
                        <div class="switch-row no-border">
                            <label class="switch">
                                <input type="checkbox" id="highContrastToggle">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Vie privée -->
            <section class="settings-section" id="privacySection" aria-labelledby="privacyTitle">
                <div class="section-header">
                    <h2 id="privacyTitle"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="currentColor"
                                d="M12 1C7.03 1 3 5.03 3 10v3c0 4.97 3.58 9 9 9s9-4.03 9-9v-3c0-4.97-4.03-9-9-9z" />
                        </svg> Vie privée</h2>
                    <p class="section-sub">Gérer vos données et la confidentialité.</p>
                </div>

                <div class="card card-vertical">
                    <div class="privacy-actions">
                        <a class="link" href="#">Gérer les cookies</a>
                        <div class="privacy-buttons">
                            <button class="btn outline" id="downloadDataBtn">Télécharger mes données</button>
                            <button class="btn danger" id="deleteDataBtn">Supprimer mes données</button>
                        </div>
                    </div>
                </div>
            </section>

        </div>

    </div>
@endsection
