<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\AdminLeadershipController;
use App\Http\Controllers\AdminBackupController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\EnsureTokenIsValid;
use App\Http\Controllers\AdminProductController;
use App\Http\Controllers\UserAddressController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactController;


// ─── Public Routes ───────────────────────────────────────────────────────────
Route::post('/login',          [AccountController::class, 'login']);
Route::post('/register',       [AccountController::class, 'store']);
Route::post('/verify',         [AccountController::class, 'verifyEmail']);
Route::post('/forgot-password',[AccountController::class, 'forgotPassword']);
Route::post('/reset-password', [AccountController::class, 'resetPassword']);

// Reviews (public)
Route::get('/reviews',                    [ReviewController::class, 'all']);
Route::get('/reviews/{review}',           [ReviewController::class, 'show']);
Route::get('/products/{product}/reviews', [ReviewController::class, 'index']);
Route::get('/categories',                 [CategoryController::class, 'index']);
Route::post('/contact',                   [ContactController::class, 'store']);


// ─── Authenticated Routes ─────────────────────────────────────────────────────
Route::middleware([EnsureTokenIsValid::class])->group(function () {

    // Account
    Route::get('/me',                    [AccountController::class, 'me']);
    Route::post('/profile/update',       [AccountController::class, 'updateProfile']);
    Route::post('/profile/update-image', [AccountController::class, 'updateProfileImage']);
    Route::delete('/delete-account',     [AccountController::class, 'destroy']);
    Route::post('/logout',               [AccountController::class, 'logout']);

    // Email Verification
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

    // Products (public-ish, but requires auth)
    Route::get('/products/{id}',    [ShopController::class, 'showProduct']);
    Route::post('/products',        [ShopController::class, 'addProduct']);
    Route::put('/products/{id}',    [ShopController::class, 'updateProduct']);
    Route::delete('/products/{id}', [ShopController::class, 'deleteProduct']);

    // Cart
    Route::get('/cart',             [ShopController::class, 'viewCart']);
    Route::post('/cart/add',        [ShopController::class, 'addToCart']);
    Route::patch('/cart/{id}',      [ShopController::class, 'updateCartQuantity']);
    Route::put('/cart/{id}',        [ShopController::class, 'updateCartQuantity']);
    Route::delete('/cart/{id}',     [ShopController::class, 'deleteFromCart']);

    // Reviews (authenticated)
    Route::post('/products/{product}/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{review}',            [ReviewController::class, 'update']);
    Route::patch('/reviews/{review}',          [ReviewController::class, 'update']);
    Route::delete('/reviews/{review}',         [ReviewController::class, 'destroy']);

    // Addresses
    Route::get('/addresses',       [UserAddressController::class, 'index']);
    Route::post('/addresses',      [UserAddressController::class, 'store']);
    Route::get('/addresses/{id}',  [UserAddressController::class, 'show']);
    Route::put('/addresses/{id}',  [UserAddressController::class, 'update']);
    Route::delete('/addresses/{id}', [UserAddressController::class, 'destroy']);

    // Blogs
    Route::get('/blogs',       [BlogController::class, 'indexBlog']);
    Route::post('/blogs',      [BlogController::class, 'storeBlog']);
    Route::get('/blogs/{id}',  [BlogController::class, 'showAllBlog']);
    Route::put('/blogs/{id}',  [BlogController::class, 'blogUpdates']);
    Route::delete('/blogs/{id}', [BlogController::class, 'deleteBlog']);

    // Admin Products
    Route::prefix('admin')->group(function () {
        Route::post('/products',       [AdminProductController::class, 'addProduct']);
        Route::get('/products',        [AdminProductController::class, 'showAllProducts']);
        Route::get('/products/{id}',   [AdminProductController::class, 'showProduct']);
        Route::put('/products/{id}',   [AdminProductController::class, 'updateProduct']);
        Route::delete('/products/{id}',[AdminProductController::class, 'deleteProduct']);

        // Admin Contacts
        Route::get('/contacts',                  [ContactController::class, 'index']);
        Route::get('/contacts/{id}',             [ContactController::class, 'show']);
        Route::patch('/contacts/{id}/status',    [ContactController::class, 'updateStatus']);
        Route::delete('/contacts/{id}',          [ContactController::class, 'destroy']);
        Route::post('/contacts/{id}/reply',      [ContactController::class, 'reply']);

        // Admin Leadership Images
        Route::get('/imgs',          [AdminLeadershipController::class, 'adminImgIndex']);
        Route::post('/imgs/store',   [AdminLeadershipController::class, 'adminImgStore']);
        Route::get('/imgs/{id}',     [AdminLeadershipController::class, 'adminImgShow']);
        Route::put('/imgs/{id}',     [AdminLeadershipController::class, 'adminImgUpdate']);
        Route::delete('/imgs/{id}',  [AdminLeadershipController::class, 'adminImgDelete']);

        // Admin Backup
        Route::prefix('backup')->group(function () {
            Route::get('/',               [AdminBackupController::class, 'adminHistoryBackup']);
            Route::post('/run',           [AdminBackupController::class, 'adminRunBackup']);
            Route::get('/download/{id}',  [AdminBackupController::class, 'adminDownloadBackup']);
            Route::delete('/{id}',        [AdminBackupController::class, 'adminDeleteBackup']);
            Route::post('/restore',       [AdminBackupController::class, 'adminUploadRestore']);
        });
    });

});
