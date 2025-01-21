<?php

use App\Http\Controllers\Documentation\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return abort(503);
});

Route::get('logout', [Auth::class, 'logout'])->name('logout');
Route::get('documentation', [Auth::class, 'index'])->name('login');
Route::post('documentation/login', [Auth::class, 'login'])->name('documentation.process.login');