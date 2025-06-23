<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ClaimController;
use App\Http\Controllers\Api\InsurerController;
use App\Http\Controllers\Api\OptimizationController;




// Submit Claim
Route::post('/claims', [ClaimController::class, 'submit']);

// Get claims for a specific insurer
Route::get('/insurers/{id}/claims', [InsurerController::class, 'claims']);

//Insurer
Route::get('/insurers', [InsurerController::class, 'index']);
Route::get('/insurers/{id}', [InsurerController::class, 'show']);
Route::post('/insurers', [InsurerController::class, 'store']);
Route::put('/insurers/{id}', [InsurerController::class, 'update']);
Route::delete('/insurers/{id}', [InsurerController::class, 'destroy']);
Route::get('/insurers/{id}/batches', [InsurerController::class, 'batches']);
Route::get('/insurers/{code}/batchDetails/{batchDate?}', [InsurerController::class, 'batchDetails']);

// Optimization and Cost Analysis
Route::get('/insurers/{id}/optimization-recommendations', [OptimizationController::class, 'getRecommendations']);
Route::post('/insurers/{id}/optimize-batching', [OptimizationController::class, 'optimizeBatching']);
Route::get('/claims/{id}/cost-breakdown', [OptimizationController::class, 'getClaimCostBreakdown']);
Route::get('/insurers/{id}/cost-analysis', [OptimizationController::class, 'getCostAnalysis']);

