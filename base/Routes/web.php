<?php

use Illuminate\Support\Facades\Route;

Route::get('error-handler', function () {
    return returnResponse("You are not authorized to make this request", 400);
})->name('error-handler');
