<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This is a REST API only project. All API routes are defined in routes/api.php.
| The web route below provides a simple health check endpoint.
|
*/

Route::get('/', function () {
    return response()->json([
        'name' => 'Posyandu API',
        'version' => '1.0.0',
        'status' => 'running',
    ]);
});
