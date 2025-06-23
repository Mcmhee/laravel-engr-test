<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ClaimController;
use App\Http\Controllers\Api\InsurerController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::post('/claims', [ClaimController::class, 'submit']);



//Insurer
Route::get('/insurers', [InsurerController::class, 'index']);
Route::get('/insurers/{id}', [InsurerController::class, 'show']);
Route::post('/insurers', [InsurerController::class, 'store']);
Route::put('/insurers/{id}', [InsurerController::class, 'update']);
Route::delete('/insurers/{id}', [InsurerController::class, 'destroy']);
Route::get('/insurers/{id}/batches', [InsurerController::class, 'batches']);
Route::get('/insurers/{code}/batchDetails/{batchDate?}', [InsurerController::class, 'batchDetails']);

