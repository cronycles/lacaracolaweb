<?php

use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\ApartmentController;
use App\Http\Controllers\Public\MapController;
use App\Http\Controllers\Public\ExperiencesController;
use App\Http\Controllers\Public\ReviewsController;
use App\Http\Controllers\Public\RulesController;
use App\Http\Controllers\Public\UsefulPlacesController;
use App\Http\Controllers\Public\BookingController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Admin\LoginController;
use Illuminate\Support\Facades\Route;

// --- Admin auth (no middleware guard — these are the login/logout endpoints) ---
Route::get('/admin/login', [LoginController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [LoginController::class, 'login'])->name('admin.login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// --- Locale switch ---
Route::post('/locale', [LocaleController::class, 'switch'])->name('locale.switch');

// --- Public routes ---
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/appartamento', [ApartmentController::class, 'index'])->name('apartment');
Route::get('/dove-siamo', [MapController::class, 'index'])->name('map');
Route::get('/esperienze', [ExperiencesController::class, 'index'])->name('experiences');
Route::get('/recensioni', [ReviewsController::class, 'index'])->name('reviews');
Route::get('/regole-casa', [RulesController::class, 'index'])->name('rules');
Route::get('/posti-utili', [UsefulPlacesController::class, 'index'])->name('useful-places');

// --- Booking availability request (flow B) ---
Route::post('/disponibilita', [BookingController::class, 'requestAvailability'])->name('booking.request');
Route::get('/disponibilita/grazie', [BookingController::class, 'thanks'])->name('booking.thanks');
