@extends('layouts.basket')

@section('title', 'TechStorm - Panier')

@section('content')
    <section class="article-panier">
        <div class="panier-header">
            <input type="checkbox" id="selectAll">
            <span>Tout sélectionner</span>
        </div>

        <!-- Article 1 -->
        <div class="article">
            <input type="checkbox" class="article-checkbox">
            <img src="https://images.unsplash.com/photo-1609081219090-a6d81d3085bf?ixlib=rb-4.1.0&auto=format&fit=crop&q=60&w=500"
                alt="AirPods Pro" />
            <div class="article-info">
                <div class="article-name">
                    <label for="dialog1" style="cursor: pointer; color: inherit;">AirPods Pro 2ème Génération</label>
                    <input type="checkbox" id="dialog1">
                    <div class="dialog-overlay">
                        <div class="dialog">
                            <label for="dialog1" class="dialog-close">×</label>
                            <h3 class="dialog-title">AirPods Pro 2ème Génération</h3>
                            <div class="dialog-section">
                                <label>Couleur</label>
                                <div class="color-options">
                                    <label class="color-option">
                                        <input type="radio" name="color1" checked>
                                        <div class="color-box" style="background-color: #ffffff; border: 1px solid #ccc;">
                                        </div>
                                    </label>
                                    <label class="color-option">
                                        <input type="radio" name="color1">
                                        <div class="color-box" style="background-color: #000000;"></div>
                                    </label>
                                    <label class="color-option">
                                        <input type="radio" name="color1">
                                        <div class="color-box" style="background-color: #ff4444;"></div>
                                    </label>
                                    <label class="color-option">
                                        <input type="radio" name="color1">
                                        <div class="color-box" style="background-color: #4444ff;"></div>
                                    </label>
                                </div>
                            </div>
                            <div class="dialog-section">
                                <label>Quantité</label>
                                <div class="dialog-quantity">
                                    <input type="number" value="1" min="1">
                                </div>
                            </div>
                            <div class="dialog-actions">
                                <label for="dialog1" class="dialog-btn cancel">Annuler</label>
                                <label for="dialog1" class="dialog-btn confirm">Confirmer</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="article-details">
                    <span><i class="fas fa-palette"></i> Blanc</span>
                    <span><i class="fas fa-box"></i> 1 unité</span>
                </div>
            </div>
            <div class="article-actions">
                <div class="quantity-control">
                    <input type="number" value="1" min="1">
                </div>
                <div class="article-price">20 000 FCFA</div>
                <a href="#" class="delete-btn">
                    <i class="fas fa-trash"></i>
                </a>
            </div>
        </div>

        <!-- Article 2 -->
        <div class="article">
            <input type="checkbox" class="article-checkbox">
            <img src="https://media.istockphoto.com/id/1419137816/photo/wireless-in-ear-headphones-with-a-case-on-a-pink-background-flat-lay.webp?a=1&b=1&s=612x612&w=0&k=20&c=axs6Mit2zi5g0rY_UnxIDkmcdqD_zdPE1ZsvufksRaU="
                alt="Casque Sans Fil">
            <div class="article-info">
                <div class="article-name">
                    <label for="dialog2" style="cursor: pointer; color: inherit;">Casque Sans Fil Premium</label>
                    <input type="checkbox" id="dialog2">
                    <div class="dialog-overlay">
                        <div class="dialog">
                            <label for="dialog2" class="dialog-close">×</label>
                            <h3 class="dialog-title">Casque Sans Fil Premium</h3>
                            <div class="dialog-section">
                                <label>Couleur</label>
                                <div class="color-options">
                                    <label class="color-option">
                                        <input type="radio" name="color2">
                                        <div class="color-box" style="background-color: #ffffff; border: 1px solid #ccc;">
                                        </div>
                                    </label>
                                    <label class="color-option">
                                        <input type="radio" name="color2" checked>
                                        <div class="color-box" style="background-color: #000000;"></div>
                                    </label>
                                    <label class="color-option">
                                        <input type="radio" name="color2">
                                        <div class="color-box" style="background-color: #ff4444;"></div>
                                    </label>
                                    <label class="color-option">
                                        <input type="radio" name="color2">
                                        <div class="color-box" style="background-color: #4444ff;"></div>
                                    </label>
                                </div>
                            </div>
                            <div class="dialog-section">
                                <label>Quantité</label>
                                <div class="dialog-quantity">
                                    <input type="number" value="2" min="1">
                                </div>
                            </div>
                            <div class="dialog-actions">
                                <label for="dialog2" class="dialog-btn cancel">Annuler</label>
                                <label for="dialog2" class="dialog-btn confirm">Confirmer</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="article-details">
                    <span><i class="fas fa-palette"></i> Noir</span>
                    <span><i class="fas fa-box"></i> 2 unités</span>
                </div>
            </div>
            <div class="article-actions">
                <div class="quantity-control">
                    <input type="number" value="2" min="1">
                </div>
                <div class="article-price">25 000 FCFA</div>
                <a href="#" class="delete-btn">
                    <i class="fas fa-trash"></i>
                </a>
            </div>
        </div>

        <!-- Article 3 -->
        <div class="article">
            <input type="checkbox" class="article-checkbox">
            <img src="https://images.unsplash.com/photo-1606220945770-b5b6c2c55bf1?auto=format&fit=crop&q=60&w=700"
                alt="Écouteurs Sport">
            <div class="article-info">
                <div class="article-name">
                    <label for="dialog3" style="cursor: pointer; color: inherit;">Écouteurs Sport Bluetooth</label>
                    <input type="checkbox" id="dialog3">
                    <div class="dialog-overlay">
                        <div class="dialog">
                            <label for="dialog3" class="dialog-close">×</label>
                            <h3 class="dialog-title">Écouteurs Sport Bluetooth</h3>
                            <div class="dialog-section">
                                <label>Couleur</label>
                                <div class="color-options">
                                    <label class="color-option">
                                        <input type="radio" name="color3">
                                        <div class="color-box" style="background-color: #ffffff; border: 1px solid #ccc;">
                                        </div>
                                    </label>
                                    <label class="color-option">
                                        <input type="radio" name="color3">
                                        <div class="color-box" style="background-color: #000000;"></div>
                                    </label>
                                    <label class="color-option">
                                        <input type="radio" name="color3" checked>
                                        <div class="color-box" style="background-color: #ff4444;"></div>
                                    </label>
                                    <label class="color-option">
                                        <input type="radio" name="color3">
                                        <div class="color-box" style="background-color: #4444ff;"></div>
                                    </label>
                                </div>
                            </div>
                            <div class="dialog-section">
                                <label>Quantité</label>
                                <div class="dialog-quantity">
                                    <input type="number" value="1" min="1">
                                </div>
                            </div>
                            <div class="dialog-actions">
                                <label for="dialog3" class="dialog-btn cancel">Annuler</label>
                                <label for="dialog3" class="dialog-btn confirm">Confirmer</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="article-details">
                    <span><i class="fas fa-palette"></i> Rouge</span>
                    <span><i class="fas fa-box"></i> 1 unité</span>
                </div>
            </div>
            <div class="article-actions">
                <div class="quantity-control">
                    <input type="number" value="1" min="1">
                </div>
                <div class="article-price">30 000 FCFA</div>
                <a href="#" class="delete-btn">
                    <i class="fas fa-trash"></i>
                </a>
            </div>
        </div>

    </section>

    <!-- RESUME -->
    <aside class="cart-summary">
        <h3 class="summary-title">Résumé</h3>
        <div class="summary-line">
            <span>Vos articles font:</span>
            <span>75 000 FCFA</span>
        </div>
        <div class="summary-line">
            <span>Livraison</span>
            <span>1 000 FCFA</span>
        </div>
        <div class="summary-line total">
            <span>Total</span>
            <span>76 000 FCFA</span>
        </div>
        <button class="checkout-btn">Procéder au paiement</button>
    </aside>
@endsection

@push('scripts')
    @vite(['resources/js/panier.js'])
@endpush
