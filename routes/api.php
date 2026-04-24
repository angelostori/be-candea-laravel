<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// test
Route::get('/test', function () {
    return \App\Models\Product::with('categories')->first();
});
