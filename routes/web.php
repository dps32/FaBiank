<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\InvestmentController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PaymentRequestController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\SearchUserController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TwoFactorController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/dashboard');
    }

    return redirect('/login');
})->name('home');

// Register / Login — solo para usuarios no autenticados
Route::middleware('guest')->group(function () {
    Route::get('/register', fn() => view('register'))->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

    Route::get('/login', fn() => view('login'))->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store')->middleware('throttle:5,1');
});

// Two factor
Route::get('/2fa/setup', [TwoFactorController::class, 'setupCreate'])->name('two-factor.setup.show');
Route::post('/2fa/setup', [TwoFactorController::class, 'setupStore'])->name('two-factor.setup.store');
Route::get('/2fa/challenge', [TwoFactorController::class, 'challengeCreate'])->name('two-factor.challenge.show');
Route::post('/2fa/challenge', [TwoFactorController::class, 'challengeStore'])->name('two-factor.challenge.store')->middleware('throttle:5,1');

// Logout
Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'show'])
    ->middleware('auth')
    ->name('dashboard');

// History
Route::get('/history', [HistoryController::class, 'show'])
    ->middleware('auth')
    ->name('history');

// Investments
Route::get('/investments', [InvestmentController::class, 'show'])
    ->middleware('auth')
    ->name('investments');

// API: Dashboard state (for polling)
Route::get('/api/dashboard-state', [DashboardController::class, 'state'])
    ->middleware('auth')
    ->name('api.dashboard-state');

// API: Search users
Route::get('/api/search-users', [SearchUserController::class, 'search'])
    ->middleware('auth')
    ->name('api.search-users');

// API: Create transaction
Route::post('/api/transactions', [TransactionController::class, 'store'])
    ->middleware('auth')
    ->name('api.transactions.store');

// API: Create payment request
Route::post('/api/payment-requests', [PaymentRequestController::class, 'store'])
    ->middleware('auth')
    ->name('api.payment-requests.store');

// API: Respond payment request
Route::post('/api/payment-requests/{paymentRequestId}/respond', [PaymentRequestController::class, 'respond'])
    ->middleware('auth')
    ->name('api.payment-requests.respond');

// API: Investment — stock prices
Route::get('/api/investments/prices', [InvestmentController::class, 'prices'])
    ->middleware('auth')
    ->name('api.investments.prices');

// API: Investment — buy
Route::post('/api/investments/buy', [InvestmentController::class, 'buy'])
    ->middleware('auth')
    ->name('api.investments.buy');

// API: Investment — sell
Route::post('/api/investments/{investmentId}/sell', [InvestmentController::class, 'sell'])
    ->middleware('auth')
    ->name('api.investments.sell');


