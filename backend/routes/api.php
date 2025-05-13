<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::get('/', function (Request $request) {
    return response()->json([
        'status' => 'success',
        'version' => 'v1',
        'baseUrl' => 'http://localhost:9000/api',
    ]);
})->name('api.welcome'); 
