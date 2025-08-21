<?php

use Illuminate\Http\Request;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::middleware('inbound.key')->group(function () {
    Route::post('/chat/incoming', [ChatController::class, 'incoming']);
});