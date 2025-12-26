<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\ProductPurchase;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;

class AdminCountsController extends Controller
{
    /**
     * Get pending orders count
     */
    public function pendingOrders(): JsonResponse
    {
        $count = ProductPurchase::where('status', 'pending')->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Get pending tickets count
     */
    public function pendingTickets(): JsonResponse
    {
        $count = Ticket::where('status', 'pending')->count();

        return response()->json(['count' => $count]);
    }
}

