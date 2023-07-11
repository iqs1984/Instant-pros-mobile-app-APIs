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

Route::group( ['middleware' => ['api']],function($router)
{
    Route::post('/signup',[AuthController::class,'signup']);
    Route::post('/login',[AuthController::class,'login']);
    Route::get('/categories',[CategoryController::class,'getAllCategories']);
    Route::get('/countries',[AddressController::class,'getAllCountries']);
    Route::get('/states/{country_id?}',[AddressController::class,'getAllStates']);
    Route::get('/cities/{state_id?}',[AddressController::class,'getAllCities']);
    Route::post('/userFcmToken',[UserController::class,'getUserFcmTokens']);
    Route::get('/getVendorServices',[UserController::class,'getVendorServices']);
    Route::get('/getVendorAbout',[UserController::class,'getVendorAbout']);
    Route::post('/uploadGalleryImage',[UserController::class,'uploadGalleryImage']);
    Route::post('/getGalleryImages',[UserController::class,'getGalleryImages']);
    Route::post('/deleteGalleryImage',[UserController::class,'deleteGalleryImage']);

    Route::group( ['middleware' => 'auth.jwt', 'prefix' => 'auth'],function($router)
    {
        Route::get('/userDetails',[UserController::class,'getUserDetails']);
        Route::post('/updateFcmToken',[UserController::class,'UpdateUserFcmTokens']);
        Route::post('/updateChatUserId',[UserController::class,'UpdateChatUserID']);
        Route::post('/addService',[UserController::class,'addService']);
        Route::post('/updateService',[UserController::class,'updateService']);
        Route::post('/deleteService',[UserController::class,'deleteService']);
        Route::post('/addVendorAbout',[UserController::class,'addVendorAbout']);
    });
});
