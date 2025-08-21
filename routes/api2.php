<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

Route::middleware('inbound.key')->group(function () {
    Route::post('/chat/incoming', [ChatController::class, 'incoming']);
});