<?php

namespace App\Http\Controllers;

use App\Models\ProductPurchase;
use App\Models\ProductPurchaseItem;
use App\Models\ProductVariant;
use App\Models\Profile;
use App\Models\ShippingPrice;
use App\Support\PhRegionResolver;
use App\Models\Cart;
use App\Models\User;
use App\Services\NotificationService;
use App\Mail\OrderCancelled;
use App\Mail\OrderProcessing;
use App\Mail\OrderShipped;
use App\Mail\OrderDelivered;
use App\Mail\OrderCompleted;
use App\Mail\OrderReceivedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ProductPurchaseController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::user();
        
        // Prevent admins from creating purchases
        if ($user->role === 'admin') {
            return response()->json([
                'message' => 'Administrators cannot proceed to checkout. Please use a customer account to make purchases.'
            ], 403);
        }

        // Check if user is suspended
        if ($user->isSuspended()) {
            return response()->json([
                'message' => 'Your account has been suspended. You cannot proceed with checkout. Please contact support if you believe this is an error.',
                'error' => 'account_suspended',
                'suspension_reason' => $user->suspension_reason,
            ], 403);
        }

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|integer|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::beginTransaction();

            // Ensure profile address is complete before proceeding
            $profile = Profile::firstOrCreate(['user_id' => $user->id]);
            $requiredAddressFields = ['barangay', 'city', 'province', 'zipcode'];
            foreach ($requiredAddressFields as $field) {
                if (empty($profile->{$field})) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Incomplete address. Please complete your profile address before checkout.',
                        'missing_field' => $field,
                    ], 422);
                }
            }

            // Validate stock availability for all items first
            foreach ($validated['items'] as $item) {
                $variant = ProductVariant::find($item['variant_id']);
                
                if (!$variant) {
                    DB::rollback();
                    return response()->json([
                        'error' => 'Product variant not found',
                        'variant_id' => $item['variant_id']
                    ], 404);
                }

                if (!$variant->hasStock($item['quantity'])) {
                    DB::rollback();
                    return response()->json([
                        'error' => 'Insufficient stock for one or more items',
                        'variant_id' => $variant->id,
                        'product_name' => $variant->product->name,
                        'available_stock' => $variant->stock,
                        'requested_quantity' => $item['quantity']
                    ], 400);
                }
            }

            // Calculate subtotal
            $subtotal = collect($validated['items'])->sum(function ($item) {
                return $item['quantity'] * $item['price'];
            });

            // Compute shipping based on profile city/province
            $storeOriginCity = config('app.store_origin_city', 'cebu city');
            $region = PhRegionResolver::resolveRegion($profile->city, $profile->province, $storeOriginCity);
            $shippingAmount = ShippingPrice::getPriceForRegion($region) ?? 0.0;
            
            // Calculate tax from all active tax prices
            $totalTaxRate = \App\Models\TaxPrice::getTotalTaxRate();
            $taxAmount = $subtotal * ($totalTaxRate / 100);
            
            $total = $subtotal + $taxAmount + $shippingAmount;

            // Create product purchase
            $purchase = ProductPurchase::create([
                'user_id' => Auth::id(),
                'total' => $total,
                'status' => 'pending',
                'shipping_address' => [
                    'street' => $profile->street,
                    'barangay' => $profile->barangay,
                    'city' => $profile->city,
                    'province' => $profile->province,
                    'zipcode' => $profile->zipcode,
                    'complete_address' => $profile->complete_address,
                    'region' => $region,
                    'shipping_amount' => $shippingAmount,
                    'tax_amount' => $taxAmount,
                    'tax_rate' => $totalTaxRate,
                ],
            ]);

            // Create product purchase items
            foreach ($validated['items'] as $item) {
                ProductPurchaseItem::create([
                    'product_purchase_id' => $purchase->id,
                    'variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }

            // Note: Stock will be decremented after successful payment in webhook
            // Note: Cart will be cleared after successful payment, not here

            DB::commit();

            // Broadcast count update to all admins via WebSocket
            $pendingOrdersCount = \App\Models\ProductPurchase::where('status', 'pending')->count();
            \App\Services\NotificationService::broadcastCountUpdate('pending_orders', $pendingOrdersCount);

            return response()->json([
                'message' => 'Purchase created successfully',
                'data' => $purchase->load(['items.variant.product', 'payment']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'error' => 'Failed to create purchase',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function index()
    {
        $purchases = ProductPurchase::where('user_id', Auth::id())
            ->with([
                'items.variant.product:id,name,slug,subcategory_id',
                'items.variant.product.subcategory:id,name,slug,category_id',
                'items.variant.product.subcategory.category:id,name,slug',
                'items.variant:id,product_id,size_id,color_id',
                'items.variant.size:id,name',
                'items.variant.color:id,name',
                'items.variant.images:id,variant_id,url',
                'items.variant.product.images:id,product_id,url',
                'payment'
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($purchase) {
                foreach ($purchase->items as $item) {
                    // Attach purchase tax info so frontend can compute totals if needed
                    $item->purchase_tax_rate = optional($purchase->shipping_address)['tax_rate'] ?? 0;
                    $item->purchase_shipping_amount = optional($purchase->shipping_address)['shipping_amount'] ?? 0;
                }
                return $purchase;
            });

        return response()->json([
            'data' => $purchases,
        ]);
    }

    public function show($id)
    {
        $purchase = ProductPurchase::where('user_id', Auth::id())
            ->with([
                'items.variant.product:id,name,slug,subcategory_id',
                'items.variant.product.subcategory:id,name,slug,category_id',
                'items.variant.product.subcategory.category:id,name,slug',
                'items.variant:id,product_id,size_id,color_id',
                'items.variant.size:id,name',
                'items.variant.color:id,name',
                'items.variant.images' => function($query) {
                    $query->select('id', 'variant_id', 'url', 'is_primary')
                        ->orderBy('is_primary', 'desc')
                        ->orderBy('sort_order', 'asc');
                },
                'items.variant.product.images' => function($query) {
                    $query->select('id', 'product_id', 'url', 'is_primary')
                        ->orderBy('is_primary', 'desc')
                        ->orderBy('sort_order', 'asc');
                },
                'payment'
            ])
            ->findOrFail($id);

        return response()->json([
            'data' => $purchase,
        ]);
    }

    /**
     * Display all orders (product purchases) for admin
     */
    public function adminOrders(Request $request)
    {
        try {
            // Check if user is admin
            $user = Auth::user();
            if (!$user || $user->role !== 'admin') {
                return response()->json([
                    'error' => 'Unauthorized. Admin access required.',
                    'user_role' => $user ? $user->role : 'not_authenticated'
                ], 403);
            }

            // Fetch product purchases
            $query = ProductPurchase::with([
                'user:id,user_name,email,role',
                'user.profile:id,user_id,full_name,phone,complete_address',
                'items.variant.product:id,name,slug,subcategory_id',
                'items.variant.product.subcategory:id,name,slug,category_id',
                'items.variant.product.subcategory.category:id,name,slug',
                'items.variant:id,product_id,size_id,color_id',
                'items.variant.size:id,name',
                'items.variant.color:id,name',
                'items.variant.images' => function($query) {
                    $query->select('id', 'variant_id', 'url', 'is_primary')
                        ->orderBy('is_primary', 'desc')
                        ->orderBy('sort_order', 'asc');
                },
                'items.variant.product.images' => function($query) {
                    $query->select('id', 'product_id', 'url', 'is_primary')
                        ->orderBy('is_primary', 'desc')
                        ->orderBy('sort_order', 'asc');
                },
                'payment'
            ]);

            // Filter by status if provided
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Search by customer name or email
            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('user', function($q) use ($search) {
                    $q->where('user_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $orders = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));

            return response()->json($orders);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch orders',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display all product purchases for admin
     */
    public function adminIndex(Request $request)
    {
        try {
            // Check if user is admin
            $user = Auth::user();
            if (!$user || $user->role !== 'admin') {
                return response()->json([
                    'error' => 'Unauthorized. Admin access required.',
                    'user_role' => $user ? $user->role : 'not_authenticated'
                ], 403);
            }

            $query = ProductPurchase::with([
                'user:id,user_name,email,role',
                'user.profile:id,user_id,full_name,phone,complete_address',
                'items.variant.product:id,name,slug,subcategory_id',
                'items.variant.product.subcategory:id,name,slug,category_id',
                'items.variant.product.subcategory.category:id,name,slug',
                'items.variant:id,product_id,size_id,color_id',
                'items.variant.size:id,name',
                'items.variant.color:id,name',
                'items.variant.images' => function($query) {
                    $query->select('id', 'variant_id', 'url', 'is_primary')
                        ->orderBy('is_primary', 'desc')
                        ->orderBy('sort_order', 'asc');
                },
                'items.variant.product.images' => function($query) {
                    $query->select('id', 'product_id', 'url', 'is_primary')
                        ->orderBy('is_primary', 'desc')
                        ->orderBy('sort_order', 'asc');
                },
                'payment'
            ]);

            // Filter by status if provided
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Search by customer name or email
            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('user', function($q) use ($search) {
                    $q->where('user_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $purchases = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));

            return response()->json($purchases);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch purchases',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update purchase status
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            // Check if user is admin
            $user = Auth::user();
            if (!$user || $user->role !== 'admin') {
                return response()->json([
                    'error' => 'Unauthorized. Admin access required.'
                ], 403);
            }

            $request->validate([
                'status' => 'required|string|in:pending,processing,shipped,delivered,completed,cancelled,failed'
            ]);

            $purchase = ProductPurchase::findOrFail($id);
            $oldStatus = $purchase->status;
            $purchase->status = $request->status;
            $purchase->save();

            // Check for auto-suspension if status changed to 'cancelled'
            if ($oldStatus !== 'cancelled' && $request->status === 'cancelled') {
                $purchaseUser = $purchase->user;
                if ($purchaseUser && $purchaseUser->role === 'user') {
                    \App\Services\UserSuspensionService::checkAndSuspendIfNeeded($purchaseUser);
                }
            }

            // Send email and notification based on status change
            if ($oldStatus !== $request->status) {
                $this->sendStatusChangeEmail($purchase, $request->status);
                $this->sendStatusChangeNotification($purchase, $request->status);
            }

            // Broadcast count update to all admins via WebSocket
            $pendingOrdersCount = ProductPurchase::where('status', 'pending')->count();
            \App\Services\NotificationService::broadcastCountUpdate('pending_orders', $pendingOrdersCount);

            return response()->json([
                'message' => 'Purchase status updated successfully',
                'data' => $purchase
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update purchase status',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark order as received (customer action)
     */
    public function markAsReceived($id)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthorized'
                ], 401);
            }

            $purchase = ProductPurchase::findOrFail($id);

            // Check if user owns the purchase
            if ($purchase->user_id !== $user->id) {
                return response()->json([
                    'error' => 'Unauthorized. You can only mark your own orders as received.'
                ], 403);
            }

            // Only allow marking as received if status is 'delivered'
            if ($purchase->status !== 'delivered') {
                return response()->json([
                    'error' => 'Invalid status',
                    'message' => 'You can only mark orders as received when they are in delivered status.'
                ], 400);
            }

            // Update status to completed
            $purchase->status = 'completed';
            $purchase->save();

            // Send email to customer
            $this->sendStatusChangeEmail($purchase, 'completed');

            // Send notification to customer
            $this->sendStatusChangeNotification($purchase, 'completed');

            // Send email and notification to all admins
            $adminUsers = User::where('role', 'admin')->get();
            foreach ($adminUsers as $admin) {
                try {
                    Mail::to($admin->email)->send(new OrderReceivedNotification($purchase, $user));
                } catch (\Exception $e) {
                    Log::error('Failed to send order received notification email to admin', [
                        'admin_id' => $admin->id,
                        'purchase_id' => $purchase->id,
                        'error' => $e->getMessage()
                    ]);
                }

                NotificationService::createOrderNotification(
                    $admin,
                    'Order Received by Customer',
                    'Customer ' . $user->user_name . ' has confirmed receipt of order #' . $purchase->id . '.',
                    [
                        'action_url' => '/admin/orders/' . $purchase->id,
                        'action_text' => 'View Order'
                    ]
                );
            }

            return response()->json([
                'message' => 'Order marked as received successfully',
                'data' => $purchase->load(['items.variant.product', 'payment'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to mark order as received',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send email based on order status
     */
    private function sendStatusChangeEmail(ProductPurchase $purchase, string $status): void
    {
        try {
            $user = $purchase->user;
            if (!$user || !$user->email) {
                return;
            }

            $mailable = null;
            switch ($status) {
                case 'cancelled':
                    $mailable = new OrderCancelled($purchase, $user);
                    break;
                case 'processing':
                    $mailable = new OrderProcessing($purchase, $user);
                    break;
                case 'shipped':
                    $mailable = new OrderShipped($purchase, $user);
                    break;
                case 'delivered':
                    $mailable = new OrderDelivered($purchase, $user);
                    break;
                case 'completed':
                    $mailable = new OrderCompleted($purchase, $user);
                    break;
            }

            if ($mailable) {
                Mail::to($user->email)->send($mailable);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send status change email', [
                'purchase_id' => $purchase->id,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send notification based on order status
     */
    private function sendStatusChangeNotification(ProductPurchase $purchase, string $status): void
    {
        try {
            $user = $purchase->user;
            if (!$user) {
                return;
            }

            $title = '';
            $message = '';
            $actionUrl = '/my-orders/' . $purchase->id;
            $actionText = 'View Order';

            switch ($status) {
                case 'cancelled':
                    $title = 'Order Cancelled';
                    $message = 'Your order #' . $purchase->id . ' has been cancelled.';
                    break;
                case 'processing':
                    $title = 'Order Processing';
                    $message = 'Your order #' . $purchase->id . ' is now being processed.';
                    break;
                case 'shipped':
                    $title = 'Order Shipped';
                    $message = 'Your order #' . $purchase->id . ' has been shipped and is on its way.';
                    break;
                case 'delivered':
                    $title = 'Order Delivered';
                    $message = 'Your order #' . $purchase->id . ' has been delivered. Please confirm receipt.';
                    break;
                case 'completed':
                    $title = 'Order Completed';
                    $message = 'Your order #' . $purchase->id . ' has been marked as completed.';
                    break;
            }

            if ($title && $message) {
                NotificationService::createOrderNotification(
                    $user,
                    $title,
                    $message,
                    [
                        'action_url' => $actionUrl,
                        'action_text' => $actionText
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to send status change notification', [
                'purchase_id' => $purchase->id,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Display purchase details for admin
     */
    public function adminShow($id)
    {
        try {
            // Check if user is admin
            $user = Auth::user();
            if (!$user || $user->role !== 'admin') {
                return response()->json([
                    'error' => 'Unauthorized. Admin access required.'
                ], 403);
            }

            $purchase = ProductPurchase::with([
                'user:id,user_name,email,role',
                'user.profile:id,user_id,full_name,phone,complete_address',
                'items.variant.product:id,name,slug,subcategory_id',
                'items.variant.product.subcategory:id,name,slug,category_id',
                'items.variant.product.subcategory.category:id,name,slug',
                'items.variant:id,product_id,size_id,color_id',
                'items.variant.size:id,name',
                'items.variant.color:id,name',
                'items.variant.images' => function($query) {
                    $query->select('id', 'variant_id', 'url', 'is_primary')
                        ->orderBy('is_primary', 'desc')
                        ->orderBy('sort_order', 'asc');
                },
                'items.variant.product.images' => function($query) {
                    $query->select('id', 'product_id', 'url', 'is_primary')
                        ->orderBy('is_primary', 'desc')
                        ->orderBy('sort_order', 'asc');
                },
                'payment'
            ])->findOrFail($id);

            return response()->json([
                'data' => $purchase,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch purchase details',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel a product purchase
     */
    public function cancel($id)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthorized'
                ], 401);
            }

            $purchase = ProductPurchase::findOrFail($id);

            // Check if user owns the purchase or is admin
            if ($purchase->user_id !== $user->id && $user->role !== 'admin') {
                return response()->json([
                    'error' => 'Unauthorized. You can only cancel your own orders.'
                ], 403);
            }

            // Define non-cancellable statuses
            $nonCancellableStatuses = ['shipped', 'delivered', 'completed'];
            
            // Check if current status is non-cancellable
            if (in_array($purchase->status, $nonCancellableStatuses)) {
                return response()->json([
                    'error' => 'Cannot cancel order',
                    'message' => 'Orders with status ' . $purchase->status . ' cannot be cancelled.'
                ], 400);
            }

            // Role-based validation
            if ($user->role === 'admin') {
                // Admin can cancel if status is 'pending' or 'processing'
                if (!in_array($purchase->status, ['pending', 'processing'])) {
                    return response()->json([
                        'error' => 'Cannot cancel order',
                        'message' => 'Admin can only cancel orders with status pending or processing.'
                    ], 400);
                }
            } else {
                // Customer can only cancel if status is 'pending'
                if ($purchase->status !== 'pending') {
                    return response()->json([
                        'error' => 'Cannot cancel order',
                        'message' => 'You can only cancel orders with pending status.'
                    ], 400);
                }
            }

            // Update status to cancelled
            $oldStatus = $purchase->status;
            $purchase->status = 'cancelled';
            $purchase->save();

            // Check for auto-suspension if status changed to 'cancelled'
            if ($oldStatus !== 'cancelled') {
                $purchaseUser = $purchase->user;
                if ($purchaseUser && $purchaseUser->role === 'user') {
                    \App\Services\UserSuspensionService::checkAndSuspendIfNeeded($purchaseUser);
                }
            }

            // Send email and notification for cancellation
            if ($oldStatus !== 'cancelled') {
                $this->sendStatusChangeEmail($purchase, 'cancelled');
                $this->sendStatusChangeNotification($purchase, 'cancelled');
            }

            return response()->json([
                'message' => 'Order cancelled successfully',
                'data' => $purchase->load(['items.variant.product', 'payment'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to cancel order',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

