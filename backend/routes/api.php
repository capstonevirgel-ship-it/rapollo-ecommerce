<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductPurchaseController;
use App\Http\Controllers\TicketPurchaseController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShippingPriceController;
use App\Http\Controllers\TaxPriceController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PsgcController;
use App\Http\Controllers\ShippingResolverController;
use App\Http\Controllers\EventCommentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// ----------------------
// Authentication
// ----------------------
Route::post('login', [AuthenticatedSessionController::class, 'store']);
Route::post('register', [RegisteredUserController::class, 'store']);
Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth:sanctum');
Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('guest');
Route::post('reset-password', [NewPasswordController::class, 'store'])->middleware('guest');

// ----------------------
// Public
// ----------------------
Route::get('categories', [CategoryController::class, 'index']);
Route::get('categories/by-id/{id}', [CategoryController::class, 'showById']);
Route::get('categories/{category}', [CategoryController::class, 'show']);

Route::get('subcategories', [SubcategoryController::class, 'index']);
Route::get('subcategories/by-id/{id}', [SubcategoryController::class, 'showById']);
Route::get('subcategories/{subcategory}', [SubcategoryController::class, 'show']);

Route::get('brands', [BrandController::class, 'index']);
Route::get('brands/{brand}', [BrandController::class, 'show']);

Route::get('sizes', [SizeController::class, 'index']);
Route::get('sizes/{size}', [SizeController::class, 'show']);

// Settings (public read access)
Route::get('settings', [SettingsController::class, 'index']);
Route::get('settings/group/{group}', [SettingsController::class, 'getByGroup']);
Route::get('settings/{key}', [SettingsController::class, 'show']);

Route::get('products', [ProductController::class, 'index']);
Route::get('products/homepage/featured', [ProductController::class, 'getFeaturedProducts']);
Route::get('products/homepage/trending', [ProductController::class, 'getTrendingProducts']);
Route::get('products/homepage/new-arrivals', [ProductController::class, 'getNewArrivals']);
Route::get('products/{slug}/related', [ProductController::class, 'getRelatedProducts']);
Route::get('products/{slug}', [ProductController::class, 'show']);

Route::get('events', [EventController::class, 'index']);
Route::get('events/{id}', [EventController::class, 'show']);

// Event Comments (public read access)
Route::get('events/{eventId}/comments', [EventCommentController::class, 'index']);

// Public Shipping Prices (for checkout)
Route::get('shipping-prices/active', [ShippingPriceController::class, 'getActivePrices']);

// Public Ratings (view only)
Route::get('ratings', [RatingController::class, 'index']);
Route::get('ratings/statistics', [RatingController::class, 'statistics']);

// PSGC proxy (public)
Route::get('psgc/regions', [PsgcController::class, 'regions']);
Route::get('psgc/provinces', [PsgcController::class, 'provinces']);
Route::get('psgc/cities-municipalities', [PsgcController::class, 'citiesMunicipalities']);
Route::get('psgc/barangays', [PsgcController::class, 'barangays']);

// Shipping region resolver (public)
Route::get('shipping/resolve-region', [ShippingResolverController::class, 'resolve']);

// Webhooks (public endpoints for external services)
Route::post('webhooks', [WebhookController::class, 'handle']);
Route::get('webhooks/test', [WebhookController::class, 'test']);
Route::post('webhooks/test', [WebhookController::class, 'test']);
Route::post('webhooks/update-payment-status', [WebhookController::class, 'updatePaymentStatus']);

// Contact Form (public)
Route::post('contact', [ContactController::class, 'submit']);


// ----------------------
// Protected
// ----------------------
Route::middleware(['auth:sanctum'])->group(function () {
    // Categories
    Route::post('categories', [CategoryController::class, 'store']);
    Route::put('categories/{category}', [CategoryController::class, 'update']);
    Route::delete('categories/{category}', [CategoryController::class, 'destroy']);

    // Subcategories
    Route::post('subcategories', [SubcategoryController::class, 'store']);
    Route::put('subcategories/{subcategory}', [SubcategoryController::class, 'update']);
    Route::delete('subcategories/{subcategory}', [SubcategoryController::class, 'destroy']);

    // Products
    Route::post('products', [ProductController::class, 'store']);
    Route::put('products/{slug}', [ProductController::class, 'update']);
    Route::delete('products/{slug}', [ProductController::class, 'destroy']);
    Route::patch('products/bulk-update-labels', [ProductController::class, 'bulkUpdateLabels']);
    Route::patch('products/bulk-update-active-status', [ProductController::class, 'bulkUpdateActiveStatus']);

    // Brands
    Route::post('brands', [BrandController::class, 'store']);
    Route::put('brands/{brand}', [BrandController::class, 'update']);
    Route::delete('brands/{brand}', [BrandController::class, 'destroy']);

    // Sizes
    Route::post('sizes', [SizeController::class, 'store']);
    Route::put('sizes/{size}', [SizeController::class, 'update']);
    Route::delete('sizes/{size}', [SizeController::class, 'destroy']);

    // Cart
    Route::get('cart', [CartController::class, 'index']);
    Route::post('cart', [CartController::class, 'store']);
    Route::put('cart/{cart}', [CartController::class, 'update']);
    Route::delete('cart/{cart}', [CartController::class, 'destroy']);

    // Events 
    Route::post('events', [EventController::class, 'store']);
    Route::put('events/{id}', [EventController::class, 'update']);
    Route::delete('events/{id}', [EventController::class, 'destroy']);

    // Event Comments (protected - authenticated users only)
    Route::post('events/{eventId}/comments', [EventCommentController::class, 'store']);
    Route::put('events/{eventId}/comments/{commentId}', [EventCommentController::class, 'update']);
    Route::delete('events/{eventId}/comments/{commentId}', [EventCommentController::class, 'destroy']);

    // Tickets
    Route::get('tickets', [TicketController::class, 'index']);
    Route::post('tickets', [TicketController::class, 'store']);
    Route::post('tickets/payment-intent', [TicketController::class, 'createPaymentIntent']);
    Route::post('tickets/confirm-payment', [TicketController::class, 'confirmPayment']);
    Route::get('tickets/admin/all', [TicketController::class, 'adminIndex']);
    Route::get('tickets/event/{eventId}', [TicketController::class, 'eventTickets']);
    Route::get('tickets/statistics', [TicketController::class, 'statistics']);
    Route::get('tickets/{id}', [TicketController::class, 'show']);
    Route::put('tickets/{id}/cancel', [TicketController::class, 'cancel']);
    Route::put('tickets/{id}/status', [TicketController::class, 'updateStatus']);

    // Product Purchases
    Route::get('product-purchases', [ProductPurchaseController::class, 'index']);
    Route::post('product-purchases', [ProductPurchaseController::class, 'store']);
    Route::get('product-purchases/{id}', [ProductPurchaseController::class, 'show']);
    Route::get('product-purchases/admin/all', [ProductPurchaseController::class, 'adminIndex']);
    Route::get('product-purchases/admin/{id}', [ProductPurchaseController::class, 'adminShow']);
    Route::put('product-purchases/{id}/status', [ProductPurchaseController::class, 'updateStatus']);
    Route::put('product-purchases/{id}/cancel', [ProductPurchaseController::class, 'cancel']);
    
    // Orders (product purchases only)
    Route::get('orders/admin', [ProductPurchaseController::class, 'adminOrders']);

    // Ticket Purchases
    Route::get('ticket-purchases', [TicketPurchaseController::class, 'index']);
    Route::get('ticket-purchases/{id}', [TicketPurchaseController::class, 'show']);
    Route::get('ticket-purchases/admin/all', [TicketPurchaseController::class, 'adminIndex']);
    Route::get('ticket-purchases/admin/{id}', [TicketPurchaseController::class, 'adminShow']);

    // Shipping Prices
    Route::get('shipping-prices', [ShippingPriceController::class, 'index']);
    Route::post('shipping-prices', [ShippingPriceController::class, 'store']);
    Route::get('shipping-prices/{id}', [ShippingPriceController::class, 'show']);
    Route::put('shipping-prices/{id}', [ShippingPriceController::class, 'update']);
    Route::delete('shipping-prices/{id}', [ShippingPriceController::class, 'destroy']);
    Route::put('shipping-prices/bulk-update', [ShippingPriceController::class, 'bulkUpdate']);

    // Tax Prices
    Route::get('tax-prices', [TaxPriceController::class, 'index']);
    Route::post('tax-prices', [TaxPriceController::class, 'store']);
    Route::get('tax-prices/{id}', [TaxPriceController::class, 'show']);
    Route::put('tax-prices/{id}', [TaxPriceController::class, 'update']);
    Route::delete('tax-prices/{id}', [TaxPriceController::class, 'destroy']);
    Route::get('tax-prices/active', [TaxPriceController::class, 'getActiveRate']);

    // Payments
    Route::post('payments/create', [PaymentController::class, 'createPayment']);
    Route::post('payments/paymongo/intent', [PaymentController::class, 'createPaymentIntent']);
    Route::post('payments/paymongo/confirm', [PaymentController::class, 'confirmPayment']);
    Route::post('payments/paymongo/refund', [PaymentController::class, 'createRefund']);

    // Protected Ratings (create, update, delete, user-specific)
    Route::get('ratings/user', [RatingController::class, 'show']);
    Route::post('ratings', [RatingController::class, 'store']);
    Route::delete('ratings', [RatingController::class, 'destroy']);
    Route::get('ratings/reviewable-products', [RatingController::class, 'reviewableProducts']);
    Route::get('ratings/reviewed-products', [RatingController::class, 'reviewedProducts']);

    // Dashboard (protected)
    Route::get('dashboard/statistics', [DashboardController::class, 'statistics']);
    Route::get('dashboard/revenue-chart', [DashboardController::class, 'revenueChart']);
    Route::get('dashboard/ticket-sales-chart', [DashboardController::class, 'ticketSalesChart']);
    Route::get('dashboard/order-status-chart', [DashboardController::class, 'orderStatusChart']);
    Route::get('dashboard/category-chart', [DashboardController::class, 'categoryChart']);
    Route::get('dashboard/top-products', [DashboardController::class, 'topProducts']);
    Route::get('dashboard/recent-orders', [DashboardController::class, 'recentOrders']);

    // Upload (admin only)
    Route::post('upload', [UploadController::class, 'uploadImage']);
    Route::post('upload/multiple', [UploadController::class, 'uploadImages']);
    Route::post('upload/event-content', [UploadController::class, 'uploadEventContentImage']);

    // Settings (admin only)
    Route::post('settings', [SettingsController::class, 'update']);
    Route::put('settings/{key}', [SettingsController::class, 'updateSingle']);
    Route::post('settings/upload-logo', [SettingsController::class, 'uploadLogo']);
    Route::delete('settings/delete-logo', [SettingsController::class, 'deleteLogo']);
    Route::post('settings/upload-team-member-image', [SettingsController::class, 'uploadTeamMemberImage']);
    Route::delete('settings/delete-team-member-image', [SettingsController::class, 'deleteTeamMemberImage']);

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::put('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('notifications/{id}', [NotificationController::class, 'delete']);
    Route::delete('notifications', [NotificationController::class, 'clearAll']);

    // Profile
    Route::get('profile', [ProfileController::class, 'show']);
    Route::put('profile', [ProfileController::class, 'update']);
    Route::post('profile/avatar', [ProfileController::class, 'uploadAvatar']);

    // Admin User Management (admin only)
    Route::prefix('admin/users')->group(function () {
        Route::get('', [AdminUserController::class, 'index']);
        Route::get('{id}', [AdminUserController::class, 'show']);
        Route::put('{id}/suspend', [AdminUserController::class, 'suspend']);
        Route::put('{id}/unsuspend', [AdminUserController::class, 'unsuspend']);
        Route::get('{id}/transactions', [AdminUserController::class, 'transactions']);
    });
});

