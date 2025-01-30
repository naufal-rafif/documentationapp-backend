<?php

use App\Http\Controllers\API\V1\Authentication\Basic;
use App\Http\Controllers\API\V1\User\Permission;
use App\Http\Controllers\API\V1\User\Profile;
use App\Http\Controllers\API\V1\User\Role;
use App\Http\Controllers\API\V1\User\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('basic/login', [Basic::class, 'login']); //create
        Route::post('basic/register', [Basic::class, 'register'])->middleware('guest'); //create
    });
    Route::prefix('user')->middleware('auth:api')->group(function () {
        Route::prefix('profile')->group(function () {
            Route::get('', [Profile::class, 'index']); //list
            Route::put('', [Profile::class, 'update']); //update
        });
        Route::prefix('user')->group(function () {
            Route::get('', [User::class, 'index'])->middleware('permission:access-users,api'); //list
            Route::post('', [User::class, 'store'])->middleware('permission:create-users,api'); //create
            Route::get('/{uuid}', [User::class, 'show'])->middleware('permission:access-users,api'); //show
            Route::put('/{uuid}', [User::class, 'update'])->middleware('permission:edit-users,api'); //update
            Route::post('/{uuid}', [User::class, 'updateStatus'])->middleware('permission:delete-users,api'); //update
            Route::delete('/{uuid}', [User::class, 'destroy'])->middleware('permission:delete-users,api'); //delete
        });
        Route::prefix('role')->group(function () {
            Route::get('', [Role::class, 'index'])->middleware('permission:access-roles,api'); //list
            Route::post('', [Role::class, 'store'])->middleware('permission:create-roles,api'); //create
            Route::get('/{uuid}', [Role::class, 'show'])->middleware('permission:access-roles,api');//show
            Route::put('/{uuid}', [Role::class, 'update'])->middleware('permission:edit-roles,api'); //update
            Route::delete('/{uuid}', [Role::class, 'destroy'])->middleware('permission:delete-roles,api'); //delete
        });
        Route::prefix('permission')->group(function () {
            Route::get('', [Permission::class, 'index'])->middleware('permission:access-permissions,api'); //list
            Route::post('', [Permission::class, 'store'])->middleware('permission:create-permissions,api'); //create
            Route::get('/{name}', [Permission::class, 'show'])->middleware('permission:access-permissions,api'); //show
            Route::put('/{name}', [Permission::class, 'update'])->middleware('permission:edit-permissions,api'); //update
            Route::delete('/{name}', [Permission::class, 'destroy'])->middleware('permission:delete-permissions,api'); //delete
        });
    });
});