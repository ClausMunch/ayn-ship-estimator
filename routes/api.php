<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\SubscribeController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

Route::get('/data', [ApiController::class, 'data']);
Route::post('/subscribe', [SubscribeController::class, 'store'])->middleware('throttle:5,60');

Route::post('/track/view', [TrackingController::class, 'view'])->middleware('throttle:30,1');
Route::post('/track/estimation', [TrackingController::class, 'estimation'])->middleware('throttle:60,1');
