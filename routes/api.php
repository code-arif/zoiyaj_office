<?php

use App\Http\Controllers\Api\BarcodeController;
use App\Http\Controllers\Api\Client\HomeController as ClientHomeController;
use App\Http\Controllers\Api\Client\PhotoScanController;
use App\Http\Controllers\Api\Client\ProfileSetupController;
use App\Http\Controllers\Api\FollowerController;
use App\Http\Controllers\Api\Professional\PortfolioController;
use App\Http\Controllers\Api\Professional\ProfessionalListController;
use App\Http\Controllers\Api\Professional\ProfessionalProfileController;
use App\Http\Controllers\Api\ResetPasswordController;
use App\Http\Controllers\Api\User\Auth\AuthenticationController;
use App\Http\Controllers\Api\User\Auth\SocialLoginController;
use App\Http\Controllers\Api\User\Auth\UserProfileController;
use App\Http\Controllers\Api\User\ChatSystemController;
use App\Http\Controllers\Api\User\PhysicalOrderController;
use App\Http\Controllers\Api\User\SubscriptionController;
use App\Http\Controllers\Api\User\UserCategoryController;
use App\Http\Controllers\Api\User\UserPreferenceController;
use App\Http\Controllers\Api\User\WishlistController;
use App\Http\Controllers\Api\Website\HomeController;
use App\Http\Controllers\Api\Website\UserManageController;
use App\Http\Controllers\Api\Shop\ShopSearchController;
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
Route::get('category/list', [HomeController::class, 'category_list']);
Route::get('brand/list', [HomeController::class, 'brand_list']);
Route::get('brand/list', [HomeController::class, 'brand_list']);
Route::get('preference/list', [HomeController::class, 'preference_list']);

Route::get('redeem/list', [HomeController::class, 'redeem_list']);

Route::get('/faq', [DynamicPageController::class, 'faq']);
Route::get('privacy-policy', [DynamicPageController::class, 'privacyPolicy']);
Route::get('term-conditions', [DynamicPageController::class, 'agreement']);

/*
|--------------------------------------------------------------------------
| Guest Routes (No Auth Required)
|--------------------------------------------------------------------------
*/

//*****Rayhan is create in CRUD============================================================ */

//USER PREFERENCES CRUD
Route::get('/user/preferences', [UserPreferenceController::class, 'index']);      // List by user_id
Route::post('/user/preferences', [UserPreferenceController::class, 'store']);     // Insert
Route::put('/user/preferences', [UserPreferenceController::class, 'update']);     // Update by user_id
Route::delete('/user/preferences', [UserPreferenceController::class, 'destroy']); // Delete by user_id

//*****Rayhan is create in CRUD============================================================ */

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

Route::prefix('barcode')->name('barcode')->group(function () {
    Route::get('/', [BarcodeController::class, 'getProduct']);
});

// professional api manage

Route::middleware(['auth:professional', 'role:professional'])->prefix('auth-professional')->group(function () {

    // profile create
    Route::post('/setup/basic/information', [ProfessionalProfileController::class, 'setup_basic']);
    Route::post('/setup/preferences/information', [ProfessionalProfileController::class, 'preferences_info']);
    Route::post('/setup/working/hours', [ProfessionalProfileController::class, 'working_hours']);
    Route::post('/setup/brands', [ProfessionalProfileController::class, 'setup_brand']);
    // Route::post('/setup/categories', [ProfessionalProfileController::class, 'setup_category']);
    Route::post('/setup/service/information', [ProfessionalProfileController::class, 'services']);

    // information
    Route::get('about/me', [ProfessionalProfileController::class, 'about_me']);
    Route::get('analytics', [ProfessionalProfileController::class, 'analytics']);
    Route::get('earning/analytics', [ProfessionalProfileController::class, 'earning_analytics']);

    // portfolio
    Route::get('/portfolio/list', [PortfolioController::class, 'list']);
    Route::post('/portfolio/update', [PortfolioController::class, 'update']);
});

Route::get('/subscription/plan', [SubscriptionController::class, 'getPlans']);
Route::get('/subscription/plan/{id}', [SubscriptionController::class, 'getPlanDetails']);

Route::get('/categories/salon-list/{category_id}', [ClientHomeController::class, 'salon_category_list']);

// popular categories
Route::get('/popular/categories/list', [ClientHomeController::class, 'popular_categories']);
Route::get('/nearby/salon/list', [ClientHomeController::class, 'nearby_salon_list']);
Route::get('/top-stylist/salon/list', [ClientHomeController::class, 'top_stylist_salon_list']);

// salon detail
Route::get('/salon/detail/{professional_id}', [ClientHomeController::class, 'salon_detail']);

// Route::middleware('auth:api')->prefix('auth')->group(function () {

//     Route::post('/subscription/setup-intent', [SubscriptionController::class, 'createSetupIntent']);
//     Route::post('/subscription/create', [SubscriptionController::class, 'createSubscription']);
//     Route::get('/subscription/plan/{id}', [SubscriptionController::class, 'getPlanDetails']);

//     Route::post('/subscription/update', [SubscriptionController::class, 'updateSubscription']);
//     Route::post('/subscription/cancel', [SubscriptionController::class, 'cancelSubscription']);

//     Route::post('/subscription/resume', [SubscriptionController::class, 'resumeSubscription']);

//     Route::get('/subscription/status', [SubscriptionController::class, 'subscriptionStatus']);
// });

/*
|-------------------------------
| Chatting route
|-------------------------------
*/
Route::middleware(['auth:api'])->prefix('auth/chat')->group(function () {
    Route::get('list', [ChatSystemController::class, 'list']);                               // List users with search & pagination
    Route::get('conversation/{receiver_id}', [ChatSystemController::class, 'conversation']); // Get conversation messages
    Route::post('send/{receiver_id}', [ChatSystemController::class, 'send']);                // Send message
    Route::get('room/{receiver_id}', [ChatSystemController::class, 'room']);                 // Get or create room
    Route::get('seen/all/{receiver_id}', [ChatSystemController::class, 'seenAll']);          // Mark all messages as read
    Route::get('seen/single/{chat_id}', [ChatSystemController::class, 'seenSingle']);        // Mark single message as read
});

// payment manage
Route::middleware('auth:api')->prefix('auth')->group(function () {

    // create checkout session
    Route::post('/create/checkout-session', [PhysicalOrderController::class, 'createCheckoutSession']);
});

Route::middleware(['auth:client', 'role:client'])->prefix('booking')->group(function () {

    Route::post('/service', [\App\Http\Controllers\Api\BookingController::class, 'bookService']);
    Route::get('/client/bookings', [\App\Http\Controllers\Api\BookingController::class, 'getClientBookings']);
    Route::post('/cancel/client/booking', [\App\Http\Controllers\Api\BookingController::class, 'cancelBooking']);
    Route::post('/complete/client/booking', [\App\Http\Controllers\Api\BookingController::class, 'completeBooking']);

    Route::post('/check-in/client/booking', [\App\Http\Controllers\Api\BookingController::class, 'checkinBooking']);

    Route::get('/client/reviews', [\App\Http\Controllers\Api\BookingController::class, 'getClientReviewBookings']);
    Route::post('/submit/review', [\App\Http\Controllers\Api\BookingController::class, 'submitReview']);
});

Route::middleware('auth:api')->prefix('auth')->group(function () {

    Route::get('/bookmark/list', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/bookmark/store', [WishlistController::class, 'toggle'])->name('wishlist.toggle');

    Route::get('/point/history', [WishlistController::class, 'getPointHistory'])->name('point.history');
});

Route::middleware(['auth:professional', 'role:professional'])->prefix('booking')->group(function () {
    Route::get('/professional', [\App\Http\Controllers\Api\BookingController::class, 'getProfessionalBookings']);
    Route::post('/approve/booking', [\App\Http\Controllers\Api\BookingController::class, 'approveBooking']);
    Route::post('/cancel/pro/booking', [\App\Http\Controllers\Api\BookingController::class, 'cancelBooking']);
    Route::post('/complete/pro/booking', [\App\Http\Controllers\Api\BookingController::class, 'completeBooking']);

    Route::post('/check-in/complete/pro/booking', [\App\Http\Controllers\Api\BookingController::class, 'confirmCheckin']);

    Route::post('/update/status', [\App\Http\Controllers\Api\BookingController::class, 'updateBookingStatus']);
});

Route::middleware(['auth:client', 'role:client'])->prefix('auth-client')->group(function () {
    // profile create
    Route::post('/setup/basic/information', [ProfileSetupController::class, 'setup_basic']);
    Route::post('/setup/preferences/information', [ProfileSetupController::class, 'preferences_info']);
    Route::post('/setup/others/information', [ProfileSetupController::class, 'others_info']);

    // information
    Route::get('about/me', [ProfileSetupController::class, 'about_me']);
});

// photo scanner api

Route::post('/photo/scan', [PhotoScanController::class, 'analyze']);

// manage followers
Route::middleware(['auth:client', 'role:client'])->prefix('auth-client')->group(function () {
    Route::get('/professionals/following', [FollowerController::class, 'followingList']);
    Route::post('/professionals/{professionalId}/follow', [FollowerController::class, 'follow']);
    Route::post('/professionals/{professionalId}/unfollow', [FollowerController::class, 'unfollow']);
    Route::get('/professionals/{professionalId}/followers', [FollowerController::class, 'getFollowers']);
    Route::get('/professionals/{professionalId}/follow-status', [FollowerController::class, 'checkFollowingStatus']);
});

/*
|--------------------------------------------------------------------------
| Shop Search & Filter Routes
|--------------------------------------------------------------------------
*/
Route::prefix('shop')->group(function () {
    // Combined search across brands, products, and deals
    Route::get('/search', [ShopSearchController::class, 'search']);

    // Individual tab searches with filtering
    Route::get('/brands', [ShopSearchController::class, 'searchBrands']);
    Route::get('/products', [ShopSearchController::class, 'searchProducts']);
    Route::get('/deals', [ShopSearchController::class, 'searchDeals']);

    // Filter options
    Route::get('/categories', [ShopSearchController::class, 'getCategories']);
    Route::get('/brand-names', [ShopSearchController::class, 'getBrandNames']);

    // Reviews
    Route::get('/reviews', [ShopSearchController::class, 'getReviews']);
    Route::get('/reviews/professional/{professionalId}', [ShopSearchController::class, 'getProfessionalReviews']);
});

/*
|--------------------------------------------------------------------------
| Profissionl list and details and serarching
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api')->group(function () {
    Route::get('/professionals/list', [ProfessionalListController::class, 'professional_list']);
    Route::post('/professionals/{professionalId}/toggle-hide', [ProfessionalListController::class, 'toggleHideProfessional']);
    Route::post('/professionals/{professionalId}/toggle-favourite', [ProfessionalListController::class, 'toggleFavourite']);
    Route::get('/professionals/favourite/list', [ProfessionalListController::class, 'favouriteList']);
});
