<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/signup', [LoginController::class, 'signup'])->name('signup');
Route::post('/register', [LoginController::class, 'register'])->name('register');

Route::get('/login', [LoginController::class, 'login'])->name('login');
Route::post('/dologin', [LoginController::class, 'doLogin'])->name('dologin');

Route::middleware('checkIfConnected')->group(function () {
    Route::get('/home', [LoginController::class, 'home'])->name('home');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});