<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\SubscribeController;
use Illuminate\Support\Facades\Route;

Route::get('/data', [ApiController::class, 'data']);
Route::post('/subscribe', [SubscribeController::class, 'store'])->middleware('throttle:5,60');
