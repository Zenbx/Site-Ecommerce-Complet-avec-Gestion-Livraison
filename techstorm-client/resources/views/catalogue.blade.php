@extends('layouts.catalog')

@section('title', 'TechStorm - Catalogue')


@section('content')
    <section class="catalogue" aria-label="Catalogue de produits">
        <!-- Barre latérale filtres -->
        <aside class="filters" aria-labelledby="filters-title">
            <h3 id="filters-title">Filtres</h3>

            <div class="filter-group">
                <label for="search" class="visually-hidden">Rechercher</label>
                <input id="search" type="search" placeholder="Rechercher un produit..." />
            </div>

            <div class="filter-group">
                <h4>Catégories</h4>
                <ul class="filter-list" id="category-filters">
                    <li><label><input type="checkbox" value="Smartphones" /> <span>Smartphones</span></label></li>
                    <li><label><input type="checkbox" value="Laptops" /> <span>Laptops</span></label></li>
                    <li><label><input type="checkbox" value="Tablettes" /> <span>Tablettes</span></label></li>
                    <li><label><input type="checkbox" value="Écouteurs" /> <span>Écouteurs</span></label></li>
                    <li><label><input type="checkbox" value="Appareils Photo" /> <span>Appareils Photo</span></label></li>
                </ul>
            </div>

            <!-- NOUVEAU : Filtre par marque -->
            <div class="filter-group">
                <h4>Marques</h4>
                <ul class="filter-list" id="brand-filters">
                    <!-- Les marques seront générées dynamiquement par JS -->
                </ul>
            </div>

            <div class="filter-group">
                <h4>Prix</h4>
                <div class="price-range">
                    <input type="range" id="price-range" min="0" max="3000000" step="10000" value="3000000" />
                    <div class="price-value">Max : <span id="price-max">3&nbsp;000&nbsp;000</span> FCFA</div>
                </div>
            </div>

            <div class="filter-actions">
                <button id="reset-filters" class="btn-small">Réinitialiser</button>
                <button id="apply-filters" class="btn-small primary">Appliquer</button>
            </div>
        </aside>

        <!-- Zone produits -->
        <div class="catalogue-body">
            <header class="catalogue-header">
                <div>
                    <h1 id="catalog-title">Tous Nos Produits</h1>
                    <p class="muted" id="product-count">Affichage de <span id="shown-count">0</span> produits</p>
                </div>

                <div class="controls">
                    <label for="sort" class="visually-hidden">Trier</label>
                    <select id="sort" aria-label="Trier les produits">
                        <option value="featured">Trier par : Pertinence</option>
                        <option value="price-asc">Prix : faible → élevé</option>
                        <option value="price-desc">Prix : élevé → faible</option>
                        <option value="new">Nouveautés</option>
                    </select>
                </div>
            </header>

            <div class="cards" id="cards" aria-live="polite" aria-busy="false">
                <!-- Les cartes vont être insérées dynamiquement via JS -->
            </div>

            <!-- Pagination -->
            <div class="pagination" id="pagination" role="navigation" aria-label="Pagination des produits">
                <button id="prev-page" class="btn-small" aria-label="Page précédente">‹</button>
                <div id="pages" class="pages"></div>
                <button id="next-page" class="btn-small" aria-label="Page suivante">›</button>
            </div>
        </div>
    </section>

@endsection

<!-- Pousser le script spécifique au catalogue dans le layout app -->
@push('scripts')
    @vite('resources/js/catalogue.js')
@endpush
