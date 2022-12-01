<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\OrderController;

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

/* Авторизация без api_token */
Route::group(['prefix' => 'auth'], function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/forgot', [AuthController::class, 'forgot'])->name('forgot');
    Route::post('/code', [AuthController::class, 'code'])->name('code');
    Route::post('/changePassword', [AuthController::class, 'changePassword'])->name('changePassword');
});

/* Авторизация с api_token */
Route::group(['prefix' => 'auth', 'middleware' => "api_auth"], function () {
    Route::post('/rebootpassword', [AuthController::class, 'rebootpassword'])->name('rebootpassword');
    Route::post('/change', [AuthController::class, 'change'])->name('change');
    Route::post('/address', [AuthController::class, 'address']);
    Route::post('/view', [AuthController::class, 'view'])->name('authview');
});

/* лицевые блоки */
Route::get('/title', [IndexController::class, 'title']);
Route::post('/application', [IndexController::class, 'application']);
Route::get('/cafe', [IndexController::class, 'cafe']);
Route::get('/index', [IndexController::class, 'index']);
Route::get('/contact', [IndexController::class, 'contact']);
Route::get('/delivery', [IndexController::class, 'delivery']);
Route::get('/vacancy', [IndexController::class, 'vacancy']);
Route::get('/ordercreate', [IndexController::class, 'ordercreate']);
Route::get('/recommendation', [IndexController::class, 'recommendation']);

/* товары */
Route::group(['prefix' => 'item'], function () {
    Route::get('/', [ItemController::class, 'item']);
    Route::get('/full', [ItemController::class ,'full']);
    Route::get('/category', [ItemController::class, 'category']);
});

Route::group(['prefix' => 'favorite', 'middleware' => 'api_auth'], function () {
   Route::post('/{id}', [ItemController::class, 'addFavorite']);
   Route::delete('/{id}', [ItemController::class, 'deleteFavorite']);
});

/* заказ */
Route::group(['prefix' => 'order'], function () {
    Route::get('/help', [OrderController::class, 'help']);
    Route::post('/create', [OrderController::class, 'create']);
    Route::post('/view', [OrderController::class, 'view'])->middleware('api_auth');
});

