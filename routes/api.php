<?php

use Illuminate\Support\Facades\Route;

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
Route::namespace('Api')->group(function() {
    Route::post('register', 'AuthController@register');
    Route::post('login', 'AuthController@login');
    Route::post('social_login', 'AuthController@loginWithSocialAccount');

    Route::post('password/forgot', 'AuthController@forgotPassword');
    Route::post('password/check_code', 'AuthController@checkResetPasswordCode');
    Route::post('password/reset', 'AuthController@resetPassword');

    Route::middleware(['auth:api'])->group(function() {
        Route::post('logout', 'AuthController@logout');
        Route::get('users/current', 'UserController@current');
    });
});
