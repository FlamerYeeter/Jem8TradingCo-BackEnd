<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\AdminLeadershipController;

use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\EnsureTokenIsValid;
use App\Http\Controllers\AdminProductController;
use App\Http\Controllers\UserAddressController;
use App\Http\Controllers\CategoryController;
// Public routes
Route::post('/login', [AccountController::class, 'login']);
Route::post('/register', [AccountController::class, 'store']);
Route::post('/verify', [AccountController::class, 'verifyEmail']);
Route::post('/forgot-password', [AccountController::class, 'forgotPassword']);
Route::post('/reset-password', [AccountController::class, 'resetPassword']);


// Reviews (public)
Route::get('/reviews', [ReviewController::class, 'all']);
Route::get('/reviews/{review}', [ReviewController::class, 'show']);
Route::get('/products/{product}/reviews', [ReviewController::class, 'index']);
<<<<<<< HEAD

//Prods



=======
Route::get('/categories', [CategoryController::class, 'index']);
>>>>>>> b72102445d6da729602f0c0ea850cc8c9f12f57c

// Routes that require authentication
Route::middleware([EnsureTokenIsValid::class]   )->group(function () {


    // Account
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

    Route::middleware('verified')->get('/dashboard', function () {
        return response()->json(['message' => 'Welcome verified user']);
    });

    // Shop
<<<<<<< HEAD
    // Route::get('/products', [ShopController::class, 'index']);
    // Route::get('/products/category/{category}', [ShopController::class, 'productsByCategory']);
    // Route::post('/products', [ShopController::class, 'addProduct']);
    // Route::put('/products/{id}', [ShopController::class, 'updateProduct']);
    
    //
=======
    //Hello
    Route::post('/cart/add', [ShopController::class, 'addToCart']);
    Route::delete('/cart/{id}', [ShopController::class, 'deleteFromCart']);
    Route::patch('/cart/{id}',[ShopController::class, 'updateCartQuantity']);
    Route::get('/cart',[ShopController::class, 'viewCart']);
>>>>>>> b72102445d6da729602f0c0ea850cc8c9f12f57c

    // Admin Products
    Route::post('/admin/products', [AdminProductController::class, 'addProduct']);
    Route::get('/admin/products', [AdminProductController::class, 'showAllProducts']);
    Route::get('/admin/products/{id}', [AdminProductController::class, 'showProduct']);
    Route::put('/admin/products/{id}', [AdminProductController::class, 'updateProduct']);
    Route::delete('/admin/products/{id}', [AdminProductController::class, 'deleteProduct']);

    // Blogs
    Route::get('/blogs', [BlogController::class, 'indexBlog']);
    Route::post('/blogs', [BlogController::class, 'storeBlog']);
    Route::get('/blogs/{id}', [BlogController::class, 'showAllBlog']);
    Route::put('/blogs/{id}', [BlogController::class, 'blogUpdates']);
    Route::delete('/blogs/{id}', [BlogController::class, 'deleteBlog']);

    // Reviews (authenticated)
    Route::post('/products/{product}/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{review}', [ReviewController::class, 'update']);
    Route::patch('/reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);

    // Cart
<<<<<<< HEAD
    Route::post('/cart/add', [ShopController::class, 'addToCart']);
    Route::delete('/cart/{id}', [ShopController::class, 'deleteFromCart']);
    Route::put('/cart/{id}', [ShopController::class, 'updateCartQuantity']);
    Route::get('/products/{id}', [ShopController::class, 'showProduct']);

=======
    // Route::get('/cart', [CartController::class, 'index']);
    // Route::post('/cart', [CartController::class, 'store']);
    // Route::put('/cart/{cart}', [CartController::class, 'update']);
    // Route::patch('/cart/{cart}', [CartController::class, 'update']);
    // Route::delete('/cart/{cart}', [CartController::class, 'destroy']);
    // Route::delete('/cart/product/{product}', [CartController::class, 'destroyByProduct']);
    // Route::post('/cart/clear', [CartController::class, 'clear']);
>>>>>>> b72102445d6da729602f0c0ea850cc8c9f12f57c

    // Checkout
    

    // Addresses
    Route::get('/addresses', [UserAddressController::class, 'index']);
    Route::post('/addresses', [UserAddressController::class, 'store']);
    Route::get('/addresses/{id}', [UserAddressController::class, 'show']);
    Route::put('/addresses/{id}', [UserAddressController::class, 'update']);
    Route::delete('/addresses/{id}', [UserAddressController::class, 'destroy']);


    //prods
    Route::post('/products', [ShopController::class, 'addProduct']);
    Route::put('/products/{id}', [ShopController::class, 'updateProduct']);
    Route::delete('/products/{id}', [ShopController::class, 'deleteProduct']);

    //admin leadership
    Route::prefix('admin')->group(function () {
        Route::get('/imgs',           [AdminLeadershipController::class, 'adminImgIndex']);
        Route::post('/imgs/store',    [AdminLeadershipController::class, 'adminImgStore']);
        Route::get('/imgs/{id}',      [AdminLeadershipController::class, 'adminImgShow']);   // fix: was pointing to wrong method
        Route::put('/imgs/{id}',      [AdminLeadershipController::class, 'adminImgUpdate']);
        Route::delete('/imgs/{id}',   [AdminLeadershipController::class, 'adminImgDelete']);
    });

});