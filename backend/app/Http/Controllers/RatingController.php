<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use App\Models\ProductPurchase;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RatingController extends Controller
{
    /**
     * Get all ratings for a specific product variant
     */
    public function index(Request $request)
    {
        $request->validate([
            'variant_id' => 'required|exists:product_variants,id'
        ]);

        $ratings = Rating::with([
            'user:id,user_name',
            'user.profile:id,user_id,avatar_url',
            'variant.product:id,name'
        ])
            ->where('variant_id', $request->variant_id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($ratings);
    }

    /**
     * Get user's rating for a specific product variant
     */
    public function show(Request $request)
    {
        $request->validate([
            'variant_id' => 'required|exists:product_variants,id'
        ]);

        $user = Auth::user();
        $rating = Rating::where('user_id', $user->id)
            ->where('variant_id', $request->variant_id)
            ->first();

        return response()->json($rating);
    }

    /**
     * Create or update a rating
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Prevent admins from writing reviews
        if ($user->role === 'admin') {
            return response()->json([
                'error' => 'Administrators cannot write reviews. Please use a customer account to write reviews.'
            ], 403);
        }

        $request->validate([
            'variant_id' => 'required|exists:product_variants,id',
            'stars' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000'
        ]);

        $variantId = $request->variant_id;

        // Get the product_id for the variant being reviewed
        $variant = ProductVariant::find($variantId);
        if (!$variant) {
            return response()->json([
                'error' => 'Product variant not found.'
            ], 404);
        }

        // Check if user has purchased any variant of this product (not just the exact variant)
        // This allows users to review any variant of a product they've purchased
        $hasPurchased = DB::table('product_purchases')
            ->join('product_purchase_items', 'product_purchases.id', '=', 'product_purchase_items.product_purchase_id')
            ->join('product_variants', 'product_purchase_items.variant_id', '=', 'product_variants.id')
            ->where('product_purchases.user_id', $user->id)
            ->where('product_purchases.status', 'completed')
            ->where('product_variants.product_id', $variant->product_id)
            ->exists();

        if (!$hasPurchased) {
            return response()->json([
                'error' => 'You can only review products you have purchased and received. Please complete your order first.'
            ], 403);
        }

        DB::beginTransaction();
        try {
            // Check if rating already exists
            $existingRating = Rating::where('user_id', $user->id)
                ->where('variant_id', $variantId)
                ->first();

            if ($existingRating) {
                // Update existing rating
                $existingRating->update([
                    'stars' => $request->stars,
                    'comment' => $request->comment
                ]);
                $rating = $existingRating;
            } else {
                // Get the most recent purchase for any variant of this product
                $purchase = ProductPurchase::where('user_id', $user->id)
                    ->where('status', 'completed')
                    ->whereHas('items', function ($query) use ($variant) {
                        $query->whereHas('variant', function ($q) use ($variant) {
                            $q->where('product_id', $variant->product_id);
                        });
                    })
                    ->orderBy('created_at', 'desc')
                    ->first();

                // Create new rating
                $rating = Rating::create([
                    'user_id' => $user->id,
                    'variant_id' => $variantId,
                    'product_purchase_id' => $purchase->id,
                    'stars' => $request->stars,
                    'comment' => $request->comment
                ]);
            }

            // Load relationships
            $rating->load(['user:id,user_name', 'variant.product:id,name']);

            DB::commit();

            return response()->json([
                'message' => $existingRating ? 'Rating updated successfully' : 'Rating created successfully',
                'rating' => $rating
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => 'Failed to save rating'
            ], 500);
        }
    }

    /**
     * Delete a rating
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'variant_id' => 'required|exists:product_variants,id'
        ]);

        $user = Auth::user();

        $rating = Rating::where('user_id', $user->id)
            ->where('variant_id', $request->variant_id)
            ->first();

        if (!$rating) {
            return response()->json([
                'error' => 'Rating not found'
            ], 404);
        }

        $rating->delete();

        return response()->json([
            'message' => 'Rating deleted successfully'
        ]);
    }

    /**
     * Get user's purchased products that can be reviewed
     */
    public function reviewableProducts(Request $request)
    {
        $user = Auth::user();

        $reviewableProducts = ProductPurchase::with([
            'items.variant.product:id,name,slug,subcategory_id',
            'items.variant.product.subcategory:id,name,slug,category_id',
            'items.variant.product.subcategory.category:id,name,slug',
            'items.variant:id,product_id,size_id,color_id'
        ])
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereHas('items')
            ->get()
            ->flatMap(function ($purchase) {
                return $purchase->items->map(function ($item) use ($purchase) {
                    return [
                        'purchase_id' => $purchase->id,
                        'variant_id' => $item->variant_id,
                        'product' => $item->variant->product,
                        'variant' => $item->variant,
                        'quantity' => $item->quantity,
                        'purchased_at' => $purchase->created_at,
                        'has_rated' => Rating::where('user_id', $purchase->user_id)
                            ->where('variant_id', $item->variant_id)
                            ->exists()
                    ];
                });
            })
            ->unique('variant_id')
            ->values();

        return response()->json($reviewableProducts);
    }

    /**
     * Get user's reviewed products only
     */
    public function reviewedProducts(Request $request)
    {
        $user = Auth::user();

        // Get all ratings with their purchase and variant information
        $ratings = Rating::with([
            'productPurchase:id,user_id,status,created_at',
            'variant.product:id,name,slug,subcategory_id',
            'variant.product.subcategory:id,name,slug,category_id',
            'variant.product.subcategory.category:id,name,slug',
            'variant.product.images:id,product_id,variant_id,url,is_primary',
            'variant:id,product_id,size_id,color_id',
            'variant.size:id,name',
            'variant.color:id,name',
            'variant.images:id,variant_id,product_id,url,is_primary'
        ])
            ->where('user_id', $user->id)
            ->get();

        if ($ratings->isEmpty()) {
            return response()->json([]);
        }

        // Get purchase IDs from ratings
        $purchaseIds = $ratings->pluck('product_purchase_id')->unique()->toArray();

        // Get the purchases and their items
        $purchases = ProductPurchase::with([
            'items.variant.product:id,name,slug,subcategory_id',
            'items.variant.product.subcategory:id,name,slug,category_id',
            'items.variant.product.subcategory.category:id,name,slug',
            'items.variant.product.images:id,product_id,variant_id,url,is_primary',
            'items.variant:id,product_id,size_id,color_id',
            'items.variant.size:id,name',
            'items.variant.color:id,name',
            'items.variant.images:id,variant_id,product_id,url,is_primary'
        ])
            ->whereIn('id', $purchaseIds)
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->get()
            ->keyBy('id');

        // Map ratings to reviewed products
        $reviewedProducts = $ratings->map(function ($rating) use ($purchases) {
            $purchase = $purchases->get($rating->product_purchase_id);
            
            if (!$purchase) {
                return null;
            }

            // Find the item in the purchase that matches the rated variant, or use any item from the purchase
            $item = $purchase->items->firstWhere('variant_id', $rating->variant_id);
            
            // If no exact match, use the first item (since we allow reviewing any variant of a purchased product)
            if (!$item) {
                $item = $purchase->items->first();
            }

            if (!$item || !$item->variant) {
                return null;
            }

            $product = $rating->variant->product ?? $item->variant->product;
            $variant = $rating->variant;
            
            // Get product image - prefer variant image, fallback to product primary image
            $productImage = null;
            if ($variant && $variant->images && $variant->images->isNotEmpty()) {
                $productImage = $variant->images->first()->url;
            } elseif ($product && $product->images && $product->images->isNotEmpty()) {
                // Try to get primary image first
                $primaryImage = $product->images->firstWhere('is_primary', true);
                $productImage = $primaryImage ? $primaryImage->url : $product->images->first()->url;
            }

            return [
                'purchase_id' => $purchase->id,
                'variant_id' => $rating->variant_id, // Use the rated variant_id
                'product' => $product,
                'variant' => $variant,
                'quantity' => $item->quantity,
                'purchased_at' => $purchase->created_at,
                'has_rated' => true,
                'product_image' => $productImage
            ];
        })
        ->filter() // Remove null values
        ->unique('variant_id')
        ->values();

        return response()->json($reviewedProducts);
    }

    /**
     * Get rating statistics for a product variant
     */
    public function statistics(Request $request)
    {
        $request->validate([
            'variant_id' => 'required|exists:product_variants,id'
        ]);

        $stats = Rating::where('variant_id', $request->variant_id)
            ->selectRaw('
                COUNT(*) as total_ratings,
                AVG(stars) as average_rating,
                SUM(CASE WHEN stars = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN stars = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN stars = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN stars = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN stars = 1 THEN 1 ELSE 0 END) as one_star
            ')
            ->first();

        return response()->json($stats);
    }
}
