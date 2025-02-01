<?php

namespace App\Http\Controllers;
/**
 * @OA\Info(
 *     title="Laravel Swagger API documentation example",
 *     version="1.0.0",
 *     @OA\Contact(
 *         email="admin@example.com"
 *     ),
 *     @OA\License(
 *         name="Apache 2.0",
 *         url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *     )
 * )
 * @OA\Tag(
 *     name="Authentication - Basic",
 *     description="Endpoints related to authentication. Use these endpoints to authenticate users and obtain an access token."
 * ) 
 * 
 * @OA\Tag(
 *     name="User - Permission",
 *     description="Endpoints related to managing permissions. Use these endpoints to create, update, delete, or fetch permissions."
 * ) 
 * 
 * @OA\Tag(
 *     name="User - Profile",
 *     description="Endpoints related to managing profiles. Use these endpoints to update or fetch profiles."
 * ) 
 * 
 * @OA\Tag(
 *     name="User - Role",
 *     description="Endpoints related to managing roles. Use these endpoints to create, update, delete, or fetch roles."
 * ) 
 * 
 * @OA\Tag(
 *     name="User - User",
 *     description="Endpoints related to managing users. Use these endpoints to create, update, delete, or fetch users."
 * ) 
 * 
 * @OA\Tag(
 *     name="Data Master - Province",
 *     description="Endpoints related to managing provinces. Use these endpoints to create, update, delete, or fetch provinces."
 * ) 
 * 
 * @OA\Tag(
 *     name="Data Master - Regency",
 *     description="Endpoints related to managing regencies. Use these endpoints to create, update, delete, or fetch regencies."
 * ) 
 * 
 * @OA\Tag(
 *     name="Data Master - District",
 *     description="Endpoints related to managing districts. Use these endpoints to create, update, delete, or fetch districts."
 * ) 
 * 
 * @OA\Server(
 *     description="Local Development Server",
 *     url="https://test.naufalrafif.com/api/v1"
 * )
 * @OA\Server(
 *     description="Staging Server",
 *     url="https://staging.example.com/api"
 * )
 * @OA\Server(
 *     description="Production Server",
 *     url="https://api.example.com"
 * )
 */
abstract class Controller
{
    //
}
