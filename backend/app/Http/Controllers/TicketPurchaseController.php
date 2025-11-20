<?php

namespace App\Http\Controllers;

use App\Models\TicketPurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketPurchaseController extends Controller
{
    /**
     * Display user's ticket purchases
     */
    public function index()
    {
        $purchases = TicketPurchase::where('user_id', Auth::id())
            ->with([
                'event:id,title,poster_url,location,date',
                'tickets',
                'payment'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $purchases,
        ]);
    }

    /**
     * Display single ticket purchase
     */
    public function show($id)
    {
        $purchase = TicketPurchase::where('user_id', Auth::id())
            ->with([
                'event:id,title,poster_url,location,date',
                'tickets',
                'payment'
            ])
            ->findOrFail($id);

        return response()->json([
            'data' => $purchase,
        ]);
    }

    /**
     * Display all ticket purchases for admin
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

            $query = TicketPurchase::with([
                'user:id,user_name,email,role',
                'user.profile:id,user_id,full_name,phone,complete_address',
                'event:id,title,poster_url,location,date',
                'tickets',
                'payment'
            ]);

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
                'error' => 'Failed to fetch ticket purchases',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display ticket purchase details for admin
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

            $purchase = TicketPurchase::with([
                'user:id,user_name,email,role',
                'user.profile:id,user_id,full_name,phone,complete_address',
                'event:id,title,poster_url,location,date',
                'tickets',
                'payment'
            ])->findOrFail($id);

            return response()->json([
                'data' => $purchase,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch ticket purchase details',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

