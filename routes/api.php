<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\FileUploadController;
use App\Http\Middleware\CrossOriginIsolation;

Route::get('read-file', [FileUploadController::class, 'readFile']);
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('/set-last-online', [AuthController::class, 'setLastOnline']);

Route::middleware(['auth:api'])->group(function () {
  Route::post('/upload-file', [FileUploadController::class, 'uploadFile']);
  Route::get('get-profile', [AuthController::class, 'getProfile']);
  Route::post('logout', [AuthController::class, 'logout']);
  Route::get('get-people', [ChatController::class, 'getPeople']);
  Route::get('get-message', [ChatController::class, 'getMessage']);
  Route::post('save-message', [ChatController::class, 'saveMessage']);
  Route::post('save-message-with-file', [ChatController::class, 'saveMessageWithFile']);
  Route::post('seen-message', [ChatController::class, 'seenMessage']);
  Route::get('get-other-user', [ChatController::class, 'getOtherUsers']);
  Route::post('send-request-friend', [ChatController::class, 'sendRequestFriend']);
  Route::put('change-request-friend/{id}', [ChatController::class, 'changeRequestFriend']);
  Route::get('get-request-friend', [ChatController::class, 'getRequestFriend']);
  Route::get('get-list-conversation', [ChatController::class, 'listConversation']);
  Route::get('get-detail-conversation', [ChatController::class, 'detailConversation']);
  Route::post('add-reaction', [ChatController::class, 'addReaction']);
  Route::post('init', [ChatController::class, 'init']);
  Route::post('add-key', [ChatController::class, 'addKey']);
});
