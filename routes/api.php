<?php

use App\Http\Controllers\productController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/', [productController::class, 'index']);
Route::post('store', [productController::class, 'store']);
Route::put('update/{id}', [productController::class, 'update']);
Route::delete('delete/{id}', [productController::class, 'destroy']);
Route::get('show/{id}', [productController::class, 'show']);
Route::post('/save_quantity/', [ProductController::class, 'saveQuantity']);
Route::post('/addProductWithQuantity', [productController::class, 'addProductWithQuantity']);
Route::post('importProduct/{id}', [productController::class, 'importProduct']);
Route::post('exportProduct/{id}', [productController::class, 'exportProduct']);
Route::get('showImports', [productController::class, 'showImports']);
Route::get('showExports', [productController::class, 'showExports']);
Route::get('showImportsAndExports', [productController::class, 'showImportsAndExports']);