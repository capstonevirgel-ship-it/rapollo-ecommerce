<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// ----------------------
// Authentication
// ----------------------
Route::post('login', [AuthenticatedSessionController::class, 'store']);
Route::post('register', [RegisteredUserController::class, 'store']);
Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth:sanctum');

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

Route::get('products', [ProductController::class, 'index']);
Route::get('products/{slug}', [ProductController::class, 'show']);

Route::get('events', [EventController::class, 'index']);
Route::get('events/{id}', [EventController::class, 'show']);

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

    // Brands
    Route::post('brands', [BrandController::class, 'store']);
    Route::put('brands/{brand}', [BrandController::class, 'update']);
    Route::delete('brands/{brand}', [BrandController::class, 'destroy']);

    // Cart
    Route::get('cart', [CartController::class, 'index']);
    Route::post('cart', [CartController::class, 'store']);
    Route::put('cart/{cart}', [CartController::class, 'update']);
    Route::delete('cart/{cart}', [CartController::class, 'destroy']);

    // Events 
    Route::post('events', [EventController::class, 'store']);
    Route::put('events/{id}', [EventController::class, 'update']);
    Route::delete('events/{id}', [EventController::class, 'destroy']);

    // Tickets
    Route::get('tickets', [TicketController::class, 'index']);
    Route::post('tickets', [TicketController::class, 'store']);
    Route::get('tickets/admin/all', [TicketController::class, 'adminIndex']);
    Route::get('tickets/event/{eventId}', [TicketController::class, 'eventTickets']);
    Route::get('tickets/statistics', [TicketController::class, 'statistics']);
    Route::get('tickets/{id}', [TicketController::class, 'show']);
    Route::put('tickets/{id}/cancel', [TicketController::class, 'cancel']);
    Route::put('tickets/{id}/status', [TicketController::class, 'updateStatus']);

    // Purchases
    Route::get('purchases', [PurchaseController::class, 'index']);
    Route::post('purchases', [PurchaseController::class, 'store']);
    Route::get('purchases/{id}', [PurchaseController::class, 'show']);

    // Payments
    Route::post('payments/create', [PaymentController::class, 'createPayment']);

    // Ratings
    Route::get('ratings', [RatingController::class, 'index']);
    Route::get('ratings/user', [RatingController::class, 'show']);
    Route::post('ratings', [RatingController::class, 'store']);
    Route::delete('ratings', [RatingController::class, 'destroy']);
    Route::get('ratings/reviewable-products', [RatingController::class, 'reviewableProducts']);
    Route::get('ratings/statistics', [RatingController::class, 'statistics']);

    // Dashboard (protected)
    Route::get('dashboard/statistics', [DashboardController::class, 'statistics']);
    Route::get('dashboard/revenue-chart', [DashboardController::class, 'revenueChart']);
    Route::get('dashboard/order-status-chart', [DashboardController::class, 'orderStatusChart']);
    Route::get('dashboard/category-chart', [DashboardController::class, 'categoryChart']);
    Route::get('dashboard/top-products', [DashboardController::class, 'topProducts']);
    Route::get('dashboard/recent-orders', [DashboardController::class, 'recentOrders']);
});

