@extends('layouts.app')

@section('title', 'TechStorm - Contact')

@push('styles')
    @vite(['resources/css/Contact_css/style.css', 'resources/css/Contact_css/main.css'])
@endpush

@section('content')
    <section class="hero">
        <h1>Contactez-nous</h1>
        <p>Notre équipe est à votre écoute pour répondre à toutes vos questions</p>
    </section>

    <div class="container">
        <div class="contact-wrapper">

            <div class="contact-form">
                <h2>Formulaire de contact</h2>
                <p class="form-note">Les champs marqués d'un astérisque (<span class="required">*</span>) sont obligatoires
                </p>
                <form action="#" method="post">
                    <div class="form-group">
                        <label for="nom">Nom et prénom <span class="required">*</span></label>
                        <input type="text" id="nom" name="nom" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="telephone">Numéro de téléphone</label>
                        <input type="tel" id="telephone" name="telephone">
                    </div>

                    <div class="form-group">
                        <label for="sujet">Objet de votre message <span class="required">*</span></label>
                        <select id="sujet" name="sujet" required>
                            <option value="">-- Sélectionner --</option>
                            <option value="commande">Suivi de commande</option>
                            <option value="produit">Renseignement produit</option>
                            <option value="livraison">Livraison</option>
                            <option value="retour">Retour et remboursement</option>
                            <option value="autre">Autre demande</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="message">Votre message <span class="required">*</span></label>
                        <textarea id="message" name="message" required></textarea>
                    </div>

                    <button type="submit" class="btn-submit">Envoyer</button>
                </form>
            </div>

            <div class="contact-info">
                <div class="info-card">
                    <h3>Nos coordonnées</h3>

                    <div class="info-item">
                        <div class="info-icon">A</div>
                        <div class="info-content">
                            <h4>Adresse</h4>
                            <p>123 Avenue du Commerce<br>Yaoundé, Cameroun</p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">T</div>
                        <div class="info-content">
                            <h4>Téléphone</h4>
                            <p>+237 6XX XX XX XX<br>Du lundi au vendredi de 8h à 18h</p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">E</div>
                        <div class="info-content">
                            <h4>Email</h4>
                            <p>contact@eshop.cm<br>support@eshop.cm</p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">H</div>
                        <div class="info-content">
                            <h4>Horaires</h4>
                            <p>Lundi - Vendredi: 8h - 18h<br>Samedi: 9h - 14h<br>Dimanche: Fermé</p>
                        </div>
                    </div>
                </div>

                <div class="map-section">
                    <h3>Localisation</h3>
                    <div class="map-placeholder">
                        Carte interactive
                    </div>
                </div>
            </div>
        </div>

        <section class="faq-section">
            <h2>Questions fréquentes</h2>

            <div class="faq-item">
                <h3>Dans quel délai recevrai-je une réponse ?</h3>
                <p>Notre équipe vous répond généralement sous 24 heures ouvrées. Pour une assistance immédiate, vous pouvez
                    nous contacter par téléphone.</p>
            </div>

            <div class="faq-item">
                <h3>Comment puis-je suivre ma commande ?</h3>
                <p>Un email de confirmation contenant votre numéro de suivi vous est envoyé dès l'expédition de votre colis.
                </p>
            </div>

            <div class="faq-item">
                <h3>Quels modes de paiement acceptez-vous ?</h3>
                <p>Nous acceptons Mobile Money (MTN et Orange Money), carte bancaire, ainsi que le paiement à la livraison
                    dans certaines zones.</p>
            </div>

            <div class="faq-item">
                <h3>Livrez-vous partout à Yaoundé ?</h3>
                <p>Oui, nous assurons la livraison dans toute la ville de Yaoundé. Les frais de livraison varient selon
                    votre localisation.</p>
            </div>
        </section>
    </div>
@endsection
