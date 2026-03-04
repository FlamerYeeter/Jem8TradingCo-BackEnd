<?php
<<<<<<< HEAD
=======


use App\Http\Controllers\ShopController;
>>>>>>> 8ee739177731040e4861b1a584d56f3d1a77b920
use App\Http\Controllers\AccountController;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\EnsureTokenIsValid;
use App\Http\Controllers\AdminProductController;

Route::post('/login', [AccountController::class, 'login']);
Route::post('/register', [AccountController::class, 'store']);
Route::post('/verify', [AccountController::class, 'verifyEmail']);
Route::post('/forgot-password', [AccountController::class, 'forgotPassword']);
Route::post('/reset-password', [AccountController::class, 'resetPassword']);

// Routes that require authentication
Route::middleware([EnsureTokenIsValid::class])->group(function () {

    // Route only accessible to authenticated users
    Route::get('/me', function(Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AccountController::class, 'logout']);

    // Email verification routes
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return response()->json(['message' => 'Email verified successfully']);
    })->middleware(['signed'])->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return response()->json(['message' => 'Verification link sent']);
    })->middleware(['throttle:6,1'])->name('verification.send');

    // Example: only verified users can access this route
    Route::middleware('verified')->get('/dashboard', function() {
        return response()->json(['message' => 'Welcome verified user']);
    });
<<<<<<< HEAD
});
=======

    Route::get('/products/{id}', [ShopController::class, 'showProduct']);
    Route::post('/cart/add', [ShopController::class, 'addToCart']);
    Route::delete('/cart/{id}', [ShopController::class, 'deleteFromCart']);
<<<<<<< HEAD
    
});
>>>>>>> 8ee739177731040e4861b1a584d56f3d1a77b920
=======
    Route::post('/admin/products', [AdminProductController::class, 'addProduct']);
    Route::get('/admin/products', [AdminProductController::class, 'showAllProducts']);
    Route::get('/admin/products/{id}', [AdminProductController::class, 'showProduct']);
    Route::put('/admin/products/{id}', [AdminProductController::class, 'updateProduct']);
    Route::delete('/admin/products/{id}', [AdminProductController::class, 'deleteProduct']);
});
>>>>>>> 4206f88515ca6956071ad98f210be0a8df7cc11f
