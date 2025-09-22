<?php

use Illuminate\Support\Facades\Route;

Route::get('error-handler', function () {
    return returnResponse("Unexpected Error", 400);
})->name('error-handler');
