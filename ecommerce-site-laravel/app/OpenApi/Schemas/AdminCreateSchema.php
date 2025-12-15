<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="AdminCreate",
 *     type="object",
 *     required={"name", "email", "password", "role"},
 *
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         example="Nouvel Admin"
 *     ),
 *
 *     @OA\Property(
 *         property="email",
 *         type="string",
 *         format="email",
 *         example="newadmin@ecommerce.cm"
 *     ),
 *
 *     @OA\Property(
 *         property="password",
 *         type="string",
 *         format="password",
 *         example="Admin@123"
 *     ),
 *
 *     @OA\Property(
 *         property="role",
 *         type="string",
 *         enum={"ADMIN", "GESTIONNAIRE", "SUPERVISEUR"},
 *         example="GESTIONNAIRE"
 *     )
 * )
 */
class AdminCreateSchema {}
