<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\UserController;
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

Route::group(['middleware' => 'api', 'prefix' => 'auth'],function($router)
{
    Route::post('/register',[AuthController::class,'register']);
    Route::post('/login',[AuthController::class,'login']);
    // Route::get('/user', [AuthController::class, 'getUser'])->middleware('auth.jwt');

    Route::get('/categories',[CategoryController::class,'getAllCategories']);
    Route::get('/countries',[AddressController::class,'getAllCountries']);
    Route::get('/states/{country_id?}',[AddressController::class,'getAllStates']);
    Route::get('/cities/{state_id?}',[AddressController::class,'getAllCities']);
    Route::get('/userDetails',[UserController::class,'getUserDetails'])->middleware('auth.jwt');

});
