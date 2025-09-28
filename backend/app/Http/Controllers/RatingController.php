<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use App\Models\Purchase;
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

        $ratings = Rating::with(['user:id,user_name', 'variant.product:id,name'])
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

        $request->validate([
            'variant_id' => 'required|exists:product_variants,id',
            'stars' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000'
        ]);

        $variantId = $request->variant_id;

        // Check if user has purchased this product variant
        $hasPurchased = Purchase::where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereHas('purchaseItems', function ($query) use ($variantId) {
                $query->where('variant_id', $variantId);
            })
            ->exists();

        if (!$hasPurchased) {
            return response()->json([
                'error' => 'You can only review products you have purchased'
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
                // Get the most recent purchase for this variant
                $purchase = Purchase::where('user_id', $user->id)
                    ->where('status', 'completed')
                    ->whereHas('purchaseItems', function ($query) use ($variantId) {
                        $query->where('variant_id', $variantId);
                    })
                    ->orderBy('created_at', 'desc')
                    ->first();

                // Create new rating
                $rating = Rating::create([
                    'user_id' => $user->id,
                    'variant_id' => $variantId,
                    'purchase_id' => $purchase->id,
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

        $reviewableProducts = Purchase::with(['purchaseItems.variant.product:id,name,slug', 'purchaseItems.variant:id,product_id,size_id,color_id'])
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereHas('purchaseItems')
            ->get()
            ->flatMap(function ($purchase) {
                return $purchase->purchaseItems->map(function ($item) use ($purchase) {
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
