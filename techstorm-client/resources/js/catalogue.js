import { requireAuth } from './auth-guard.js';

// Données produit avec marques ajoutées
/*
const PRODUCTS = [
  // ==================== SMARTPHONES ====================
  // iPhone
  { id: 1, title: "iPhone 15 Pro Max", category: "Smartphones", brand: "Apple", price: 850000, stock: 5, img: "images/Telephones/Iphone/phone_iphone_1.jpeg", featured: true, new: true, desc: "Le summum de la technologie Apple avec puce A17 Pro." },
  { id: 2, title: "iPhone 15 Pro", category: "Smartphones", brand: "Apple", price: 750000, stock: 8, img: "images/Telephones/Iphone/phone_iphone_2.jpeg", featured: true, new: true, desc: "Performance exceptionnelle et caméra professionnelle." },
  { id: 3, title: "iPhone 15", category: "Smartphones", brand: "Apple", price: 650000, stock: 10, img: "images/Telephones/Iphone/phone_iphone_3.jpeg", featured: false, new: true, desc: "iPhone nouvelle génération, design élégant." },
  { id: 4, title: "iPhone 14 Pro", category: "Smartphones", brand: "Apple", price: 600000, stock: 6, img: "images/Telephones/Iphone/phone_iphone_4.jpeg", featured: false, new: false, desc: "Dynamic Island et caméra 48MP." },
  { id: 5, title: "iPhone 14", category: "Smartphones", brand: "Apple", price: 500000, stock: 12, img: "images/Telephones/Iphone/phone_iphone_5.jpeg", featured: false, new: false, desc: "iPhone fiable avec excellente autonomie." },
  { id: 6, title: "iPhone 13", category: "Smartphones", brand: "Apple", price: 400000, stock: 15, img: "images/Telephones/Iphone/phone_iphone_6.jpeg", featured: false, new: false, desc: "Rapport qualité-prix imbattable." },
  { id: 7, title: "iPhone SE 2024", category: "Smartphones", brand: "Apple", price: 320000, stock: 20, img: "images/Telephones/Iphone/phone_iphone_7.jpeg", featured: false, new: false, desc: "Compact et puissant, parfait pour débutants." },
  { id: 8, title: "iPhone 12 Pro", category: "Smartphones", brand: "Apple", price: 380000, stock: 8, img: "images/Telephones/Iphone/phone_iphone_8.jpeg", featured: false, new: false, desc: "Toujours performant avec 5G." },

  // Samsung
  { id: 9, title: "Galaxy S25 Ultra", category: "Smartphones", brand: "Samsung", price: 720000, stock: 6, img: "images/Telephones/Samsung/phone_samsung_1.jpeg", featured: true, new: true, desc: "Écran 6.8\" AMOLED, S Pen intégré." },
  { id: 10, title: "Galaxy S25+", category: "Smartphones", brand: "Samsung", price: 620000, stock: 9, img: "images/Telephones/Samsung/phone_samsung_2.jpeg", featured: true, new: true, desc: "Grand écran et batterie longue durée." },
  { id: 11, title: "Galaxy S24", category: "Smartphones", brand: "Samsung", price: 520000, stock: 12, img: "images/Telephones/Samsung/phone_samsung_3.jpeg", featured: false, new: false, desc: "Smartphone polyvalent avec IA embarquée." },
  { id: 12, title: "Galaxy Z Fold 5", category: "Smartphones", brand: "Samsung", price: 950000, stock: 3, img: "images/Telephones/Samsung/phone_samsung_4.jpeg", featured: true, new: true, desc: "Smartphone pliable révolutionnaire." },
  { id: 13, title: "Galaxy A54", category: "Smartphones", brand: "Samsung", price: 280000, stock: 18, img: "images/Telephones/Samsung/phone_samsung_5.jpeg", featured: false, new: false, desc: "Milieu de gamme avec excellentes photos." },

  // ==================== LAPTOPS ====================
  // MacBook
  { id: 14, title: "MacBook Pro 16\" M3 Max", category: "Laptops", brand: "Apple", price: 2800000, stock: 2, img: "images/PC/Mac/pc_mac_1.jpeg", featured: true, new: true, desc: "Puissance ultime pour pros de la création." },
  { id: 15, title: "MacBook Pro 14\" M3 Pro", category: "Laptops", brand: "Apple", price: 2200000, stock: 4, img: "images/PC/Mac/pc_mac_2.jpeg", featured: true, new: true, desc: "Compact et ultra-performant." },
  { id: 16, title: "MacBook Air 15\" M3", category: "Laptops", brand: "Apple", price: 1600000, stock: 6, img: "images/PC/Mac/pc_mac_3.jpeg", featured: true, new: true, desc: "Grand écran, design fin et léger." },
  { id: 17, title: "MacBook Air 13\" M2", category: "Laptops", brand: "Apple", price: 1200000, stock: 8, img: "images/PC/Mac/pc_mac_4.jpeg", featured: false, new: false, desc: "Idéal pour étudiants et nomades." },
  { id: 18, title: "MacBook Pro 13\" M2", category: "Laptops", brand: "Apple", price: 1400000, stock: 5, img: "images/PC/Mac/pc_mac_5.jpeg", featured: false, new: false, desc: "Performance et portabilité." },

  // ASUS
  { id: 19, title: "ASUS ROG Strix G16", category: "Laptops", brand: "ASUS", price: 1500000, stock: 4, img: "images/PC/Asus/pc_asus_1.jpeg", featured: true, new: true, desc: "PC gamer RTX 4070, écran 165Hz." },
  { id: 20, title: "ASUS TUF Gaming A15", category: "Laptops", brand: "ASUS", price: 950000, stock: 7, img: "images/PC/Asus/pc_asus_2.jpeg", featured: false, new: false, desc: "Gaming robuste et abordable." },
  { id: 21, title: "ASUS Zenbook 14", category: "Laptops", brand: "ASUS", price: 850000, stock: 6, img: "images/PC/Asus/pc_asus_3.jpeg", featured: false, new: false, desc: "Ultra-portable premium pour professionnels." },
  { id: 22, title: "ASUS VivoBook 15", category: "Laptops", brand: "ASUS", price: 480000, stock: 12, img: "images/PC/Asus/pc_asus_4.jpeg", featured: false, new: false, desc: "PC polyvalent pour usage quotidien." },
  { id: 23, title: "ASUS ProArt StudioBook", category: "Laptops", brand: "ASUS", price: 1800000, stock: 3, img: "images/PC/Asus/pc_asus_5.jpeg", featured: true, new: true, desc: "Station de travail pour créateurs 3D." },

  // Dell
  { id: 24, title: "Dell XPS 15", category: "Laptops", brand: "Dell", price: 1600000, stock: 5, img: "images/PC/Dell/pc_dell_1.jpeg", featured: true, new: true, desc: "Écran 4K OLED, design premium." },
  { id: 25, title: "Dell Inspiron 16", category: "Laptops", brand: "Dell", price: 720000, stock: 8, img: "images/PC/Dell/pc_dell_2.jpeg", featured: false, new: false, desc: "Grand écran confortable pour le multimédia." },
  { id: 26, title: "Dell Latitude 14", category: "Laptops", brand: "Dell", price: 980000, stock: 6, img: "images/PC/Dell/pc_dell_3.jpeg", featured: false, new: false, desc: "PC professionnel sécurisé et durable." },

  // HP
  { id: 27, title: "HP Spectre x360 14", category: "Laptops", brand: "HP", price: 1350000, stock: 4, img: "images/PC/HP/pc_hp_1.jpeg", featured: true, new: true, desc: "Convertible 2-en-1 haut de gamme." },
  { id: 28, title: "HP Envy 15", category: "Laptops", brand: "HP", price: 920000, stock: 7, img: "images/PC/HP/pc_hp_2.jpeg", featured: false, new: false, desc: "Design élégant, performance créative." },
  { id: 29, title: "HP Pavilion Gaming", category: "Laptops", brand: "HP", price: 680000, stock: 9, img: "images/PC/HP/pc_hp_3.jpeg", featured: false, new: false, desc: "Gaming accessible avec GTX 1650." },
  { id: 30, title: "HP ProBook 450", category: "Laptops", brand: "HP", price: 550000, stock: 10, img: "images/PC/HP/pc_hp_4.jpeg", featured: false, new: false, desc: "PC business fiable et évolutif." },

  // ==================== CASQUES & ÉCOUTEURS ====================
  { id: 31, title: "Casque 300 BT Pro", category: "Écouteurs", brand: "TechStorm", price: 65000, stock: 8, img: "images/Casque/casque 300 bt_1.png", featured: true, new: true, desc: "Son Hi-Fi, réduction de bruit active." },
  { id: 32, title: "Casque 300 BT Sport", category: "Écouteurs", brand: "TechStorm", price: 48000, stock: 12, img: "images/Casque/casque 300 BT_2.png", featured: false, new: false, desc: "Résistant à la sueur, autonomie 30h." },
  { id: 33, title: "Casque 300 BT Studio", category: "Écouteurs", brand: "TechStorm", price: 72000, stock: 6, img: "images/Casque/casque 300 BT_3.png", featured: true, new: true, desc: "Qualité studio pour producteurs." },
  { id: 34, title: "Casque 300 BT Kids", category: "Écouteurs", brand: "TechStorm", price: 35000, stock: 15, img: "images/Casque/casque 300 BT_4.png", featured: false, new: false, desc: "Limité à 85dB, parfait pour enfants." },
  { id: 35, title: "Casque 300 BT Gaming", category: "Écouteurs", brand: "TechStorm", price: 58000, stock: 10, img: "images/Casque/casque 300 BT_5.png", featured: true, new: false, desc: "Son surround 7.1, micro détachable." },
  { id: 36, title: "Casque 300 BT Lite", category: "Écouteurs", brand: "TechStorm", price: 38000, stock: 18, img: "images/Casque/casque 300 BT_6.png", featured: false, new: false, desc: "Version allégée, confort maximal." },
  { id: 37, title: "Casque 300 BT Travel", category: "Écouteurs", brand: "TechStorm", price: 62000, stock: 7, img: "images/Casque/casque 300 BT_7.png", featured: false, new: false, desc: "Pliable avec étui de transport." },
  { id: 38, title: "Casque 300 BT Deluxe", category: "Écouteurs", brand: "TechStorm", price: 85000, stock: 5, img: "images/Casque/casque 300 BT_8.png", featured: true, new: true, desc: "Version premium, cuir et métal." },
  { id: 39, title: "Casque 300 BT Office", category: "Écouteurs", brand: "TechStorm", price: 52000, stock: 9, img: "images/Casque/casque 300 BT_10.png", featured: false, new: false, desc: "Micro antibruit pour télétravail." },
  { id: 40, title: "Casque 300 BT Bass", category: "Écouteurs", brand: "TechStorm", price: 55000, stock: 11, img: "images/Casque/casque 300 BT_11.png", featured: false, new: false, desc: "Basses profondes pour amateurs EDM." },

  // ==================== TABLETTES ====================
  { id: 41, title: "iPad Pro 12.9\" M2", category: "Tablettes", brand: "Apple", price: 980000, stock: 4, img: "images/Tablette/tablette_1.jpeg", featured: true, new: true, desc: "Écran Liquid Retina XDR, puce M2." },
  { id: 42, title: "Galaxy Tab S9 Ultra", category: "Tablettes", brand: "Samsung", price: 850000, stock: 5, img: "images/Tablette/tablette_2.jpeg", featured: true, new: true, desc: "Écran AMOLED 14.6\", S Pen inclus." },
  { id: 43, title: "iPad Air 10.9\"", category: "Tablettes", brand: "Apple", price: 520000, stock: 8, img: "images/Tablette/tablette_3.jpeg", featured: false, new: false, desc: "Polyvalente avec puce M1." },

  // ==================== APPAREILS PHOTO ====================
  { id: 44, title: "Canon EOS R6 Mark II", category: "Appareils Photo", brand: "Canon", price: 1850000, stock: 3, img: "images/Appareil Photo/app photo_1.png", featured: true, new: true, desc: "Hybride plein format, vidéo 6K." },
  { id: 45, title: "Sony A7 IV", category: "Appareils Photo", brand: "Sony", price: 1950000, stock: 2, img: "images/Appareil Photo/app photo_2.png", featured: true, new: true, desc: "33MP, autofocus IA révolutionnaire." },
  { id: 46, title: "Nikon Z6 III", category: "Appareils Photo", brand: "Nikon", price: 1650000, stock: 4, img: "images/Appareil Photo/app photo_3.png", featured: true, new: true, desc: "Stabilisation 5 axes, RAW 14 bits." },
  { id: 47, title: "Fujifilm X-T5", category: "Appareils Photo", brand: "Fujifilm", price: 1280000, stock: 5, img: "images/Appareil Photo/app photo_4.jpeg", featured: false, new: false, desc: "APS-C 40MP, simulations film légendaires." },
  { id: 48, title: "Canon EOS R10", category: "Appareils Photo", brand: "Canon", price: 780000, stock: 6, img: "images/Appareil Photo/app photo_5.jpeg", featured: false, new: false, desc: "Hybride compact pour débutants." },
  { id: 49, title: "Sony ZV-E10", category: "Appareils Photo", brand: "Sony", price: 620000, stock: 8, img: "images/Appareil Photo/app photo_6.jpeg", featured: false, new: false, desc: "Parfait pour vlogging et contenu créateur." },
  { id: 50, title: "Nikon Z30", category: "Appareils Photo", brand: "Nikon", price: 580000, stock: 7, img: "images/Appareil Photo/app photo_7.jpeg", featured: false, new: false, desc: "Vidéo 4K, écran orientable." },
  { id: 51, title: "Panasonic Lumix S5", category: "Appareils Photo", brand: "Panasonic", price: 1450000, stock: 3, img: "images/Appareil Photo/app photo_8.jpeg", featured: true, new: false, desc: "Plein format vidéo-centrique." }
];
*/
document.addEventListener('DOMContentLoaded', async () => {
    const apiUrl = import.meta.env.VITE_API_URL;
    let PRODUCTS = [];
    try {
        const response = await fetch(`${apiUrl}/api/public/products`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        const data = await response.json();
        
        // Robustesse : accepter différents formats ( {products: []}, {data: []}, ou directly [] )
        if (response.ok) {
            // L'utilisateur montre une réponse : { success: true, data: { products: [...], pagination: {...} } }
            // Ou parfois direct : { products: [...] }
            PRODUCTS = (data.data && data.data.products) || data.products || (Array.isArray(data) ? data : []);
            
            console.log(`Succès ! ${PRODUCTS.length} produits récupérés.`);
            
            if (PRODUCTS.length > 0) {
                console.log("Premier produit :", PRODUCTS[0].title || PRODUCTS[0].name);
            }
        } else {
            console.error("L'API a renvoyé une erreur :", response.status);
            // On peut essayer de charger les produits commentés en fallback si besoin
        }
    } catch (error) {
        console.error('Erreur lors de la récupération des produits :', error);
        // On reste sur PRODUCTS = [] par défaut
    }

// Extraire les marques uniques (Robustesse si PRODUCTS est vide)
const allBrands = (PRODUCTS && PRODUCTS.length) 
    ? [...new Set(PRODUCTS.map(p => p.brand).filter(b => b))].sort() 
    : [];

// Config pagination
const PAGE_SIZE = 6;
let currentPage = 1;
let filtered = PRODUCTS ? [...PRODUCTS] : [];

// DOM elements
const cardsEl = document.getElementById('cards');
const shownCountEl = document.getElementById('shown-count');
const productCountEl = document.getElementById('product-count');
const sortEl = document.getElementById('sort');
const searchEl = document.getElementById('search');
const priceRangeEl = document.getElementById('price-range');
const priceMaxEl = document.getElementById('price-max');
const categoryFiltersEl = document.getElementById('category-filters');
const brandFiltersEl = document.getElementById('brand-filters');
const prevPageBtn = document.getElementById('prev-page');
const nextPageBtn = document.getElementById('next-page');
const pagesEl = document.getElementById('pages');
const applyFiltersBtn = document.getElementById('apply-filters');
const resetFiltersBtn = document.getElementById('reset-filters');
const cartCountEl = document.getElementById('cart-count');


async function updateCartCount() {
    try {
        // 1. Essayer d'abord le LocalStorage
        let localCart = JSON.parse(localStorage.getItem('techstorm_cart') || '[]');
        if (localCart.length > 0) {
            const totalItems = localCart.reduce((sum, item) => sum + (item.quantity || 1), 0);
            if (cartCountEl) cartCountEl.textContent = totalItems;
            return;
        }

        // 2. Si vide localement, essayer l'API
        const token = localStorage.getItem('user_token');
        if (!token) {
            if (cartCountEl) cartCountEl.textContent = '0';
            return;
        }

        const response = await fetch(`${import.meta.env.VITE_API_URL}/api/client/cart`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            const apiItems = data.items || [];
            // Mettre en cache dans LocalStorage
            localStorage.setItem('techstorm_cart', JSON.stringify(apiItems));
            const totalItems = apiItems.reduce((sum, item) => sum + (item.quantity || 1), 0);
            if (cartCountEl) cartCountEl.textContent = totalItems;
        }
    } catch (error) {
        console.error('Erreur lors de la mise à jour du compteur:', error);
    }
}

// Ajouter un produit au panier (Logique hybride : LocalStorage + API)
async function addToCart(productId) {
    // 0. Vérifier l'authentification
    if (!requireAuth()) {
        return false; // Redirection déjà déclenchée par requireAuth()
    }

    try {
        // 1. Trouver le produit dans la liste locale pour avoir ses détails
        const product = PRODUCTS.find(p => (p.id || p._id || p.ID) == productId);
        if (!product) {
            console.error('Produit non trouvé localement:', productId);
            return false;
        }

        // 2. Mise à jour immédiate du LocalStorage pour la réactivité
        let localCart = JSON.parse(localStorage.getItem('techstorm_cart') || '[]');
        const existingIndex = localCart.findIndex(item => (item.id || item.product_id) == productId);

        if (existingIndex > -1) {
            localCart[existingIndex].quantity++;
        } else {
            localCart.push({
                product_id: productId,
                id: productId, // Pour la compatibilité
                title: product.name || product.title,
                name: product.name || product.title,
                price: product.price,
                img: product.image_url || product.img,
                quantity: 1,
                selected_attributes: { color: 'white' }
            });
        }
        localStorage.setItem('techstorm_cart', JSON.stringify(localCart));
        
        // Mise à jour immédiate du compteur UI
        const totalItems = localCart.reduce((sum, item) => sum + item.quantity, 0);
        if (cartCountEl) {
            cartCountEl.textContent = totalItems;
        }

        // 3. Synchronisation avec l'API en arrière-plan (si connecté)
        const token = localStorage.getItem('user_token');
        if (token) {
            fetch(`${import.meta.env.VITE_API_URL}/api/client/cart/items`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: 1,
                    attributes: { color: 'white' }
                })
            }).then(async res => {
                const data = await res.json();
                if (res.ok) {
                    // Mettre à jour l'ID de la ligne dans LocalStorage pour les futurs DELETE/PATCH
                    let updatedCart = JSON.parse(localStorage.getItem('techstorm_cart') || '[]');
                    const itemIdx = updatedCart.findIndex(i => (i.product_id || i.id) == productId);
                    if (itemIdx > -1) {
                        // Supposons que l'API renvoie { cart_line_id: ... } ou { data: { id: ... } }
                        const newLineId = data.cart_line_id || (data.data && data.data.id) || data.id;
                        if (newLineId) {
                            updatedCart[itemIdx].cart_line_id = newLineId;
                            localStorage.setItem('techstorm_cart', JSON.stringify(updatedCart));
                        }
                    }
                } else {
                    console.warn('Erreur synchro API panier:', data);
                }
            }).catch(err => console.error('Erreur réseau synchro API:', err));
        }

        return true;
    } catch (error) {
        console.error('Erreur add to cart (hybrid):', error);
        return false;
    }
}

// Helpers
function formatPrice(n) {
    if (n === undefined || n === null) return '0';
    // Si c'est déjà une chaîne avec FCFA, on le nettoie
    let val = n.toString().replace(/FCFA/gi, '').trim();
    // On enlève les espaces existants pour reformater proprement
    val = val.replace(/\s/g, '');
    const num = parseFloat(val);
    if (isNaN(num)) return val;
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '\u00A0');
}

// Générer dynamiquement les filtres de marques
function initBrandFilters() {
    brandFiltersEl.innerHTML = '';
    allBrands.forEach(brand => {
        const li = document.createElement('li');
        li.innerHTML = `<label><input type="checkbox" value="${brand}" /> <span>${brand}</span></label>`;
        brandFiltersEl.appendChild(li);
    });
}

function renderCards(items) {
    cardsEl.innerHTML = '';
    const start = (currentPage - 1) * PAGE_SIZE;
    const pageItems = items.slice(start, start + PAGE_SIZE);

    if (pageItems.length === 0) {
        cardsEl.innerHTML = `<div class="empty">Aucun produit trouvé. Essayez d'élargir vos filtres.</div>`;
    } else {
        for (const p of pageItems) {
            const article = document.createElement('article');
            article.className = 'card';
            
            // Mapping de champs robuste
            const productId = p.id || p._id || p.ID;
            const title = p.name || p.title || 'Produit sans nom';
            const brand = p.brand || '-';
            const stock = p.quantity !== undefined ? p.quantity : (p.stock !== undefined ? p.stock : 0);
            const price = p.price !== undefined ? p.price : 0;
            const img = p.image_url || p.img || p.image || p.photo || '';
            const imgSrc = img.startsWith('http') ? img : (import.meta.env.VITE_API_URL + '/' + img);

            article.setAttribute('data-id', productId);

            // Badges
            let badges = '';
            if (p.new) badges += '<span class="badge badge-new">NOUVEAU</span>';
            if (p.featured) badges += '<span class="badge badge-featured">⭐</span>';

            article.innerHTML = `
        <div class="card-badges">${badges}</div>
        <div class="card-thumb">
          <img src="${imgSrc}" alt="${title}" onerror="this.onerror=null;this.src='/images/default-product.png'" />
        </div>
        <div class="card-body">
          <div class="card-brand">${brand}</div>
          <h4>${title}</h4>
          <div class="meta">
            <div class="qty">Stock: <span>${stock}</span></div>
            <div class="price"><span>${formatPrice(price)}</span><small>FCFA</small></div>
          </div>
          <div class="card-actions">
            <button class="btn-add" data-id="${productId}">Ajouter au panier</button>
            <button class="btn-quiet quickview" data-id="${productId}">Aperçu</button>
          </div>
        </div>
      `;
            cardsEl.appendChild(article);
        }
    }

    shownCountEl.textContent = items.length;
    renderPagination(items.length);
    attachCardEvents();
}

function renderPagination(totalItems) {
    const totalPages = Math.max(1, Math.ceil(totalItems / PAGE_SIZE));
    pagesEl.innerHTML = '';
    for (let i = 1; i <= totalPages; i++) {
        const btn = document.createElement('button');
        btn.className = 'page-btn';
        btn.textContent = i;
        if (i === currentPage) btn.classList.add('active');
        btn.addEventListener('click', () => { currentPage = i; renderCards(filtered); });
        pagesEl.appendChild(btn);
    }
    prevPageBtn.disabled = currentPage === 1;
    nextPageBtn.disabled = currentPage === totalPages;
}

if (prevPageBtn) {
    prevPageBtn.addEventListener('click', () => { if (currentPage>1) { currentPage--; renderCards(filtered); }});
}
if (nextPageBtn) {
    nextPageBtn.addEventListener('click', () => {
        const totalPages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
        if (currentPage < totalPages) { currentPage++; renderCards(filtered); }
    });
}

function applyFilters() {
    const query = searchEl.value.trim().toLowerCase();
    const maxPrice = parseInt(priceRangeEl.value, 10);
    const checkedCats = Array.from(categoryFiltersEl.querySelectorAll('input:checked')).map(i => i.value);
    const checkedBrands = Array.from(brandFiltersEl.querySelectorAll('input:checked')).map(i => i.value);

    filtered = PRODUCTS.filter(p => {
        if (p.price > maxPrice) return false;
        if (checkedCats.length && !checkedCats.includes(p.category)) return false;
        if (checkedBrands.length && !checkedBrands.includes(p.brand)) return false;
        if (query && !(p.title.toLowerCase().includes(query) || (p.desc && p.desc.toLowerCase().includes(query)))) return false;
        return true;
    });

    // Tri
    const sortVal = sortEl.value;
    if (sortVal === 'price-asc') filtered.sort((a,b)=>a.price-b.price);
    else if (sortVal === 'price-desc') filtered.sort((a,b)=>b.price-a.price);
    else if (sortVal === 'new') filtered.sort((a,b)=> (b.new?1:0) - (a.new?1:0));
    else filtered.sort((a,b)=> (b.featured?1:0) - (a.featured?1:0));

    currentPage = 1;
    renderCards(filtered);
}

// Events pour boutons dans chaque carte
function attachCardEvents() {
    // Add to cart
    document.querySelectorAll('.btn-add').forEach(btn => {
        btn.removeEventListener('click', onAddClick);
        btn.addEventListener('click', onAddClick);
    });

    // Quickview
    document.querySelectorAll('.quickview').forEach(btn => {
        btn.removeEventListener('click', onQuickView);
        btn.addEventListener('click', onQuickView);
    });
}

async function onAddClick(e) {
    const id = this.dataset.id || e.currentTarget.dataset.id;
    console.log('Ajout au panier du produit ID:', id);
    const success = await addToCart(id);

    if (success) {
        this.textContent = 'Ajouté ✓';
        this.style.background = '#28e0d6';
        setTimeout(() => {
            this.textContent = 'Ajouter au panier';
            this.style.background = '';
        }, 900);
    }
}

function onQuickView(e) {
    const id = (this.dataset.id || e.currentTarget.dataset.id);
    const product = PRODUCTS.find(p => (p.id || p._id || p.ID) == id);
    openQuickView(product);
}

// Quick view modal
const modal = document.getElementById('quickview');
const modalBody = document.getElementById('quickview-body');
const modalCloseBtn = document.querySelector('.modal-close');
if (modalCloseBtn) modalCloseBtn.addEventListener('click', closeModal);
if (modal) modal.addEventListener('click', (ev)=> { if (ev.target === modal) closeModal(); });

function openQuickView(p) {
    if (!p) return;

    const productId = p.id || p._id || p.ID;
    const title = p.title || p.name || 'Produit sans nom';
    const brand = p.brand || '-';
    const img = p.img || p.image || p.photo || '';
    const imgSrc = img.startsWith('http') ? img : (import.meta.env.VITE_API_URL + '/' + img);

    modalBody.innerHTML = `
    <div class="product-container">
      <!-- Galerie d'images produit -->
      <div class="product-gallery">
        <div class="main-image">
          <img src="${imgSrc}" alt="${title}" id="mainProductImage" onerror="this.src='/images/default-product.png'" />
        </div>
        <div class="thumbnail-gallery">
          <div class="thumbnail active" onclick="changeImage(this, '${imgSrc}')">
            <img src="${imgSrc}" alt="Vue 1" onerror="this.src='/images/default-product.png'" />
          </div>
        </div>
      </div>

      <!-- Informations produit -->
      <div class="product-info">
        <div class="product-brand-tag">${brand}</div>
        <h1 class="product-title">${title}</h1>
        
        <div class="product-category">
          <span>${p.category || 'Général'}</span>
          ${p.new ? '<span class="badge-new-inline">NOUVEAU</span>' : ''}
          ${p.featured ? '<span class="badge-featured-inline">POPULAIRE</span>' : ''}
        </div>

        <div class="product-rating">
          <div class="stars">
            <span class="star filled">★</span>
            <span class="star filled">★</span>
            <span class="star filled">★</span>
            <span class="star filled">★</span>
            <span class="star">★</span>
          </div>
          <span class="rating-score">4.5/5</span>
          <span class="rating-count">(${Math.floor(Math.random() * 200 + 50)} avis)</span>
        </div>

        <div class="product-price">
          <span class="current-price">${formatPrice(p.price)} FCFA</span>
        </div>

        <div class="product-stock ${p.stock < 5 ? 'low-stock' : ''}">
          ${p.stock < 5 ? '⚠️' : '✓'} ${p.stock} en stock
        </div>

        <div class="product-description">
          <p>${p.desc}</p>
        </div>

        <div class="product-actions">
          <button class="btn-add-cart" onclick="addToCartFromModal('${productId}')">
            AJOUTER AU PANIER
          </button>
          <button class="btn-buy-now">Acheter maintenant</button>
        </div>
      </div>
    </div>
  `;

    modal.setAttribute('aria-hidden','false');
    modal.classList.add('open');
}

// Fonction globale pour ajouter au panier depuis le modal
window.addToCartFromModal = function(id) {
    // Vérifier l'authentification avant d'ajouter au panier
    if (!requireAuth()) {
        return; // Redirection en cours
    }
    
    const success = addToCart(id);

    if (success) {
        const btn = event.target;
        btn.textContent = 'Ajouté ✓';
        btn.style.background = '#28e0d6';
        setTimeout(() => {
            btn.textContent = 'AJOUTER AU PANIER';
            btn.style.background = '';
        }, 1500);
    }
};

// Fonction globale pour changer l'image
window.changeImage = function(thumb, imgSrc) {
    document.getElementById('mainProductImage').src = imgSrc;
    document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
    thumb.classList.add('active');
};

function closeModal() {
    modal.setAttribute('aria-hidden','true');
    modal.classList.remove('open');
}

// Price range UI
if (priceRangeEl) {
    priceRangeEl.addEventListener('input', ()=> {
        priceMaxEl.textContent = formatPrice(priceRangeEl.value);
    });
}

// Recherche en "enter"
if (searchEl) {
    searchEl.addEventListener('keyup', (e) => {
        if (e.key === 'Enter') applyFilters();
    });
}

// Buttons apply / reset
if (applyFiltersBtn) applyFiltersBtn.addEventListener('click', applyFilters);
if (resetFiltersBtn) {
    resetFiltersBtn.addEventListener('click', () => {
        searchEl.value = '';
        priceRangeEl.value = priceRangeEl.max;
        priceMaxEl.textContent = formatPrice(priceRangeEl.max);
        categoryFiltersEl.querySelectorAll('input').forEach(i => i.checked = false);
        brandFiltersEl.querySelectorAll('input').forEach(i => i.checked = false);
        sortEl.value = 'featured';
        applyFilters();
    });
}

// tri change
if (sortEl) sortEl.addEventListener('change', applyFilters);

// initial render - only run on catalogue page
if (cardsEl && brandFiltersEl) {
    (function init() {
        initBrandFilters();
        if (priceMaxEl && priceRangeEl) priceMaxEl.textContent = formatPrice(priceRangeEl.value);
        filtered = [...PRODUCTS];
        renderCards(filtered);
        updateCartCount(); // Mise à jour initiale du compteur
    })();
}

// Accessibilité: focus trap simple (optionnel)
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeModal();
});
});

