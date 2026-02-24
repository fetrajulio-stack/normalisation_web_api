<?php

use App\Http\Controllers\API\ConsigneController;
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

Route::get('/list-codifications', [\App\Http\Controllers\API\CodificationController::class, 'listCodification']);
Route::get('/list-champs', [\App\Http\Controllers\API\CodificationController::class, 'getChampsByCodeDossier']);
Route::post('/normalisation/{codification_id}', [\App\Http\Controllers\API\NormalisationController::class, 'normaliser']);
Route::post('/consignes/parametrage/add', [ConsigneController::class, 'store']);
Route::get('/consignes/list', [ConsigneController::class, 'listAll']);
Route::get('/consignes/parametrage/{codificationId}', [ConsigneController::class, 'edit']);
Route::post('/consignes/parametrage/update/{codificationId}', [ConsigneController::class, 'update']);

