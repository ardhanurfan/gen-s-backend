<?php

use App\Http\Controllers\API\AdsController;
use App\Http\Controllers\API\AudioController;
use App\Http\Controllers\API\AudioPlaylistController;
use App\Http\Controllers\API\HistoryController;
use App\Http\Controllers\API\GalleryController;
use App\Http\Controllers\API\ImageController;
use App\Http\Controllers\API\PlaylistController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('register', [UserController::class, 'register']);
Route::post('login', [UserController::class, 'login']);
Route::get('email/verify/{id}', [UserController::class, 'verify'])->name('verification.verify');

Route::post('forgot-password', [UserController::class, 'forgotPassword']);
Route::post('reset-password', [UserController::class, 'resetPassword']);

Route::get('gallery', [GalleryController::class, 'all']);
Route::post('create-gallery', [GalleryController::class, 'create']);
Route::post('delete-gallery', [GalleryController::class, 'delete']);

Route::post('add-image', [ImageController::class, 'add']);
Route::post('delete-image', [ImageController::class, 'delete']);
Route::post('move-image', [ImageController::class, 'move']);

Route::post('delete-audio', [AudioController::class, 'delete']);

Route::post('delete-playlist', [PlaylistController::class, 'delete']);
Route::post('swap-playlist', [PlaylistController::class, 'swap']);
Route::post('rename-playlist', [PlaylistController::class, 'rename']);

Route::post('add-audio-playlist', [AudioPlaylistController::class, 'add']);
Route::post('delete-audio-playlist', [AudioPlaylistController::class, 'delete']);
Route::post('swap-audio-playlist', [AudioPlaylistController::class, 'swap']);

Route::get('ads', [AdsController::class, 'all']);
Route::post('add-ads', [AdsController::class, 'add']);
Route::post('delete-ads', [AdsController::class, 'delete']);
Route::post('edit-ads', [AdsController::class, 'edit']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('user', [UserController::class, 'fetch']);
    Route::post('logout', [UserController::class, 'logout']);
    
    Route::get('history', [HistoryController::class, 'all']);
    Route::post('history', [HistoryController::class, 'add']);
    
    Route::get('audio', [AudioController::class, 'all']);
    Route::post('add-audio', [AudioController::class, 'add']);

    Route::get('playlist', [PlaylistController::class, 'all']);
    Route::post('add-playlist', [PlaylistController::class, 'add']);
});
