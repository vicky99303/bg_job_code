<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    runBackgroundJob('App\\Jobs\\Bgjob', 'handle', ['key1' => 'value1', 'key2' => 'value2'], 3);
    //return view('welcome');
});
