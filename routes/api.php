<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\NormalisationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/normalise', [\App\Http\Controllers\API\NormalisationController::class, 'importParametre']);
Route::get('/assemblage', [\App\Http\Controllers\API\NormalisationController::class, 'AssemblageMdb']);
Route::get('/importmdb', [\App\Http\Controllers\API\NormalisationController::class, 'importMdb']);
Route::get('/testa', [\App\Http\Controllers\API\NormalisationController::class, 'testa']);
Route::get('/authtest', [\App\Http\Controllers\API\TestController::class, 'testPg']);
