<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\DonationController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Campaign routes
Route::apiResource('campaigns', CampaignController::class);
Route::get('campaigns/slug/{slug}', [CampaignController::class, 'show']);
Route::post('campaigns/{id}/close', [CampaignController::class, 'close']);
Route::post('campaigns/{id}/reopen', [CampaignController::class, 'reopen']);

// Donation routes
Route::apiResource('donations', DonationController::class);