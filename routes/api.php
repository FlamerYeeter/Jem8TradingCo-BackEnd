<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\EnsureTokenIsValid;
use App\Http\Controllers\AdminProductController;
use App\Http\Controllers\UserAddressController;
Route::post('/login', [AccountController::class, 'login']);
Route::post('/register', [AccountController::class, 'store']);
Route::post('/verify', [AccountController::class, 'verifyEmail']);
Route::post('/forgot-password', [AccountController::class, 'forgotPassword']);
Route::post('/reset-password', [AccountController::class, 'resetPassword']);

route::get('/products/{id}', [ShopController::class, 'showProduct']);

// Reviews (public)
Route::get('/reviews', [ReviewController::class, 'all']);
Route::get('/reviews/{review}', [ReviewController::class, 'show']);
Route::get('/products/{product}/reviews', [ReviewController::class, 'index']);


// Routes that require authentication
Route::middleware('auth:sanctum')->group(function () {

    // Route only accessible to authenticated users
    Route::get('/me', [AccountController::class, 'me']);
    Route::post('/profile/update', [AccountController::class, 'updateProfile']);
    Route::post('/profile/update-image', [AccountController::class, 'updateProfileImage']);
    Route::delete('/delete-account', [AccountController::class, 'destroy']);

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
    Route::get('/products/{id}', [ShopController::class, 'showProduct']);
    Route::post('/cart/add', [ShopController::class, 'addToCart']);
    Route::delete('/cart/{id}', [ShopController::class, 'deleteFromCart']);
    Route::post('/admin/products', [AdminProductController::class, 'addProduct']);
    Route::get('/admin/products', [AdminProductController::class, 'showAllProducts']);
    Route::get('/admin/products/{id}', [AdminProductController::class, 'showProduct']);
    Route::put('/admin/products/{id}', [AdminProductController::class, 'updateProduct']);
    Route::delete('/admin/products/{id}', [AdminProductController::class, 'deleteProduct']);

    //blogs
    Route::get('/blogs', [BlogController::class, 'indexBlog']);
    Route::post('/blogs', [BlogController::class, 'storeBlog']);
    Route::get('/blogs/{id}', [BlogController::class, 'showAllBlog']);
    Route::put('/blogs/{id}', [BlogController::class, 'blogUpdates']);
    Route::delete('/blogs/{id}', [BlogController::class, 'deleteBlog']);
    
});
=======
    // Authenticated review routes
    Route::post('/products/{product}/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{review}', [ReviewController::class, 'update']);
    Route::patch('/reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);

    // Cart routes
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::put('/cart/{cart}', [CartController::class, 'update']);
    Route::patch('/cart/{cart}', [CartController::class, 'update']);
    Route::delete('/cart/{cart}', [CartController::class, 'destroy']);
    Route::delete('/cart/product/{product}', [CartController::class, 'destroyByProduct']);
    Route::post('/cart/clear', [CartController::class, 'clear']);
    // Checkout
    Route::post('/checkout', [CheckoutController::class, 'store']);

    Route::get('/addresses', [UserAddressController::class, 'index']);
    Route::post('/addresses', [UserAddressController::class, 'store']);
    Route::get('/addresses/{id}', [UserAddressController::class, 'show']);
    Route::put('/addresses/{id}', [UserAddressController::class, 'update']);
    Route::delete('/addresses/{id}', [UserAddressController::class, 'destroy']);
});
>>>>>>> 56c9bc192dea37e0c9c945ceb4630dfbc85b88ed
