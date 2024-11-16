<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\FileUploadController;

Route::post('/upload-image', [FileUploadController::class, 'uploadImage'])->name('upload.image');
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('/set-last-online', [AuthController::class, 'setLastOnline']);

// Route::post('send-otp', [AuthController::class, 'sendOtp']);
// Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
Route::get('get-profile', [AuthController::class, 'getProfile'])->middleware('auth:api');
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
Route::get('get-people', [ChatController::class, 'getPeople'])->middleware('auth:api');
Route::get('get-message', [ChatController::class, 'getMessage'])->middleware('auth:api');
Route::get('get-detail-conversation', [ChatController::class, 'detailConversation'])->middleware('auth:api');
