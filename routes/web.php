<?php

use Illuminate\Support\Facades\Route;

Route::get('/{any}', function () {
    return view('spa'); // 'spa' là view chứa ứng dụng Vue.js
})->where('any', '.*'); // Chấp nhận mọi route con