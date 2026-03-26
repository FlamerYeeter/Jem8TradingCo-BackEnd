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
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\AdminLeadershipController;
use App\Http\Controllers\Dashboard;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AdminBackupController;

// Public routes
Route::post('/login', [AccountController::class, 'login']);
Route::post('/register', [AccountController::class, 'store']);
Route::post('/verify', [AccountController::class, 'verifyEmail']);
Route::post('/forgot-password', [AccountController::class, 'forgotPassword']);
Route::post('/reset-password', [AccountController::class, 'resetPassword']);

Route::get('/products/{id}', [ShopController::class, 'showProduct']);

// Reviews (public)
Route::get('/reviews', [ReviewController::class, 'all']);
Route::get('/reviews/{review}', [ReviewController::class, 'show']);
Route::get('/products/{product}/reviews', [ReviewController::class, 'index']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::post('/contact', [ContactController::class, 'store']);
Route::get('/findaccount/{id}', [AccountController::class, 'show']);
// Routes that require authentication
Route::middleware([EnsureTokenIsValid::class])->group(function () {

    // Account
    Route::get('/me', [AccountController::class, 'me']);
    Route::post('/profile/update', [AccountController::class, 'updateProfile']);
    Route::post('/profile/update-image', [AccountController::class, 'updateProfileImage']);
Route::delete('/accounts/{id}', [AccountController::class, 'adminDestroy']);
    Route::post('/logout', [AccountController::class, 'logout']);

    Route::get('/leadership', [AdminLeadershipController::class, 'adminImgIndex']);
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
    //Hello
    Route::post('/cart/add', [ShopController::class, 'addToCart']);
    Route::delete('/cart/{id}', [ShopController::class, 'deleteFromCart']);
    Route::patch('/cart/{id}',[ShopController::class, 'updateCartQuantity']);
    Route::get('/cart',[ShopController::class, 'viewCart']);

    Route::get('/cart/isCheckout',[ShopController::class, 'checkedOut']);

    Route::get('/my-deliveries', [DeliveryController::class, 'indexUser']);
    Route::get('/deliveries', [DeliveryController::class, 'index']);
    Route::patch('/deliveries/{deliveryId}/status', [DeliveryController::class, 'updateStatus']);

    Route::get('/showAllUser', [AccountController::class, 'index']);
    Route::get('/showUser/{id}', [AccountController::class, 'show']);

    Route::get('/admin-leadership', [AdminLeadershipController::class, 'adminImgIndex']);

    // CREATE leadership
    Route::post('/admin-leadership', [AdminLeadershipController::class, 'adminImgStore']);

    // UPDATE leadership by ID
    Route::post('/admin-leadership/{id}', [AdminLeadershipController::class, 'adminImgUpdate']);
    // You can also use PUT/PATCH if you prefer RESTful convention
    // Route::put('/admin-leadership/{id}', [AdminLeadershipController::class, 'adminImgUpdate']);

    // DELETE leadership by ID
    Route::delete('/admin-leadership/{id}', [AdminLeadershipController::class, 'adminImgDelete']);

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
    Route::post('/reviews/{review}/reply', [ReviewController::class, 'reply']);
    Route::delete('/reviews/{review}/reply', [ReviewController::class, 'deleteReply']);
    //checkout
    Route::get('/checkout', [CheckoutController::class, 'index']);
    // Cart
    // Route::get('/cart', [CartController::class, 'index']);
    // Route::post('/cart', [CartController::class, 'store']);
    // Route::put('/cart/{cart}', [CartController::class, 'update']);
    // Route::patch('/cart/{cart}', [CartController::class, 'update']);
    // Route::delete('/cart/{cart}', [CartController::class, 'destroy']);
    // Route::delete('/cart/product/{product}', [CartController::class, 'destroyByProduct']);
    // Route::post('/cart/clear', [CartController::class, 'clear']);
     // Backup Recovery
    Route::post('/admin/backups/run',              [AdminBackupController::class, 'adminRunBackup']);
    Route::post('/admin/backups/restore',          [AdminBackupController::class, 'adminUploadRestore']);
    Route::get('/admin/backups',                   [AdminBackupController::class, 'adminHistoryBackup']);
    Route::get('/admin/backups/{id}/download',     [AdminBackupController::class, 'adminDownloadBackup']);
    Route::delete('/admin/backups/{id}',           [AdminBackupController::class, 'adminDeleteBackup']);

    // Checkout
    Route::post('/checkout', [CheckoutController::class, 'store']);

    // Addresses
    Route::get('/addresses', [UserAddressController::class, 'index']);
    Route::post('/addresses', [UserAddressController::class, 'store']);
    Route::get('/addresses/{id}', [UserAddressController::class, 'show']);
    Route::put('/addresses/{id}', [UserAddressController::class, 'update']);
    Route::delete('/addresses/{id}', [UserAddressController::class, 'destroy']);

    Route::get('/dashboard', [Dashboard::class, 'allDashboard']);

    // Chat

    // Activity Log
    Route::prefix('admin')->group(function(){
    Route::get('activity-logs',                  [ActivityLogController::class, 'logFetch']);
    Route::post('activity-logs',                 [ActivityLogController::class, 'storeLogs']);
    Route::get('activity-logs/{activityLog}',    [ActivityLogController::class, 'showLogs']);
    Route::put('activity-logs/{activityLog}',    [ActivityLogController::class, 'updateLogs']);
    Route::delete('activity-logs',               [ActivityLogController::class, 'delallLogs']);
    Route::delete('activity-logs/{activityLog}', [ActivityLogController::class, 'destroyLogs']);
    });

});

// Chat routes (public)
Route::get('/chat/messages', [\App\Http\Controllers\ChatController::class, 'index']);
Route::post('/chat/messages', [\App\Http\Controllers\ChatController::class, 'store']);
Route::get('/chat/rooms', [\App\Http\Controllers\ChatController::class, 'rooms']);
Route::get('/chat/rooms/summary', [\App\Http\Controllers\ChatController::class, 'roomsSummary']);

Route::middleware([EnsureTokenIsValid::class])->group(function () {
    Route::get('/admin/contacts',              [ContactController::class, 'index']);
    Route::get('/admin/contacts/{id}',         [ContactController::class, 'show']);
    Route::patch('/admin/contacts/{id}/status',[ContactController::class, 'updateStatus']);
    Route::delete('/admin/contacts/{id}',      [ContactController::class, 'destroy']);
    Route::post('/admin/contacts/{id}/reply', [ContactController::class, 'reply']);


    Route::put('/accounts/{id}', [AccountController::class, 'update']);


});
