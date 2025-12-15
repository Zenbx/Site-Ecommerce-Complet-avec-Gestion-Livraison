<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="AdminLogin",
 *     type="object",
 *     required={"email", "password"},
 *
 *     @OA\Property(
 *         property="email",
 *         type="string",
 *         format="email",
 *         example="admin@ecommerce.cm"
 *     ),
 *
 *     @OA\Property(
 *         property="password",
 *         type="string",
 *         format="password",
 *         example="admin123"
 *     )
 * )
 */
class AdminLoginSchema {}
