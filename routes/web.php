<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
Route::get('/', function () {
    return view('welcome');
});
Route::get('/create-super-admin', function () {
    $user=new User;
    $user->name="super admin";
    $user->email="superadmin@gmail.com";
    $user->password=Hash::make('123456');
    $user->role="super-admin";
    $user->save();
    dd('donw');
});
Route::get('/db', function () {
    DB::statement('ALTER TABLE users MODIFY COLUMN note LONGTEXT;');
    dd('donw');
});

Route::get('clear',function(){
	Artisan::call('config:cache');
     Artisan::call('cache:clear');
     Artisan::call('view:clear');
     return 'Routes cache cleared';
});

