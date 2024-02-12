<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TicketController;

// Public routes (do not require authentication)
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:120,1');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:120,1');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:120,1');
Route::post('/change-password', [AuthController::class, 'changePassword'])->middleware('throttle:120,1');

// Authenticated routes (require authentication via Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user'])->middleware('throttle:120,1');
    Route::post('/store-ticket', [TicketController::class, 'store'])->middleware('throttle:120,1');
    Route::post('/charge', [TicketController::class, 'charge'])->middleware('throttle:120,1');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('throttle:120,1');
    Route::post('/get-all-users', [AuthController::class, 'getAllUsers'])->middleware('throttle:120,1');
});
Route::get('invalid',function(){
	 return response()->json(['message'=>'Access token not matched'],422);
})->name('invalid');
