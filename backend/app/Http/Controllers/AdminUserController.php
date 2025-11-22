<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ProductPurchase;
use App\Models\TicketPurchase;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminUserController extends Controller
{
    /**
     * Display a listing of all customers (users with role 'user').
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            // Check if user is admin
            $admin = Auth::user();
            if (!$admin || $admin->role !== 'admin') {
                return response()->json([
                    'error' => 'Unauthorized. Admin access required.',
                ], 403);
            }

            $query = User::where('role', 'user')
                ->with('profile:id,user_id,full_name,phone,complete_address,avatar_url');

            // Search functionality
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('user_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhereHas('profile', function ($profileQuery) use ($search) {
                          $profileQuery->where('full_name', 'like', "%{$search}%")
                                       ->orWhere('phone', 'like', "%{$search}%");
                      });
                });
            }

            // Filter by suspension status
            if ($request->has('is_suspended')) {
                $query->where('is_suspended', $request->is_suspended === 'true' || $request->is_suspended === true);
            }

            // Get transaction counts for each user
            $users = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));

            // Add transaction counts to each user
            $users->getCollection()->transform(function ($user) {
                $user->product_purchases_count = ProductPurchase::where('user_id', $user->id)->count();
                $user->ticket_purchases_count = TicketPurchase::where('user_id', $user->id)->count();
                $user->cancellation_count = \App\Services\UserSuspensionService::getCancellationCount($user);
                return $user;
            });

            return response()->json($users);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch users',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            // Check if user is admin
            $admin = Auth::user();
            if (!$admin || $admin->role !== 'admin') {
                return response()->json([
                    'error' => 'Unauthorized. Admin access required.',
                ], 403);
            }

            $user = User::where('role', 'user')
                ->with('profile')
                ->findOrFail($id);

            // Get transaction counts
            $user->product_purchases_count = ProductPurchase::where('user_id', $user->id)->count();
            $user->ticket_purchases_count = TicketPurchase::where('user_id', $user->id)->count();
            $user->cancellation_count = \App\Services\UserSuspensionService::getCancellationCount($user);

            return response()->json([
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch user',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Suspend a user.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function suspend(Request $request, $id)
    {
        try {
            // Check if user is admin
            $admin = Auth::user();
            if (!$admin || $admin->role !== 'admin') {
                return response()->json([
                    'error' => 'Unauthorized. Admin access required.',
                ], 403);
            }

            $request->validate([
                'reason' => 'nullable|string|max:1000',
            ]);

            $user = User::where('role', 'user')->findOrFail($id);

            if ($user->isSuspended()) {
                return response()->json([
                    'error' => 'User is already suspended.',
                ], 400);
            }

            $reason = $request->reason ?? 'Account suspended by administrator.';
            $user->suspend($reason);

            // Send notification
            NotificationService::createSuspensionNotification($user, $reason, true);

            return response()->json([
                'message' => 'User suspended successfully',
                'data' => $user->fresh('profile'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to suspend user',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unsuspend a user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function unsuspend($id)
    {
        try {
            // Check if user is admin
            $admin = Auth::user();
            if (!$admin || $admin->role !== 'admin') {
                return response()->json([
                    'error' => 'Unauthorized. Admin access required.',
                ], 403);
            }

            $user = User::where('role', 'user')->findOrFail($id);

            if (!$user->isSuspended()) {
                return response()->json([
                    'error' => 'User is not suspended.',
                ], 400);
            }

            $user->unsuspend();

            // Send notification
            NotificationService::createSuspensionNotification($user, '', false);

            return response()->json([
                'message' => 'User unsuspended successfully',
                'data' => $user->fresh('profile'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to unsuspend user',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's transactions (product purchases and ticket purchases).
     *
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function transactions(Request $request, $id)
    {
        try {
            // Check if user is admin
            $admin = Auth::user();
            if (!$admin || $admin->role !== 'admin') {
                return response()->json([
                    'error' => 'Unauthorized. Admin access required.',
                ], 403);
            }

            $user = User::where('role', 'user')->findOrFail($id);

            // Get product purchases
            $productPurchases = ProductPurchase::where('user_id', $user->id)
                ->with([
                    'items.variant.product:id,name,slug',
                    'items.variant.size:id,name',
                    'items.variant.color:id,name',
                    'payment'
                ])
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 10), ['*'], 'product_page');

            // Get ticket purchases
            $ticketPurchases = TicketPurchase::where('user_id', $user->id)
                ->with([
                    'event:id,title',
                    'tickets:id,ticket_purchase_id,ticket_number,status,price',
                    'payment'
                ])
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 10), ['*'], 'ticket_page');

            return response()->json([
                'product_purchases' => $productPurchases,
                'ticket_purchases' => $ticketPurchases,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch transactions',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}