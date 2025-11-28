<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\AuthController;

// Authentication routes
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Public campaign routes
Route::get('campaigns', [CampaignController::class, 'index']);
Route::get('campaigns/slug/{slug}', [CampaignController::class, 'showBySlug']);

// Public donation routes
Route::post('donations', [DonationController::class, 'store']);
Route::get('donations', [DonationController::class, 'index']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    
    // Campaign management routes
    Route::get('campaigns/all', [CampaignController::class, 'all']);
    Route::post('campaigns', [CampaignController::class, 'store']);
    Route::get('campaigns/{campaign}', [CampaignController::class, 'show']);
    Route::put('campaigns/{campaign}', [CampaignController::class, 'update']);
    Route::delete('campaigns/{campaign}', [CampaignController::class, 'destroy']);
    Route::put('campaigns/{campaign}/status', [CampaignController::class, 'updateStatus']);
    
    // Donation management routes
    Route::get('donations/{id}', [DonationController::class, 'show']);
    Route::put('donations/{id}', [DonationController::class, 'update']);
    Route::delete('donations/{id}', [DonationController::class, 'destroy']);
});