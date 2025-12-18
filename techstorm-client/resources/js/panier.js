
// ========================================
// CART.JS - Gestion du Panier Interactif avec Formulaire de Commande
// ========================================

// Récupération du panier depuis localStorage
function getCart() {
    const cart = localStorage.getItem('techstorm_cart');
    return cart ? JSON.parse(cart) : [];
}

// Sauvegarde du panier dans localStorage
function saveCart(cart) {
    localStorage.setItem('techstorm_cart', JSON.stringify(cart));
    updateCartCount();
}

// Mise à jour du compteur dans le header
function updateCartCount() {
    const cart = getCart();
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    const compteurEl = document.querySelector('.compteur');
    if (compteurEl) {
        compteurEl.textContent = `(${totalItems})`;
    }
}

// Formatage du prix
function formatPrice(n) {
    return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '\u00A0');
}

// Génération du HTML pour les options de couleur
function generateColorOptions(productId, selectedColor) {
    const colors = [
        { name: 'Blanc', value: 'white', style: 'background-color: #ffffff; border: 1px solid #ccc;' },
        { name: 'Noir', value: 'black', style: 'background-color: #000000;' },
        { name: 'Rouge', value: 'red', style: 'background-color: #ff4444;' },
        { name: 'Bleu', value: 'blue', style: 'background-color: #4444ff;' }
    ];

    return colors.map((color, index) => `
    <label class="color-option">
      <input type="radio" name="color${productId}" value="${color.value}" ${selectedColor === color.value ? 'checked' : ''}>
      <div class="color-box" style="${color.style}"></div>
    </label>
  `).join('');
}

// Obtenir le nom de la couleur
function getColorName(colorValue) {
    const colorMap = {
        'white': 'Blanc',
        'black': 'Noir',
        'red': 'Rouge',
        'blue': 'Bleu'
    };
    return colorMap[colorValue] || 'Blanc';
}

// Affichage des articles du panier
function renderCartItems() {
    const cart = getCart();
    const articleSection = document.querySelector('.article-panier');

    if (!articleSection) return;

    // Vider le contenu existant sauf le header
    const header = articleSection.querySelector('.panier-header');
    articleSection.innerHTML = '';
    if (header) articleSection.appendChild(header);

    if (cart.length === 0) {
        articleSection.innerHTML += `
      <div class="empty-cart" style="text-align: center; padding: 3rem; grid-column: 1/-1;">
        <i class="fas fa-shopping-cart" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
        <p style="font-size: 1.2rem; color: #666; margin-bottom: 1rem;">Votre panier est vide</p>
        <a href="/catalogue" class="checkout-btn" style="margin-top: 1rem; display: inline-block; text-decoration: none; padding: 0.8rem 2rem;">
          Continuer vos achats
        </a>
      </div>
    `;
        updateSummary();
        return;
    }

    // Générer les articles
    cart.forEach((item, index) => {
        const article = document.createElement('div');
        article.className = 'article';
        article.dataset.index = index;

        article.innerHTML = `
      <input type="checkbox" class="article-checkbox" data-index="${index}" ${item.selected ? 'checked' : ''}>
      <img src="${item.img.startsWith('http') ? item.img : (import.meta.env.VITE_API_URL + '/' + item.img)}" alt="${item.title}" />
      <div class="article-info">
        <div class="article-name">
          <label for="dialog${index}" style="cursor: pointer; color: inherit;">${item.title}</label>
          <input type="checkbox" id="dialog${index}">
          <div class="dialog-overlay">
            <div class="dialog">
              <label for="dialog${index}" class="dialog-close">×</label>
              <h3 class="dialog-title">${item.title}</h3>
              
              <div class="dialog-section">
                <label>Couleur</label>
                <div class="color-options" data-index="${index}">
                  ${generateColorOptions(index, item.color)}
                </div>
              </div>
              
              <div class="dialog-section">
                <label>Quantité</label>
                <div class="dialog-quantity">
                  <input type="number" value="${item.quantity}" min="1" max="${item.stock}" data-index="${index}" class="dialog-qty-input">
                </div>
              </div>
              
              <div class="dialog-actions">
                <label for="dialog${index}" class="dialog-btn cancel">Annuler</label>
                <label for="dialog${index}" class="dialog-btn confirm" data-index="${index}">Confirmer</label>
              </div>
            </div>
          </div>
        </div>
        <div class="article-details">
          <span><i class="fas fa-palette"></i> ${getColorName(item.color)}</span>
          <span><i class="fas fa-box"></i> ${item.quantity} unité${item.quantity > 1 ? 's' : ''}</span>
        </div>
      </div>
      <div class="article-actions">
        <div class="quantity-control">
          <input type="number" value="${item.quantity}" min="1" max="${item.stock}" data-index="${index}" class="qty-input">
        </div>
        <div class="article-price">${formatPrice(item.price * item.quantity)} FCFA</div>
        <a href="#" class="delete-btn" data-index="${index}">
          <i class="fas fa-trash"></i>
        </a>
      </div>
    `;

        articleSection.appendChild(article);
    });

    attachCartEvents();
    updateSummary();
}

// Attacher les événements aux éléments du panier
function attachCartEvents() {
    // Checkbox individuelles
    document.querySelectorAll('.article-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const index = parseInt(this.dataset.index);
            const cart = getCart();
            cart[index].selected = this.checked;
            saveCart(cart);
            updateSummary();
        });
    });

    // Checkbox "Tout sélectionner"
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const cart = getCart();
            cart.forEach(item => item.selected = this.checked);
            saveCart(cart);
            renderCartItems();
        });
    }

    // Changement de quantité (input principal)
    document.querySelectorAll('.qty-input').forEach(input => {
        input.addEventListener('change', function() {
            const index = parseInt(this.dataset.index);
            const newQty = parseInt(this.value);
            const cart = getCart();

            if (newQty > 0 && newQty <= cart[index].stock) {
                cart[index].quantity = newQty;
                saveCart(cart);
                renderCartItems();
            } else {
                this.value = cart[index].quantity;
                alert(`Quantité disponible: ${cart[index].stock}`);
            }
        });
    });

    // Boutons de suppression
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const index = parseInt(this.dataset.index);
            if (confirm('Voulez-vous vraiment supprimer cet article ?')) {
                const cart = getCart();
                cart.splice(index, 1);
                saveCart(cart);
                renderCartItems();
            }
        });
    });

    // Boutons "Confirmer" dans les dialogues
    document.querySelectorAll('.dialog-btn.confirm').forEach(btn => {
        btn.addEventListener('click', function() {
            const index = parseInt(this.dataset.index);
            const cart = getCart();

            // Récupérer la nouvelle couleur
            const colorInput = document.querySelector(`.color-options[data-index="${index}"] input[type="radio"]:checked`);
            if (colorInput) {
                cart[index].color = colorInput.value;
            }

            // Récupérer la nouvelle quantité
            const qtyInput = document.querySelector(`.dialog-qty-input[data-index="${index}"]`);
            if (qtyInput) {
                const newQty = parseInt(qtyInput.value);
                if (newQty > 0 && newQty <= cart[index].stock) {
                    cart[index].quantity = newQty;
                }
            }

            saveCart(cart);
            renderCartItems();
        });
    });
}

// Mise à jour du résumé (prix total)
function updateSummary() {
    const cart = getCart();
    const selectedItems = cart.filter(item => item.selected);

    const subtotal = selectedItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const delivery = selectedItems.length > 0 ? 1000 : 0;
    const total = subtotal + delivery;

    // Mise à jour du DOM
    const summaryLines = document.querySelectorAll('.summary-line');
    if (summaryLines.length >= 3) {
        summaryLines[0].querySelector('span:last-child').textContent = `${formatPrice(subtotal)} FCFA`;
        summaryLines[1].querySelector('span:last-child').textContent = `${formatPrice(delivery)} FCFA`;
        summaryLines[2].querySelector('span:last-child').textContent = `${formatPrice(total)} FCFA`;
    }

    // Désactiver le bouton de paiement si rien n'est sélectionné
    const checkoutBtn = document.querySelector('.checkout-btn');
    if (checkoutBtn) {
        if (selectedItems.length === 0) {
            checkoutBtn.disabled = true;
            checkoutBtn.style.opacity = '0.5';
            checkoutBtn.style.cursor = 'not-allowed';
        } else {
            checkoutBtn.disabled = false;
            checkoutBtn.style.opacity = '1';
            checkoutBtn.style.cursor = 'pointer';
        }
    }
}

// ========================================
// FORMULAIRE DE COMMANDE
// ========================================

function showOrderForm() {
    const cart = getCart();
    const selectedItems = cart.filter(item => item.selected);

    if (selectedItems.length === 0) {
        alert('Veuillez sélectionner au moins un article');
        return;
    }

    // Calculer les totaux
    const subtotal = selectedItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const delivery = 1000;
    const total = subtotal + delivery;

    // Créer le modal du formulaire
    const formModal = document.createElement('div');
    formModal.id = 'order-form-modal';
    formModal.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 10000;
    overflow-y: auto;
    padding: 2rem;
  `;

    formModal.innerHTML = `
    <div style="background: white; border-radius: 12px; max-width: 600px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
      
      <!-- Header -->
      <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; border-radius: 12px 12px 0 0;">
        <h2 style="margin: 0; font-size: 1.8rem;">
          <i class="fas fa-clipboard-list"></i> Finaliser la commande
        </h2>
        <p style="margin: 0.5rem 0 0 0; opacity: 0.9;">Veuillez remplir vos informations</p>
      </div>

      <!-- Form Content -->
      <div style="padding: 2rem;">
        
        <!-- Résumé de la commande -->
        <div style="background: #f7f7f7; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
          <h3 style="margin: 0 0 1rem 0; color: #333; font-size: 1.2rem;">
            <i class="fas fa-shopping-bag"></i> Récapitulatif
          </h3>
          <div style="display: flex; flex-direction: column; gap: 0.5rem;">
            ${selectedItems.map(item => `
              <div style="display: flex; justify-content: space-between; font-size: 0.95rem;">
                <span>${item.title} <small>(x${item.quantity})</small></span>
                <span style="font-weight: 600;">${formatPrice(item.price * item.quantity)} FCFA</span>
              </div>
            `).join('')}
            <hr style="margin: 0.5rem 0; border: none; border-top: 1px solid #ddd;">
            <div style="display: flex; justify-content: space-between; font-size: 0.95rem;">
              <span>Livraison</span>
              <span style="font-weight: 600;">${formatPrice(delivery)} FCFA</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 1.2rem; color: #667eea; margin-top: 0.5rem;">
              <strong>Total</strong>
              <strong>${formatPrice(total)} FCFA</strong>
            </div>
          </div>
        </div>

        <!-- Formulaire -->
        <form id="checkout-form">
          
          <!-- Informations personnelles -->
          <div style="margin-bottom: 2rem;">
            <h3 style="margin: 0 0 1rem 0; color: #333; font-size: 1.1rem; border-bottom: 2px solid #667eea; padding-bottom: 0.5rem;">
              <i class="fas fa-user"></i> Informations personnelles
            </h3>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
              <div>
                <label style="display: block; margin-bottom: 0.3rem; color: #555; font-size: 0.9rem;">Prénom *</label>
                <input type="text" name="firstName" required style="width: 100%; padding: 0.7rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
              </div>
              <div>
                <label style="display: block; margin-bottom: 0.3rem; color: #555; font-size: 0.9rem;">Nom *</label>
                <input type="text" name="lastName" required style="width: 100%; padding: 0.7rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
              </div>
            </div>

            <div style="margin-bottom: 1rem;">
              <label style="display: block; margin-bottom: 0.3rem; color: #555; font-size: 0.9rem;">Email *</label>
              <input type="email" name="email" required style="width: 100%; padding: 0.7rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
            </div>

            <div style="margin-bottom: 1rem;">
              <label style="display: block; margin-bottom: 0.3rem; color: #555; font-size: 0.9rem;">Téléphone *</label>
              <input type="tel" name="phone" required placeholder="+237 6XX XXX XXX" style="width: 100%; padding: 0.7rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
            </div>
          </div>

          <!-- Adresse de livraison -->
          <div style="margin-bottom: 2rem;">
            <h3 style="margin: 0 0 1rem 0; color: #333; font-size: 1.1rem; border-bottom: 2px solid #667eea; padding-bottom: 0.5rem;">
              <i class="fas fa-map-marker-alt"></i> Adresse de livraison
            </h3>
            
            <div style="margin-bottom: 1rem;">
              <label style="display: block; margin-bottom: 0.3rem; color: #555; font-size: 0.9rem;">Adresse complète *</label>
              <input type="text" name="address" required placeholder="Ex: Akwa, Rue de la Joie" style="width: 100%; padding: 0.7rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
              <div>
                <label style="display: block; margin-bottom: 0.3rem; color: #555; font-size: 0.9rem;">Ville *</label>
                <select name="city" required style="width: 100%; padding: 0.7rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; background: white;">
                  <option value="">Sélectionnez</option>
                  <option value="douala">Douala</option>
                  <option value="yaounde">Yaoundé</option>
                  <option value="bafoussam">Bafoussam</option>
                  <option value="garoua">Garoua</option>
                  <option value="bamenda">Bamenda</option>
                </select>
              </div>
              <div>
                <label style="display: block; margin-bottom: 0.3rem; color: #555; font-size: 0.9rem;">Quartier</label>
                <input type="text" name="quarter" placeholder="Ex: Bonamoussadi" style="width: 100%; padding: 0.7rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
              </div>
            </div>
          </div>

          <!-- Mode de paiement -->
          <div style="margin-bottom: 2rem;">
            <h3 style="margin: 0 0 1rem 0; color: #333; font-size: 1.1rem; border-bottom: 2px solid #667eea; padding-bottom: 0.5rem;">
              <i class="fas fa-credit-card"></i> Mode de paiement
            </h3>
            
            <div style="display: flex; flex-direction: column; gap: 0.8rem;">
              <label style="display: flex; align-items: center; padding: 1rem; border: 2px solid #ddd; border-radius: 8px; cursor: pointer; transition: all 0.3s;" class="payment-option">
                <input type="radio" name="payment" value="mobile_money" required style="margin-right: 1rem; width: 20px; height: 20px;">
                <div style="flex: 1;">
                  <strong style="display: block; color: #333;">Mobile Money</strong>
                  <small style="color: #666;">Orange Money, MTN MoMo</small>
                </div>
                <i class="fas fa-mobile-alt" style="font-size: 1.5rem; color: #667eea;"></i>
              </label>

              <label style="display: flex; align-items: center; padding: 1rem; border: 2px solid #ddd; border-radius: 8px; cursor: pointer; transition: all 0.3s;" class="payment-option">
                <input type="radio" name="payment" value="cash" required style="margin-right: 1rem; width: 20px; height: 20px;">
                <div style="flex: 1;">
                  <strong style="display: block; color: #333;">Paiement à la livraison</strong>
                  <small style="color: #666;">Espèces à la réception</small>
                </div>
                <i class="fas fa-money-bill-wave" style="font-size: 1.5rem; color: #28a745;"></i>
              </label>
            </div>
          </div>

          <!-- Notes -->
          <div style="margin-bottom: 2rem;">
            <label style="display: block; margin-bottom: 0.3rem; color: #555; font-size: 0.9rem;">
              <i class="fas fa-comment"></i> Instructions de livraison (optionnel)
            </label>
            <textarea name="notes" rows="3" placeholder="Ex: Sonner à l'entrée, ne pas livrer avant 14h..." style="width: 100%; padding: 0.7rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; resize: vertical;"></textarea>
          </div>

          <!-- Boutons d'action -->
          <div style="display: flex; gap: 1rem; margin-top: 2rem;">
            <button type="button" id="cancel-order" style="flex: 1; padding: 1rem; background: #e0e0e0; border: none; border-radius: 8px; font-size: 1rem; cursor: pointer; font-weight: 600; color: #555;">
              <i class="fas fa-times"></i> Annuler
            </button>
            <button type="submit" style="flex: 2; padding: 1rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 1rem; cursor: pointer; font-weight: 600; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);">
              <i class="fas fa-check-circle"></i> Valider la commande
            </button>
          </div>

        </form>
      </div>
    </div>
  `;

    document.body.appendChild(formModal);

    // Style pour les options de paiement au hover
    const style = document.createElement('style');
    style.textContent = `
    .payment-option:hover {
      border-color: #667eea !important;
      background: #f0f4ff;
    }
    .payment-option input:checked {
      accent-color: #667eea;
    }
    .payment-option:has(input:checked) {
      border-color: #667eea !important;
      background: #f0f4ff;
    }
  `;
    document.head.appendChild(style);

    // Événement fermeture
    document.getElementById('cancel-order').addEventListener('click', () => {
        formModal.remove();
    });

    // Événement soumission
    document.getElementById('checkout-form').addEventListener('submit', (e) => {
        e.preventDefault();

        // Récupérer les données du formulaire
        const formData = new FormData(e.target);
        const orderData = {
            customer: {
                firstName: formData.get('firstName'),
                lastName: formData.get('lastName'),
                email: formData.get('email'),
                phone: formData.get('phone')
            },
            delivery: {
                address: formData.get('address'),
                city: formData.get('city'),
                quarter: formData.get('quarter'),
                notes: formData.get('notes')
            },
            payment: formData.get('payment'),
            items: selectedItems,
            summary: {
                subtotal: subtotal,
                delivery: delivery,
                total: total
            },
            orderDate: new Date().toISOString(),
            orderNumber: 'TS-' + Date.now()
        };

        // Simuler l'envoi de la commande
        simulateOrderSubmission(orderData, formModal);
    });
}

// Simulation d'envoi de commande
function simulateOrderSubmission(orderData, formModal) {
    // Afficher un loader
    formModal.innerHTML = `
    <div style="background: white; border-radius: 12px; padding: 3rem; text-align: center; max-width: 500px;">
      <div style="margin-bottom: 2rem;">
        <div class="loader" style="border: 4px solid #f3f3f3; border-top: 4px solid #667eea; border-radius: 50%; width: 60px; height: 60px; animation: spin 1s linear infinite; margin: 0 auto;"></div>
      </div>
      <h3 style="color: #333; margin-bottom: 1rem;">Traitement de votre commande...</h3>
      <p style="color: #666;">Veuillez patienter</p>
    </div>
  `;

    // Ajouter l'animation du loader
    const spinStyle = document.createElement('style');
    spinStyle.textContent = `
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  `;
    document.head.appendChild(spinStyle);

    // Simuler un délai d'envoi (2-3 secondes)
    setTimeout(() => {
        // Afficher la confirmation
        showOrderConfirmation(orderData, formModal);

        // Sauvegarder la commande dans localStorage
        saveOrder(orderData);

        // Vider le panier des articles commandés
        clearOrderedItems(orderData.items);

    }, 2500);
}

// Afficher la confirmation de commande
function showOrderConfirmation(orderData, formModal) {
    formModal.innerHTML = `
    <div style="background: white; border-radius: 12px; max-width: 600px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
      
      <!-- Header avec animation de succès -->
      <div style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 3rem 2rem; text-align: center;">
        <div style="font-size: 4rem; margin-bottom: 1rem; animation: bounceIn 0.6s;">
          <i class="fas fa-check-circle"></i>
        </div>
        <h2 style="margin: 0; font-size: 1.8rem;">Commande validée !</h2>
        <p style="margin: 0.5rem 0 0 0; opacity: 0.9;">Merci pour votre confiance</p>
      </div>

      <!-- Contenu -->
      <div style="padding: 2rem;">
        
        <!-- Numéro de commande -->
        <div style="background: #f0f9ff; border-left: 4px solid #667eea; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
              <small style="color: #666; display: block; margin-bottom: 0.3rem;">Numéro de commande</small>
              <strong style="font-size: 1.3rem; color: #667eea;">${orderData.orderNumber}</strong>
            </div>
            <i class="fas fa-receipt" style="font-size: 2rem; color: #667eea; opacity: 0.3;"></i>
          </div>
        </div>

        <!-- Informations client -->
        <div style="margin-bottom: 2rem;">
          <h3 style="margin: 0 0 1rem 0; color: #333; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-user-check" style="color: #667eea;"></i> Informations de livraison
          </h3>
          <div style="background: #f7f7f7; padding: 1rem; border-radius: 8px; line-height: 1.8;">
            <p style="margin: 0;"><strong>${orderData.customer.firstName} ${orderData.customer.lastName}</strong></p>
            <p style="margin: 0; color: #666;"><i class="fas fa-envelope" style="width: 20px;"></i> ${orderData.customer.email}</p>
            <p style="margin: 0; color: #666;"><i class="fas fa-phone" style="width: 20px;"></i> ${orderData.customer.phone}</p>
            <p style="margin: 0; color: #666;"><i class="fas fa-map-marker-alt" style="width: 20px;"></i> ${orderData.delivery.address}, ${orderData.delivery.city}</p>
          </div>
        </div>

        <!-- Articles commandés -->
        <div style="margin-bottom: 2rem;">
          <h3 style="margin: 0 0 1rem 0; color: #333; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-shopping-bag" style="color: #667eea;"></i> Articles commandés
          </h3>
          <div style="border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;">
            ${orderData.items.map((item, index) => `
              <div style="display: flex; justify-content: space-between; padding: 1rem; ${index > 0 ? 'border-top: 1px solid #e0e0e0;' : ''} background: ${index % 2 === 0 ? '#fafafa' : 'white'};">
                <div style="flex: 1;">
                  <strong style="display: block; color: #333; margin-bottom: 0.3rem;">${item.title}</strong>
                  <small style="color: #666;">Quantité: ${item.quantity} | Couleur: ${getColorName(item.color)}</small>
                </div>
                <div style="text-align: right; font-weight: 600; color: #667eea;">
                  ${formatPrice(item.price * item.quantity)} FCFA
                </div>
              </div>
            `).join('')}
            
            <!-- Total -->
            <div style="background: #667eea; color: white; padding: 1rem; display: flex; justify-content: space-between; align-items: center;">
              <strong style="font-size: 1.1rem;">Total à payer</strong>
              <strong style="font-size: 1.3rem;">${formatPrice(orderData.summary.total)} FCFA</strong>
            </div>
          </div>
        </div>

        <!-- Mode de paiement -->
        <div style="background: #fff9e6; border: 1px solid #ffd700; border-radius: 8px; padding: 1rem; margin-bottom: 2rem;">
          <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
            <i class="fas fa-info-circle" style="color: #ff9800;"></i>
            <strong style="color: #333;">Mode de paiement sélectionné</strong>
          </div>
          <p style="margin: 0; color: #666;">
            ${orderData.payment === 'mobile_money' ? '<i class="fas fa-mobile-alt"></i> Mobile Money (Orange/MTN)' : '<i class="fas fa-money-bill-wave"></i> Paiement à la livraison'}
          </p>
        </div>

        <!-- Message de confirmation -->
        <div style="text-align: center; padding: 1.5rem; background: #f0fdf4; border-radius: 8px; margin-bottom: 2rem;">
          <i class="fas fa-paper-plane" style="font-size: 2rem; color: #38ef7d; margin-bottom: 0.5rem;"></i>
          <p style="margin: 0; color: #333; line-height: 1.6;">
            Un email de confirmation a été envoyé à <strong>${orderData.customer.email}</strong>
            <br>
            <small style="color: #666;">Vous serez contacté sous 24h pour confirmer la livraison</small>
          </p>
        </div>

        <!-- Boutons d'action -->
        <div style="display: flex; gap: 1rem;">
          <button id="download-receipt" style="flex: 1; padding: 1rem; background: white; border: 2px solid #667eea; color: #667eea; border-radius: 8px; font-size: 1rem; cursor: pointer; font-weight: 600;">
            <i class="fas fa-download"></i> Télécharger le reçu
          </button>
          <button id="close-confirmation" style="flex: 1; padding: 1rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 1rem; cursor: pointer; font-weight: 600;">
            <i class="fas fa-home"></i> Retour à l'accueil
          </button>
        </div>
      </div>
    </div>
  `;

    // Animation de bounceIn
    const bounceStyle = document.createElement('style');
    bounceStyle.textContent = `
    @keyframes bounceIn {
      0% { transform: scale(0); opacity: 0; }
      50% { transform: scale(1.1); }
      100% { transform: scale(1); opacity: 1; }
    }
  `;
    document.head.appendChild(bounceStyle);

    // Événements des boutons
    document.getElementById('close-confirmation').addEventListener('click', () => {
        formModal.remove();
        window.location.href = '../Accueil/accueil.html';
    });

    document.getElementById('download-receipt').addEventListener('click', () => {
        downloadReceipt(orderData);
    });
}

// Sauvegarder la commande dans localStorage
function saveOrder(orderData) {
    let orders = localStorage.getItem('techstorm_orders');
    orders = orders ? JSON.parse(orders) : [];
    orders.push(orderData);
    localStorage.setItem('techstorm_orders', JSON.stringify(orders));
}

// Vider les articles commandés du panier
function clearOrderedItems(orderedItems) {
    const cart = getCart();
    const orderedIds = orderedItems.map(item => item.id);
    const newCart = cart.filter(item => !orderedIds.includes(item.id));
    saveCart(newCart);
    renderCartItems();
}

// Télécharger le reçu (génération simple)
function downloadReceipt(orderData) {
    const receiptContent = `
═══════════════════════════════════════
        REÇU DE COMMANDE - TECHSTORM
═══════════════════════════════════════

Numéro de commande: ${orderData.orderNumber}
Date: ${new Date(orderData.orderDate).toLocaleString('fr-FR')}

───────────────────────────────────────
CLIENT
───────────────────────────────────────
Nom: ${orderData.customer.firstName} ${orderData.customer.lastName}
Email: ${orderData.customer.email}
Téléphone: ${orderData.customer.phone}

───────────────────────────────────────
LIVRAISON
───────────────────────────────────────
Adresse: ${orderData.delivery.address}
Ville: ${orderData.delivery.city}
Quartier: ${orderData.delivery.quarter || 'N/A'}

───────────────────────────────────────
ARTICLES
───────────────────────────────────────
${orderData.items.map(item =>
        `${item.title}\n  Qté: ${item.quantity} | Couleur: ${getColorName(item.color)}\n  Prix: ${formatPrice(item.price * item.quantity)} FCFA`
    ).join('\n\n')}

───────────────────────────────────────
RÉCAPITULATIF
───────────────────────────────────────
Sous-total:        ${formatPrice(orderData.summary.subtotal)} FCFA
Livraison:         ${formatPrice(orderData.summary.delivery)} FCFA
───────────────────────────────────────
TOTAL:             ${formatPrice(orderData.summary.total)} FCFA
═══════════════════════════════════════

Mode de paiement: ${orderData.payment === 'mobile_money' ? 'Mobile Money' : 'Paiement à la livraison'}

${orderData.delivery.notes ? `Instructions: ${orderData.delivery.notes}` : ''}

───────────────────────────────────────
Merci pour votre confiance !
TechStorm - Le futur entre vos mains
───────────────────────────────────────
  `;

    // Créer un blob et télécharger
    const blob = new Blob([receiptContent], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `Recu_${orderData.orderNumber}.txt`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);

    alert('Reçu téléchargé avec succès !');
}

// Bouton "Procéder au paiement"
function initCheckout() {
    const checkoutBtn = document.querySelector('.checkout-btn');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', showOrderForm);
    }
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    updateCartCount();
    renderCartItems();
    initCheckout();
});
