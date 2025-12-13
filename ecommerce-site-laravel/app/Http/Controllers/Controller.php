<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title="E-Commerce Delivery API",
 *     version="1.0.0",
 *     description="API complète pour un système e-commerce avec livraison et scan QR Code",
 *     @OA\Contact(
 *         email="support@ecommerce.cm",
 *         name="Support E-Commerce"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Serveur de développement"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Entrez votre token JWT (obtenu lors de la connexion)"
 * )
 * 
 * @OA\Tag(
 *     name="Authentification",
 *     description="Endpoints pour l'inscription, connexion et gestion des utilisateurs"
 * )
 * 
 * @OA\Tag(
 *     name="Produits",
 *     description="Gestion du catalogue de produits"
 * )
 * 
 * @OA\Tag(
 *     name="Panier",
 *     description="Gestion du panier d'achat"
 * )
 * 
 * @OA\Tag(
 *     name="Commandes",
 *     description="Création et suivi des commandes"
 * )
 * 
 * @OA\Tag(
 *     name="Livraisons",
 *     description="Gestion des livraisons avec QR Code"
 * )
 * 
 * @OA\Tag(
 *     name="Admin - Produits",
 *     description="Administration des produits"
 * )
 * 
 * @OA\Tag(
 *     name="Admin - Commandes",
 *     description="Administration des commandes"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}