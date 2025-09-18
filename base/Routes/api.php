<?php

use Base\Auth\AuthController;
use Base\Request\RequestController;
use Illuminate\Support\Facades\Route;


Route::group(['middleware' => 'validation'], function () {
    Route::post('auth', [AuthController::class, 'auth']);
    Route::group(['middleware' => 'auth:api'], function () {
        Route::post('request', [RequestController::class, 'customRequest']);
    });
    Route::post('grequest',[RequestController::class,'customRequest']);
});
