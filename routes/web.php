<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VideoController;

Route::get('/', [VideoController::class, 'index'])->name('home');
Route::post('/compress', [VideoController::class, 'compress'])->name('compress');
Route::post('/convert', [VideoController::class, 'convert'])->name('convert');
