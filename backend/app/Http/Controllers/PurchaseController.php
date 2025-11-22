<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\ProductVariant;
use App\Models\Profile;
use App\Models\ShippingPrice;
use App\Support\PhRegionResolver;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function store(Request $request)
    {
        // Prevent admins from creating purchases
        if (Auth::user()->role === 'admin') {
            return response()->json([
                'message' => 'Administrators cannot proceed to checkout. Please use a customer account to make purchases.'
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
            $user = Auth::user();
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

            // Create purchase
            $purchase = Purchase::create([
                'user_id' => Auth::id(),
                'total' => $total,
                'status' => 'pending',
                'type' => 'product', // Explicitly set type
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

            // Create purchase items
            foreach ($validated['items'] as $item) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }

            // Note: Stock will be decremented after successful payment in webhook
            // Note: Cart will be cleared after successful payment, not here

            DB::commit();

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
        $purchases = Purchase::where('user_id', Auth::id())
            ->where('type', 'product') // Only show product purchases, exclude tickets
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
        $purchase = Purchase::where('user_id', Auth::id())
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

            // Only fetch product purchases (including NULL for backward compatibility)
            $query = Purchase::with([
                'user:id,user_name,email,role',
                'user.profile:id,user_id,full_name,phone,complete_address',
                'items.variant.product:id,name,slug,subcategory_id',
                'items.variant.product.subcategory:id,name,slug,category_id',
                'items.variant.product.subcategory.category:id,name,slug',
                'items.variant:id,product_id,size_id,color_id',
                'items.variant.size:id,name',
                'items.variant.color:id,name',
                'items.variant.images:id,variant_id,url',
                'items.variant.product.images:id,product_id,url',
                'payment'
            ])->where(function($q) {
                $q->where('type', 'product')
                  ->orWhereNull('type'); // Handle NULL as product for backward compatibility
            });

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
     * Display all purchases for admin
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

            $query = Purchase::with([
                'user:id,user_name,email,role',
                'user.profile:id,user_id,full_name,phone,complete_address',
                'items.variant.product:id,name,slug,subcategory_id',
                'items.variant.product.subcategory:id,name,slug,category_id',
                'items.variant.product.subcategory.category:id,name,slug',
                'items.variant:id,product_id,size_id,color_id',
                'items.variant.size:id,name',
                'items.variant.color:id,name',
                'items.variant.images:id,variant_id,url',
                'items.variant.product.images:id,product_id,url',
                'payment',
                'event:id,title'
            ]);

            // Filter by status if provided
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Filter by type if provided
            if ($request->has('type') && $request->type !== 'all') {
                // Handle NULL type values as 'product' for backward compatibility
                // Existing purchases created before type field was added may have NULL
                if ($request->type === 'product') {
                    $query->where(function($q) {
                        $q->where('type', 'product')
                          ->orWhereNull('type');
                    });
                } else {
                    $query->where('type', $request->type);
                }
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
                'status' => 'required|string|in:pending,shipped,delivered'
            ]);

            $purchase = Purchase::findOrFail($id);
            $purchase->status = $request->status;
            $purchase->save();

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

            $purchase = Purchase::with([
                'user:id,user_name,email,role',
                'user.profile:id,user_id,full_name,phone,complete_address',
                'items.variant.product:id,name,slug,subcategory_id',
                'items.variant.product.subcategory:id,name,slug,category_id',
                'items.variant.product.subcategory.category:id,name,slug',
                'items.variant:id,product_id,size_id,color_id',
                'items.variant.size:id,name',
                'items.variant.color:id,name',
                'items.variant.images:id,variant_id,url',
                'items.variant.product.images:id,product_id,url',
                'payment',
                'event:id,title'
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
}