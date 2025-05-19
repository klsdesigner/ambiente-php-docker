<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
Route::prefix('v1')->group(function () {
    Route::get('/', function (Request $request) {
        return response()->json([
            'status' => 'success',
            'version' => 'v1',
            'baseUrl' => 'http://localhost:8080/api/v1',
        ], 200);
    }); 


    // Route::get('/users', 'UserController@index')->name('api.users.index');
    // Route::get('/users/{user}', 'UserController@show')->name('api.users.show');
    // Route::post('/users', 'UserController@store')->name('api.users.store');
    // Route::put('/users/{user}', 'UserController@update')->name('api.users.update');
    // Route::delete('/users/{user}', 'UserController@destroy')->name('api.users.destroy');
});

