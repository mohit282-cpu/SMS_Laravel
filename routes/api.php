<?php

use Illuminate\Http\Request;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('throttle:120,1')->group(function () {
    Route::get('/provinces', [\App\Http\Controllers\NepalLocationController::class, 'provinces'])->name('api.nepal.provinces');
    Route::get('/provinces/{province}/districts', [\App\Http\Controllers\NepalLocationController::class, 'apiProvinceDistricts'])->where('province', '[0-9]+')->name('api.nepal.province_districts');
    Route::get('/districts/{district}/local-governments', [\App\Http\Controllers\NepalLocationController::class, 'apiDistrictLocalGovernments'])->where('district', '[0-9]+')->name('api.nepal.district_locals');
    Route::get('/local-governments/{id}', [\App\Http\Controllers\NepalLocationController::class, 'apiShowLocalGovernment'])->where('id', '[0-9]+')->name('api.nepal.local_show');
});
