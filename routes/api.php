<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\FileUploadController;

Route::get('read-file', [FileUploadController::class, 'readFile']);
Route::post('/upload-file', [FileUploadController::class, 'uploadFile'])->middleware('auth:api');
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('/set-last-online', [AuthController::class, 'setLastOnline']);

// Route::post('send-otp', [AuthController::class, 'sendOtp']);
// Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
Route::get('get-profile', [AuthController::class, 'getProfile'])->middleware('auth:api');
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
Route::get('get-people', [ChatController::class, 'getPeople'])->middleware('auth:api');
Route::get('get-message', [ChatController::class, 'getMessage'])->middleware('auth:api');
Route::post('save-message', [ChatController::class, 'saveMessage'])->middleware('auth:api');
Route::post('save-message-with-file', [ChatController::class, 'saveMessageWithFile'])->middleware('auth:api');
Route::post('seen-message', [ChatController::class, 'seenMessage'])->middleware('auth:api');
Route::get('get-other-user', [ChatController::class, 'getOtherUsers'])->middleware('auth:api');
Route::post('send-request-friend', [ChatController::class, 'sendRequestFriend'])->middleware('auth:api');
Route::put('change-request-friend/{id}', [ChatController::class, 'changeRequestFriend'])->middleware('auth:api');
Route::get('get-request-friend', [ChatController::class, 'getRequestFriend'])->middleware('auth:api');
Route::get('get-list-conversation', [ChatController::class, 'listConversation'])->middleware('auth:api');
Route::get('get-detail-conversation', [ChatController::class, 'detailConversation'])->middleware('auth:api');
Route::post('add-reaction', [ChatController::class, 'addReaction'])->middleware('auth:api');
Route::post('init', [ChatController::class, 'init'])->middleware('auth:api');
Route::post('add-key', [ChatController::class, 'addKey'])->middleware('auth:api');
