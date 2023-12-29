<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SearchController;


Route::group( ['middleware' => ['api']],function($router)
{
    Route::post('/signup',[AuthController::class,'signup']);
    Route::post('/login',[AuthController::class,'login']);
    Route::post('/socialLogin',[AuthController::class,'socialLogin']);
    Route::post('/forgotPasswordMail',[AuthController::class,'forgotPasswordMail']);
    Route::post('/resetPassword',[AuthController::class,'resetPassword']);
    Route::get('/categories',[CategoryController::class,'getAllCategories']);
    Route::get('/vendorSearch',[SearchController::class,'vendorSearch']);
    Route::get('/countries',[AddressController::class,'getAllCountries']);
    Route::get('/states/{country_id?}',[AddressController::class,'getAllStates']);
    Route::get('/cities/{state_id?}',[AddressController::class,'getAllCities']);
    Route::post('/userFcmToken',[UserController::class,'getUserFcmTokens']);
    Route::get('/getVendorServices',[UserController::class,'getVendorServices']);
    Route::get('/getVendorAbout',[UserController::class,'getVendorAbout']);
    Route::get('/getGalleryImages',[UserController::class,'getGalleryImages']);
    Route::post('/deleteGalleryImage',[UserController::class,'deleteGalleryImage']);
    Route::post('/updateProfileImage',[UserController::class,'updateProfileImage']);
    Route::post('/getDocument',[UserController::class,'getDocument']);
    Route::get('/getVendorByCategoryId',[UserController::class,'getVendorByCategoryId']);
    Route::post('/getVendorReviews',[UserController::class,'getVendorReviews']);
    Route::post('/deleteVendorSlot',[UserController::class,'deleteVendorSlot']);
    Route::post('/getVendorSlot',[UserController::class,'getVendorSlot']);
    Route::post('/updateService',[UserController::class,'updateService']);
    Route::post('/ratingPercentage',[UserController::class,'ratingPercentage']);
    Route::post('/processingFee',[UserController::class,'processingFee']);
    Route::post('/dateList',[UserController::class,'dateList']);
    Route::get('/getDisbursementFee',[UserController::class,'getDisbursementFee']);
    Route::post('/getOrderDetails',[OrderController::class,'getOrderDetails']);
    Route::post('/deleteNotification',[OrderController::class,'deleteNotification']);
    Route::post('/updateProcessingFee',[OrderController::class,'updateProcessingFee']);
    Route::post('/updateEscrowTransctionID',[OrderController::class,'updateEscrowTransctionID']);
    Route::post('/updatePaymentStatus',[OrderController::class,'updatePaymentStatus']);
   

    

    Route::group( ['middleware' => 'auth.jwt', 'prefix' => 'auth'],function($router)
    {
        Route::post('/changePassword',[AuthController::class,'changePassword']);
        Route::get('/userDetails',[UserController::class,'getUserDetails']);
        Route::post('/updateFcmToken',[UserController::class,'updateFcmToken']);
        Route::post('/updateChatUserId',[UserController::class,'UpdateChatUserID']);
        Route::post('/addService',[UserController::class,'addService']);
        Route::post('/deleteService',[UserController::class,'deleteService']);
        Route::post('/addVendorAbout',[UserController::class,'addVendorAbout']);
        Route::post('/UpdateUserDetails',[UserController::class,'UpdateUserDetails']);
        Route::post('/addEscrowAccount',[UserController::class,'addEscrowAccount']);
        Route::post('/setPublishedStatus',[UserController::class,'setPublishedStatus']);
        Route::post('/createReviews',[UserController::class,'createReviews']);
        Route::post('/addVendorSlot',[UserController::class,'addVendorSlot']);
        Route::post('/addRemoveFavorite',[UserController::class,'addRemoveFavorite']);
        Route::get('/getFavoriteVendors',[UserController::class,'getFavoriteVendors']);
        Route::post('/uploadGalleryImage',[UserController::class,'uploadGalleryImage']);
        Route::post('/getEscrowAccount',[UserController::class,'getEscrowAccount']);
        Route::post('/createOrder',[OrderController::class,'createOrder']);
        Route::get('/myBooking',[OrderController::class,'myBooking']);
        Route::get('/getOrderByStatus',[OrderController::class,'getOrderByStatus']);
        Route::post('/orderUpdate',[OrderController::class,'orderUpdate']);
        Route::post('/orderReschedule',[OrderController::class,'orderReschedule']);
        Route::get('/getNotification',[OrderController::class,'getNotification']);
        Route::post('/unreadNotification',[OrderController::class,'unreadNotification']);
        Route::get('/myTransaction',[OrderController::class,'myTransaction']);
        Route::post('/saveUserSecondaryAddress',[AddressController::class,'saveUserSecondaryAddress']);
        Route::post('/updateUserSecondaryAddress',[AddressController::class,'updateUserSecondaryAddress']);
        Route::post('/deleteUserSecondaryAddress',[AddressController::class,'deleteUserSecondaryAddress']);
        Route::get('/UserSecondaryAddressList',[AddressController::class,'UserSecondaryAddressList']);
    });
});
