<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = [
            // ==================== SMARTPHONES ====================
            // iPhone
            [ 'id' => 1, 'title' => "iPhone 15 Pro Max", 'category' => "Smartphones", 'brand' => "Apple", 'price' => 850000, 'stock' => 5, 'img' => "images/Telephones/Iphone/phone_iphone_1.jpeg", 'featured' => true, 'new' => true, 'desc' => "Le summum de la technologie Apple avec puce A17 Pro." ],
            [ 'id' => 2, 'title' => "iPhone 15 Pro", 'category' => "Smartphones", 'brand' => "Apple", 'price' => 750000, 'stock' => 8, 'img' => "images/Telephones/Iphone/phone_iphone_2.jpeg", 'featured' => true, 'new' => true, 'desc' => "Performance exceptionnelle et caméra professionnelle." ],
            [ 'id' => 3, 'title' => "iPhone 15", 'category' => "Smartphones", 'brand' => "Apple", 'price' => 650000, 'stock' => 10, 'img' => "images/Telephones/Iphone/phone_iphone_3.jpeg", 'featured' => false, 'new' => true, 'desc' => "iPhone nouvelle génération, design élégant." ],
            [ 'id' => 4, 'title' => "iPhone 14 Pro", 'category' => "Smartphones", 'brand' => "Apple", 'price' => 600000, 'stock' => 6, 'img' => "images/Telephones/Iphone/phone_iphone_4.jpeg", 'featured' => false, 'new' => false, 'desc' => "Dynamic Island et caméra 48MP." ],
            [ 'id' => 5, 'title' => "iPhone 14", 'category' => "Smartphones", 'brand' => "Apple", 'price' => 500000, 'stock' => 12, 'img' => "images/Telephones/Iphone/phone_iphone_5.jpeg", 'featured' => false, 'new' => false, 'desc' => "iPhone fiable avec excellente autonomie." ],
            [ 'id' => 6, 'title' => "iPhone 13", 'category' => "Smartphones", 'brand' => "Apple", 'price' => 400000, 'stock' => 15, 'img' => "images/Telephones/Iphone/phone_iphone_6.jpeg", 'featured' => false, 'new' => false, 'desc' => "Rapport qualité-prix imbattable." ],
            [ 'id' => 7, 'title' => "iPhone SE 2024", 'category' => "Smartphones", 'brand' => "Apple", 'price' => 320000, 'stock' => 20, 'img' => "images/Telephones/Iphone/phone_iphone_7.jpeg", 'featured' => false, 'new' => false, 'desc' => "Compact et puissant, parfait pour débutants." ],
            [ 'id' => 8, 'title' => "iPhone 12 Pro", 'category' => "Smartphones", 'brand' => "Apple", 'price' => 380000, 'stock' => 8, 'img' => "images/Telephones/Iphone/phone_iphone_8.jpeg", 'featured' => false, 'new' => false, 'desc' => "Toujours performant with 5G." ],

            // Samsung
            [ 'id' => 9, 'title' => "Galaxy S25 Ultra", 'category' => "Smartphones", 'brand' => "Samsung", 'price' => 720000, 'stock' => 6, 'img' => "images/Telephones/Samsung/phone_samsung_1.jpeg", 'featured' => true, 'new' => true, 'desc' => "Écran 6.8\" AMOLED, S Pen intégré." ],
            [ 'id' => 10, 'title' => "Galaxy S25+", 'category' => "Smartphones", 'brand' => "Samsung", 'price' => 620000, 'stock' => 9, 'img' => "images/Telephones/Samsung/phone_samsung_2.jpeg", 'featured' => true, 'new' => true, 'desc' => "Grand écran et batterie longue durée." ],
            [ 'id' => 11, 'title' => "Galaxy S24", 'category' => "Smartphones", 'brand' => "Samsung", 'price' => 520000, 'stock' => 12, 'img' => "images/Telephones/Samsung/phone_samsung_3.jpeg", 'featured' => false, 'new' => false, 'desc' => "Smartphone polyvalent avec IA embarquée." ],
            [ 'id' => 12, 'title' => "Galaxy Z Fold 5", 'category' => "Smartphones", 'brand' => "Samsung", 'price' => 950000, 'stock' => 3, 'img' => "images/Telephones/Samsung/phone_samsung_4.jpeg", 'featured' => true, 'new' => true, 'desc' => "Smartphone pliable révolutionnaire." ],
            [ 'id' => 13, 'title' => "Galaxy A54", 'category' => "Smartphones", 'brand' => "Samsung", 'price' => 280000, 'stock' => 18, 'img' => "images/Telephones/Samsung/phone_samsung_5.jpeg", 'featured' => false, 'new' => false, 'desc' => "Milieu de gamme avec excellentes photos." ],

            // ==================== LAPTOPS ====================
            // MacBook
            [ 'id' => 14, 'title' => "MacBook Pro 16\" M3 Max", 'category' => "Laptops", 'brand' => "Apple", 'price' => 2800000, 'stock' => 2, 'img' => "images/PC/Mac/pc_mac_1.jpeg", 'featured' => true, 'new' => true, 'desc' => "Puissance ultime pour pros de la création." ],
            [ 'id' => 15, 'title' => "MacBook Pro 14\" M3 Pro", 'category' => "Laptops", 'brand' => "Apple", 'price' => 2200000, 'stock' => 4, 'img' => "images/PC/Mac/pc_mac_2.jpeg", 'featured' => true, 'new' => true, 'desc' => "Compact et ultra-performant." ],
            [ 'id' => 16, 'title' => "MacBook Air 15\" M3", 'category' => "Laptops", 'brand' => "Apple", 'price' => 1600000, 'stock' => 6, 'img' => "images/PC/Mac/pc_mac_3.jpeg", 'featured' => true, 'new' => true, 'desc' => "Grand écran, design fin et léger." ],
            [ 'id' => 17, 'title' => "MacBook Air 13\" M2", 'category' => "Laptops", 'brand' => "Apple", 'price' => 1200000, 'stock' => 8, 'img' => "images/PC/Mac/pc_mac_4.jpeg", 'featured' => false, 'new' => false, 'desc' => "Idéal pour étudiants et nomades." ],
            [ 'id' => 18, 'title' => "MacBook Pro 13\" M2", 'category' => "Laptops", 'brand' => "Apple", 'price' => 1400000, 'stock' => 5, 'img' => "images/PC/Mac/pc_mac_5.jpeg", 'featured' => false, 'new' => false, 'desc' => "Performance et portabilité." ],

            // ASUS
            [ 'id' => 19, 'title' => "ASUS ROG Strix G16", 'category' => "Laptops", 'brand' => "ASUS", 'price' => 1500000, 'stock' => 4, 'img' => "images/PC/Asus/pc_asus_1.jpeg", 'featured' => true, 'new' => true, 'desc' => "PC gamer RTX 4070, écran 165Hz." ],
            [ 'id' => 20, 'title' => "ASUS TUF Gaming A15", 'category' => "Laptops", 'brand' => "ASUS", 'price' => 950000, 'stock' => 7, 'img' => "images/PC/Asus/pc_asus_2.jpeg", 'featured' => false, 'new' => false, 'desc' => "Gaming robuste et abordable." ],
            [ 'id' => 21, 'title' => "ASUS Zenbook 14", 'category' => "Laptops", 'brand' => "ASUS", 'price' => 850000, 'stock' => 6, 'img' => "images/PC/Asus/pc_asus_3.jpeg", 'featured' => false, 'new' => false, 'desc' => "Ultra-portable premium pour professionnels." ],
            [ 'id' => 22, 'title' => "ASUS VivoBook 15", 'category' => "Laptops", 'brand' => "ASUS", 'price' => 480000, 'stock' => 12, 'img' => "images/PC/Asus/pc_asus_4.jpeg", 'featured' => false, 'new' => false, 'desc' => "PC polyvalent pour usage quotidien." ],
            [ 'id' => 23, 'title' => "ASUS ProArt StudioBook", 'category' => "Laptops", 'brand' => "ASUS", 'price' => 1800000, 'stock' => 3, 'img' => "images/PC/Asus/pc_asus_5.jpeg", 'featured' => true, 'new' => true, 'desc' => "Station de travail pour créateurs 3D." ],

            // Dell
            [ 'id' => 24, 'title' => "Dell XPS 15", 'category' => "Laptops", 'brand' => "Dell", 'price' => 1600000, 'stock' => 5, 'img' => "images/PC/Dell/pc_dell_1.jpeg", 'featured' => true, 'new' => true, 'desc' => "Écran 4K OLED, design premium." ],
            [ 'id' => 25, 'title' => "Dell Inspiron 16", 'category' => "Laptops", 'brand' => "Dell", 'price' => 720000, 'stock' => 8, 'img' => "images/PC/Dell/pc_dell_2.jpeg", 'featured' => false, 'new' => false, 'desc' => "Grand écran confortable pour le multimédia." ],
            [ 'id' => 26, 'title' => "Dell Latitude 14", 'category' => "Laptops", 'brand' => "Dell", 'price' => 980000, 'stock' => 6, 'img' => "images/PC/Dell/pc_dell_3.jpeg", 'featured' => false, 'new' => false, 'desc' => "PC professionnel sécurisé et durable." ],

            // HP
            [ 'id' => 27, 'title' => "HP Spectre x360 14", 'category' => "Laptops", 'brand' => "HP", 'price' => 1350000, 'stock' => 4, 'img' => "images/PC/HP/pc_hp_1.jpeg", 'featured' => true, 'new' => true, 'desc' => "Convertible 2-en-1 haut de gamme." ],
            [ 'id' => 28, 'title' => "HP Envy 15", 'category' => "Laptops", 'brand' => "HP", 'price' => 920000, 'stock' => 7, 'img' => "images/PC/HP/pc_hp_2.jpeg", 'featured' => false, 'new' => false, 'desc' => "Design élégant, performance créative." ],
            [ 'id' => 29, 'title' => "HP Pavilion Gaming", 'category' => "Laptops", 'brand' => "HP", 'price' => 680000, 'stock' => 9, 'img' => "images/PC/HP/pc_hp_3.jpeg", 'featured' => false, 'new' => false, 'desc' => "Gaming accessible avec GTX 1650." ],
            [ 'id' => 30, 'title' => "HP ProBook 450", 'category' => "Laptops", 'brand' => "HP", 'price' => 550000, 'stock' => 10, 'img' => "images/PC/HP/pc_hp_4.jpeg", 'featured' => false, 'new' => false, 'desc' => "PC business fiable et évolutif." ],

            // ==================== CASQUES & ÉCOUTEURS ====================
            [ 'id' => 31, 'title' => "Casque 300 BT Pro", 'category' => "Écouteurs", 'brand' => "TechStorm", 'price' => 65000, 'stock' => 8, 'img' => "images/Casque/casque 300 bt_1.png", 'featured' => true, 'new' => true, 'desc' => "Son Hi-Fi, réduction de bruit active." ],
            [ 'id' => 32, 'title' => "Casque 300 BT Sport", 'category' => "Écouteurs", 'brand' => "TechStorm", 'price' => 48000, 'stock' => 12, 'img' => "images/Casque/casque 300 BT_2.png", 'featured' => false, 'new' => false, 'desc' => "Résistant à la sueur, autonomie 30h." ],
            [ 'id' => 33, 'title' => "Casque 300 BT Studio", 'category' => "Écouteurs", 'brand' => "TechStorm", 'price' => 72000, 'stock' => 6, 'img' => "images/Casque/casque 300 BT_3.png", 'featured' => true, 'new' => true, 'desc' => "Qualité studio pour producteurs." ],
            [ 'id' => 34, 'title' => "Casque 300 BT Kids", 'category' => "Écouteurs", 'brand' => "TechStorm", 'price' => 35000, 'stock' => 15, 'img' => "images/Casque/casque 300 BT_4.png", 'featured' => false, 'new' => false, 'desc' => "Limité à 85dB, parfait pour enfants." ],
            [ 'id' => 35, 'title' => "Casque 300 BT Gaming", 'category' => "Écouteurs", 'brand' => "TechStorm", 'price' => 58000, 'stock' => 10, 'img' => "images/Casque/casque 300 BT_5.png", 'featured' => true, 'new' => false, 'desc' => "Son surround 7.1, micro détachable." ],
            [ 'id' => 36, 'title' => "Casque 300 BT Lite", 'category' => "Écouteurs", 'brand' => "TechStorm", 'price' => 38000, 'stock' => 18, 'img' => "images/Casque/casque 300 BT_6.png", 'featured' => false, 'new' => false, 'desc' => "Version allégée, confort maximal." ],
            [ 'id' => 37, 'title' => "Casque 300 BT Travel", 'category' => "Écouteurs", 'brand' => "TechStorm", 'price' => 62000, 'stock' => 7, 'img' => "images/Casque/casque 300 BT_7.png", 'featured' => false, 'new' => false, 'desc' => "Pliable avec étui de transport." ],
            [ 'id' => 38, 'title' => "Casque 300 BT Deluxe", 'category' => "Écouteurs", 'brand' => "TechStorm", 'price' => 85000, 'stock' => 5, 'img' => "images/Casque/casque 300 BT_8.png", 'featured' => true, 'new' => true, 'desc' => "Version premium, cuir et métal." ],
            [ 'id' => 39, 'title' => "Casque 300 BT Office", 'category' => "Écouteurs", 'brand' => "TechStorm", 'price' => 52000, 'stock' => 9, 'img' => "images/Casque/casque 300 BT_10.png", 'featured' => false, 'new' => false, 'desc' => "Micro antibruit pour télétravail." ],
            [ 'id' => 40, 'title' => "Casque 300 BT Bass", 'category' => "Écouteurs", 'brand' => "TechStorm", 'price' => 55000, 'stock' => 11, 'img' => "images/Casque/casque 300 BT_11.png", 'featured' => false, 'new' => false, 'desc' => "Basses profondes pour amateurs EDM." ],

            // ==================== TABLETTES ====================
            [ 'id' => 41, 'title' => "iPad Pro 12.9\" M2", 'category' => "Tablettes", 'brand' => "Apple", 'price' => 980000, 'stock' => 4, 'img' => "images/Tablette/tablette_1.jpeg", 'featured' => true, 'new' => true, 'desc' => "Écran Liquid Retina XDR, puce M2." ],
            [ 'id' => 42, 'title' => "Galaxy Tab S9 Ultra", 'category' => "Tablettes", 'brand' => "Samsung", 'price' => 850000, 'stock' => 5, 'img' => "images/Tablette/tablette_2.jpeg", 'featured' => true, 'new' => true, 'desc' => "Écran AMOLED 14.6\", S Pen inclus." ],
            [ 'id' => 43, 'title' => "iPad Air 10.9\"", 'category' => "Tablettes", 'brand' => "Apple", 'price' => 520000, 'stock' => 8, 'img' => "images/Tablette/tablette_3.jpeg", 'featured' => false, 'new' => false, 'desc' => "Polyvalente avec puce M1." ],

            // ==================== APPAREILS PHOTO ====================
            [ 'id' => 44, 'title' => "Canon EOS R6 Mark II", 'category' => "Appareils Photo", 'brand' => "Canon", 'price' => 1850000, 'stock' => 3, 'img' => "images/Appareil Photo/app photo_1.png", 'featured' => true, 'new' => true, 'desc' => "Hybride plein format, vidéo 6K." ],
            [ 'id' => 45, 'title' => "Sony A7 IV", 'category' => "Appareils Photo", 'brand' => "Sony", 'price' => 1950000, 'stock' => 2, 'img' => "images/Appareil Photo/app photo_2.png", 'featured' => true, 'new' => true, 'desc' => "33MP, autofocus IA révolutionnaire." ],
            [ 'id' => 46, 'title' => "Nikon Z6 III", 'category' => "Appareils Photo", 'brand' => "Nikon", 'price' => 1650000, 'stock' => 4, 'img' => "images/Appareil Photo/app photo_3.png", 'featured' => true, 'new' => true, 'desc' => "Stabilisation 5 axes, RAW 14 bits." ],
            [ 'id' => 47, 'title' => "Fujifilm X-T5", 'category' => "Appareils Photo", 'brand' => "Fujifilm", 'price' => 1280000, 'stock' => 5, 'img' => "images/Appareil Photo/app photo_4.jpeg", 'featured' => false, 'new' => false, 'desc' => "APS-C 40MP, simulations film légendaires." ],
            [ 'id' => 48, 'title' => "Canon EOS R10", 'category' => "Appareils Photo", 'brand' => "Canon", 'price' => 780000, 'stock' => 6, 'img' => "images/Appareil Photo/app photo_5.jpeg", 'featured' => false, 'new' => false, 'desc' => "Hybride compact pour débutants." ],
            [ 'id' => 49, 'title' => "Sony ZV-E10", 'category' => "Appareils Photo", 'brand' => "Sony", 'price' => 620000, 'stock' => 8, 'img' => "images/Appareil Photo/app photo_6.jpeg", 'featured' => false, 'new' => false, 'desc' => "Parfait pour vlogging et contenu créateur." ],
            [ 'id' => 50, 'title' => "Nikon Z30", 'category' => "Appareils Photo", 'brand' => "Nikon", 'price' => 580000, 'stock' => 7, 'img' => "images/Appareil Photo/app photo_7.jpeg", 'featured' => false, 'new' => false, 'desc' => "Vidéo 4K, écran orientable." ],
            [ 'id' => 51, 'title' => "Panasonic Lumix S5", 'category' => "Appareils Photo", 'brand' => "Panasonic", 'price' => 1450000, 'stock' => 3, 'img' => "images/Appareil Photo/app photo_8.jpeg", 'featured' => true, 'new' => false, 'desc' => "Plein format vidéo-centrique." ]
        ];

        return response()->json(['products' => $products]);
    }
}
