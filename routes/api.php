<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\InternshipApplicationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/test/validate-application', [InternshipApplicationController::class, 'validateApplication']);

Route::post('/test/validate-application-procedure', [InternshipApplicationController::class, 'validateApplicationWithProcedure']);

// Comments (polymorphic)
Route::post('/comments', [CommentController::class, 'store']);
Route::get('/comments/{type}/{id}', [CommentController::class, 'index']);
