<?php
//   dd

use App\Http\Controllers\Api\Professional\PortfolioController;
use App\Http\Controllers\Api\Professional\ProfessionalProfileController;
use App\Http\Controllers\Api\ResetPasswordController;
use App\Http\Controllers\Api\Seller\BusinessPayoutController;
use App\Http\Controllers\Api\Seller\DashboardController;
use App\Http\Controllers\Api\Seller\OrderController;
use App\Http\Controllers\Api\Seller\PhysicalBookController;
use App\Http\Controllers\Api\Seller\StripeOnboardingController;
use App\Http\Controllers\Api\User\Auth\AuthenticationController;
use App\Http\Controllers\Api\User\Auth\SocialLoginController;
use App\Http\Controllers\Api\User\Auth\UserProfileController;
use App\Http\Controllers\Api\User\BookCompletionController;
use App\Http\Controllers\Api\User\BookReviewController;
use App\Http\Controllers\Api\User\ChatSystemController;
use App\Http\Controllers\Api\User\PhysicalOrderController;
use App\Http\Controllers\Api\User\SubscriptionController;
use App\Http\Controllers\Api\User\UserCategoryController;
use App\Http\Controllers\Api\User\WishlistController;
use App\Http\Controllers\Api\Website\HomeController;
use App\Http\Controllers\Api\Website\UserManageController;
use App\Http\Controllers\Web\Backend\Settings\DynamicPageController;
use App\Http\Controllers\Web\Backend\SplashController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('splash', [SplashController::class, 'Splash']);
Route::get('specialty/list', [HomeController::class, 'specialty_list']);
Route::get('brand/list', [HomeController::class, 'brand_list']);

Route::get('privacy-policy', [DynamicPageController::class, 'privacyPolicy']);
Route::get('term-conditions', [DynamicPageController::class, 'agreement']);

/*
|--------------------------------------------------------------------------
| Guest Routes (No Auth Required)
|--------------------------------------------------------------------------
*/

Broadcast::routes([
    'middleware' => ['auth:api'], // or 'auth:jwt' depending on guard
]);

Route::group(['middleware' => 'guest:api'], function () {

    // Authentication
    Route::post('/login', [AuthenticationController::class, 'login']);
    Route::post('/register', [AuthenticationController::class, 'register']);
    Route::post('/register-otp-verify', [AuthenticationController::class, 'RegistrationVerifyOtp']);

    // Password Reset
    Route::post('forgot-password', [ResetPasswordController::class, 'forgotPassword']);
    Route::post('/verify-otp', [ResetPasswordController::class, 'VerifyOTP']);
    Route::post('/reset-password', [ResetPasswordController::class, 'ResetPassword']);

    // Social Login
    Route::post('social/signin/{provider}', [SocialLoginController::class, 'socialSignin']);

});

//  user  manage
Route::middleware('auth:api')->group(function () {

    Route::post('/logout', [AuthenticationController::class, 'logout']);
    Route::post('/user/category/store', [UserCategoryController::class, 'store']);

    Route::get('/user/details', [UserManageController::class, 'user_info']);
    Route::post('/user/details/update', [UserManageController::class, 'user_info_update']);

    Route::post('/user/avatar/update', [UserManageController::class, 'user_avatar_update']);

    // reset password
    Route::post('/user/password/reset', [UserManageController::class, 'reset_password']);

});

// logout
// Route::post('/logout', [AuthenticationController::class, 'logout']);
Route::delete('/delete-profile', [UserProfileController::class, 'deleteProfile']);

/*
|--------------------------------------------------------------------------
| Authenticated Routes (Prefix: auth)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:api')->prefix('auth')->group(function () {

    Route::post('/update/role', [AuthenticationController::class, 'updateRole']);

});

// professional api manage

Route::middleware(['auth:professional', 'role:professional'])->prefix('auth-professional')->group(function () {

    // profile create
    Route::post('/setup/basic/information', [ProfessionalProfileController::class, 'setup_basic']);
    Route::post('/setup/preferences/information', [ProfessionalProfileController::class, 'preferences_info']);
    Route::post('/setup/working/hours', [ProfessionalProfileController::class, 'working_hours']);
    Route::post('/setup/brands', [ProfessionalProfileController::class, 'setup_brand']);
    Route::post('/setup/service/information', [ProfessionalProfileController::class, 'services']);

    // information
    Route::get('about/me', [ProfessionalProfileController::class, 'about_me']);

    // portfolio
    Route::get('/portfolio/list', [PortfolioController::class, 'list']);
    Route::post('/portfolio/update', [PortfolioController::class, 'update']);

});

Route::get('/subscription/plan', [SubscriptionController::class, 'getPlans']);
Route::get('/subscription/plan/{id}', [SubscriptionController::class, 'getPlanDetails']);

Route::middleware('auth:api')->prefix('auth')->group(function () {

    Route::post('/subscription/setup-intent', [SubscriptionController::class, 'createSetupIntent']);
    Route::post('/subscription/create', [SubscriptionController::class, 'createSubscription']);
    Route::get('/subscription/plan/{id}', [SubscriptionController::class, 'getPlanDetails']);

    Route::post('/subscription/update', [SubscriptionController::class, 'updateSubscription']);
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancelSubscription']);

    Route::post('/subscription/resume', [SubscriptionController::class, 'resumeSubscription']);

    Route::get('/subscription/status', [SubscriptionController::class, 'subscriptionStatus']);

});

Route::middleware('auth:api')->prefix('auth')->group(function () {

    Route::get('/wishlist/list', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/store', [WishlistController::class, 'toggle'])->name('wishlist.toggle');

    Route::get('similar/book/list', [HomeController::class, 'similar_book_list']);

});

Route::middleware('auth:api')->prefix('auth')->group(function () {

    Route::get('/book/completion/list', [BookCompletionController::class, 'index'])->name('book.completion.index');
    Route::post('/book/completion/store', [BookCompletionController::class, 'toggle'])->name('book.completion.toggle');

});

// book review routes
Route::middleware('auth:api')->prefix('auth')->group(function () {

    Route::post('/book/review/store', [BookReviewController::class, 'store'])->name('book.review.store');

    // stripe onboarding
    Route::post('account/user/onboarding', [StripeOnboardingController::class, 'onboard']);
    Route::get('account/connect/check', [StripeOnboardingController::class, 'connect_check']);

    // stripe payout
    Route::get('account/balance', [BusinessPayoutController::class, 'getBalance']);
    Route::post('account/payout/withdraw', [BusinessPayoutController::class, 'withdraw']);
    Route::get('account/transactions', [BusinessPayoutController::class, 'getAllTransactionHistory']);

});

Route::middleware('auth:api')->prefix('auth/seller')->group(function () {

    Route::get('/book/list', [PhysicalBookController::class, 'index'])->name('seller.book.index');
    Route::post('/book/store', [PhysicalBookController::class, 'store'])->name('seller.book.store');

    // single book image delete
    Route::delete('/book/image/delete/{id}', [PhysicalBookController::class, 'deleteImage'])->name('seller.book.image.delete');

    // book edit
    Route::get('/book/edit/{id}', [PhysicalBookController::class, 'edit'])->name('seller.book.edit');
    Route::post('/book/update', [PhysicalBookController::class, 'update'])->name('seller.book.update');
    Route::delete('/book/delete/{id}', [PhysicalBookController::class, 'destroy'])->name('seller.book.delete');

    Route::get('/review/list', [DashboardController::class, 'review_list'])->name('seller.review.list');

});

// as a seller order manage
Route::middleware('auth:api')->prefix('auth/seller')->group(function () {

    Route::get('/order/list', [OrderController::class, 'order_list'])->name('seller.order.list');
    Route::get('/order/details/{id}', [OrderController::class, 'order_details'])->name('seller.order.details');

    // shipping info update
    Route::post('/order/shipping/update', [OrderController::class, 'update_shipping_info'])->name('seller.order.shipping.update');

});

Route::middleware('auth:api')->prefix('auth/buyer')->group(function () {

    Route::get('/order/list', [PhysicalOrderController::class, 'buyer_order_list'])->name('buyer.order.list');
    Route::get('/order/details/{id}', [PhysicalOrderController::class, 'buyer_order_details'])->name('buyer.order.details');

    // confirm delivery
    Route::post('/order/delivery/confirm', [PhysicalOrderController::class, 'confirm_delivery'])->name('buyer.order.confirm.delivery');

});

Route::middleware(['auth:api'])->prefix('auth/chat')->group(function () {

    Route::get('list', [ChatSystemController::class, 'list']);
    Route::post('send/{receiver_id}', [ChatSystemController::class, 'send']);
    Route::get('order/{order_id}/conversation/{receiver_id}', [ChatSystemController::class, 'conversation']);
    Route::get('room/{receiver_id}', [ChatSystemController::class, 'room']);
    Route::get('search', [ChatSystemController::class, 'search']);
    Route::get('seen/all/{receiver_id}', [ChatSystemController::class, 'seenAll']);
    Route::get('seen/single/{chat_id}', [ChatSystemController::class, 'seenSingle']);

});

// payment manage
Route::middleware('auth:api')->prefix('auth')->group(function () {

    // create checkout session
    Route::post('/create/checkout-session', [PhysicalOrderController::class, 'createCheckoutSession']);

});
