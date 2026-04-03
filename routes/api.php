<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\API\ConsigneController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\NormalisationController;
use App\Http\Controllers\API\CodificationController;

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

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [UserController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('user')->group(function () {



       //Route::post('/register', [UserController::class, 'store']);

    });

    Route::prefix('parametrage')->group(function () {
        Route::get('/list-codifications', [\App\Http\Controllers\API\CodificationController::class, 'listCodification']);
        Route::get('/list-champs', [\App\Http\Controllers\API\CodificationController::class, 'getChampsByCodeDossier']);
        Route::get('/codifications', [\App\Http\Controllers\API\CodificationController::class, 'getId']);
        Route::get('/nom-lot', [\App\Http\Controllers\API\CodificationController::class, 'getNomLot']);
    });

    Route::prefix('consigne')->group(function () {
        Route::get('/list', [ConsigneController::class, 'listAll']);
        Route::post('/parametrage/add', [ConsigneController::class, 'store']);
        Route::put('/parametrage/update/{codificationId}', [ConsigneController::class, 'update']);
        Route::get('/parametrage/{codificationId}', [ConsigneController::class, 'edit']);
    });

    Route::apiResource('/parametre/datamap', \App\Http\Controllers\API\DatamapController::class);

    Route::post('/normalisation/{codification_id}', [\App\Http\Controllers\API\NormalisationController::class, 'normaliser']);
    Route::get('/normalise', [\App\Http\Controllers\API\NormalisationController::class, 'importParametre']);
});

Route::get('/assemblage', [\App\Http\Controllers\API\NormalisationController::class, 'AssemblageMdb']);
Route::get('/importmdb', [\App\Http\Controllers\API\NormalisationController::class, 'importMdb']);
Route::get('/lots', [\App\Http\Controllers\API\NormalisationController::class, 'getLots']);
Route::get('/testa', [\App\Http\Controllers\API\NormalisationController::class, 'testa']);
Route::get('/authtest', [\App\Http\Controllers\API\TestController::class, 'testPg']);
Route::get('/downloadexcel/{filename}', [\App\Http\Controllers\API\NormalisationController::class, 'downloadExcel']);
Route::post('datamaps/export', [\App\Http\Controllers\API\DatamapController::class, 'exportToTxt']);
Route::get('downloadtxt/{filename}', [App\Http\Controllers\API\DatamapController::class, 'downloadTxt']);
Route::post('/excel/import', [\App\Http\Controllers\API\NormalisationController::class, 'importExcel']);
