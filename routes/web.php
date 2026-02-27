<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\SubscribeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);
Route::get('/verify/{token}', [SubscribeController::class, 'verify']);
Route::get('/unsubscribe/{token}', [SubscribeController::class, 'unsubscribe']);
