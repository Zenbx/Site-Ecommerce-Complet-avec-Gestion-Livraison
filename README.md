# Site-Ecommerce-Complet-avec-Gestion-Livraison

API REST complète pour une plateforme e-commerce avec système de livraison et scan QR Code.

## 📋 Prérequis

- PHP 8.1+
- Composer
- PostgreSQL / MySQL
- Laravel 10+

## 🚀 Installation

### 1. Configuration de la base de données

```bash
# Copier le fichier .env
cp .env.example .env

# Configurer votre base de données dans .env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ecommerce_db
DB_USERNAME=postgres
DB_PASSWORD=votre_password
```

### 2. Installer les dépendances

```bash
composer install
php artisan key:generate
```

### 3. Exécuter les migrations (utilisez votre init.sql)

```bash
# Option 1: Via psql
psql -U postgres -d ecommerce_db -f init.sql

# Option 2: Via Laravel (si vous créez des migrations)
php artisan migrate
```

### 4. Remplir la base avec des données de test

```bash
php artisan db:seed
```

### 5. Créer le lien symbolique pour les images

```bash
php artisan storage:link
```

### 6. Lancer le serveur

```bash
php artisan serve
# API disponible sur http://localhost:8000
```

## 🔐 Comptes de Test

Après le seeding, utilisez ces identifiants :

| Type | Email | Mot de passe |
|------|-------|--------------|
| **Admin** | admin@ecommerce.cm | admin123 |
| **Client** | john@example.com | password123 |
| **Livreur** | pierre@delivery.cm | delivery123 |

## 📡 Test des Endpoints

### 1. Authentification Client

```bash
# Inscription
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "address": "123 Test Street, Douala"
  }'

# Connexion
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123",
    "user_type": "client"
  }'
```

**Sauvegardez le token retourné pour les prochaines requêtes !**

### 2. Catalogue Produits (Public)

```bash
# Liste des produits
curl http://localhost:8000/api/products

# Détails d'un produit
curl http://localhost:8000/api/products/1
```

### 3. Panier (Authentifié)

```bash
# Remplacez YOUR_TOKEN par le token obtenu lors de la connexion
TOKEN="YOUR_TOKEN"

# Voir le panier
curl http://localhost:8000/api/client/cart \
  -H "Authorization: Bearer $TOKEN"

# Ajouter au panier
curl -X POST http://localhost:8000/api/client/cart/items \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 1,
    "quantity": 2
  }'

# Modifier la quantité
curl -X PATCH http://localhost:8000/api/client/cart/items/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "quantity": 3
  }'
```

### 4. Créer une Commande

```bash
curl -X POST http://localhost:8000/api/client/orders \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "delivery_address": "123 Main St, Douala, Cameroun",
    "payment_method": "MOMO",
    "notes": "Livrer entre 14h et 18h"
  }'
```

### 5. Voir mes Commandes

```bash
# Liste
curl http://localhost:8000/api/client/orders \
  -H "Authorization: Bearer $TOKEN"

# Détails d'une commande
curl http://localhost:8000/api/client/orders/1 \
  -H "Authorization: Bearer $TOKEN"
```

### 6. Admin - Gérer les Produits

```bash
# Connexion admin
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@ecommerce.cm",
    "password": "admin123",
    "user_type": "admin"
  }'

ADMIN_TOKEN="YOUR_ADMIN_TOKEN"

# Créer un produit
curl -X POST http://localhost:8000/api/admin/products \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Nouveau Produit",
    "quantity": 10,
    "price": 50000,
    "serial_id": "PROD-NEW-001",
    "description": "Description du produit",
    "brand": "Brand X",
    "category": "Catégorie",
    "is_active": true
  }'

# Mettre à jour le stock
curl -X PATCH http://localhost:8000/api/admin/products/1/stock \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "quantity": 50
  }'
```

### 7. Admin - Assigner une Livraison

```bash
# Liste des commandes
curl http://localhost:8000/api/admin/orders \
  -H "Authorization: Bearer $ADMIN_TOKEN"

# Assigner à un livreur
curl -X POST http://localhost:8000/api/admin/orders/1/assign-delivery \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "delivery_person_id": 1,
    "notes": "Livraison prioritaire"
  }'
```

### 8. Livreur - Gérer les Livraisons

```bash
# Connexion livreur
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "pierre@delivery.cm",
    "password": "delivery123",
    "user_type": "delivery_person"
  }'

DELIVERY_TOKEN="YOUR_DELIVERY_TOKEN"

# Voir mes livraisons
curl http://localhost:8000/api/delivery-person/deliveries \
  -H "Authorization: Bearer $DELIVERY_TOKEN"

# Marquer comme récupéré
curl -X POST http://localhost:8000/api/delivery-person/deliveries/1/pickup \
  -H "Authorization: Bearer $DELIVERY_TOKEN"

# Démarrer la livraison
curl -X POST http://localhost:8000/api/delivery-person/deliveries/1/start \
  -H "Authorization: Bearer $DELIVERY_TOKEN"

# Scanner le QR Code (utilisez le qr_token de la commande)
curl -X POST http://localhost:8000/api/delivery-person/deliveries/1/scan-qr \
  -H "Authorization: Bearer $DELIVERY_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "qr_token": "le_token_qr_de_la_commande"
  }'

# Compléter la livraison
curl -X POST http://localhost:8000/api/delivery-person/deliveries/1/complete \
  -H "Authorization: Bearer $DELIVERY_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "notes": "Livraison effectuée avec succès"
  }'
```

## 🔄 Workflow Complet

### Scénario : Client passe une commande → Livraison

1. **Client s'inscrit/connecte**
2. **Client ajoute des produits au panier**
3. **Client crée une commande** (le panier est vidé, le stock est déduit)
4. **Admin assigne la commande à un livreur**
5. **Livreur récupère le colis** (status: PICKED_UP)
6. **Livreur démarre la livraison** (status: IN_TRANSIT)
7. **Livreur scanne le QR Code** chez le client
8. **Livreur soumet une preuve** (photo/signature)
9. **Livreur complète la livraison** (status: DELIVERED)

## 📊 Structure de la BD

Tables principales :
- `client` - Clients
- `admin` - Administrateurs
- `delivery_person` - Livreurs
- `product` - Produits
- `cart` / `cart_line` - Panier
- `order` / `order_line` - Commandes
- `payment` - Paiements
- `delivery` - Livraisons avec QR Code

## 🛠️ Dépannage

### Erreur "Column not found"
```bash
# Vérifiez que init.sql a bien été exécuté
psql -U postgres -d ecommerce_db -c "\dt"
```

### Erreur "Unauthenticated"
```bash
# Vérifiez que le token est valide et bien envoyé
# Format: Authorization: Bearer YOUR_TOKEN
```

### Images non accessibles
```bash
# Créer le lien symbolique
php artisan storage:link
```

## 📝 Prochaines Étapes

- ✅ Ajouter la gestion des catégories
- ✅ Implémenter les filtres de recherche
- ✅ Ajouter la pagination
- ⏳ WebSockets pour le tracking en temps réel
- ⏳ Notifications push
- ⏳ Système de notation des livreurs

## 📞 Support

Pour toute question, contactez l'équipe de développement.