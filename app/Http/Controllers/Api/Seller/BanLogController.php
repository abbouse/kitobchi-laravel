<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\SellerBanLog;
use App\Models\Seller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BanLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:seller');
    }

    /**
     * Check if user has access to ban logs
     * ✅ SODDA LOGIKA:
     * - parent_id = NULL → OWNER → seller_id ishlatiladi
     * - parent_id mavjud → EMPLOYEE → parent_id (OWNER) ishlatiladi
     */
    private function getStoreSellerId($seller)
    {
        // EMPLOYEE bo'lsa → OWNER ID (parent_id)
        // OWNER bo'lsa → O'Z ID
        return $seller->parent_id ?: $seller->id;
    }

    private function hasBanLogAccess($seller)
    {
        $storeSellerId = $this->getStoreSellerId($seller);
        
        // OWNER yoki ADMIN (role=1)
        return !$seller->parent_id || $seller->role == 1;
    }

    public function index()
    {
        try {
            $seller = Auth::guard('seller')->user();
            if (!$seller) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            // ✅ ACCESS CHECK
            if (!$this->hasBanLogAccess($seller)) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Access denied. Ban logs available only for Admin and Owner.'
                ], 403);
            }

            // ✅ TO'G'RI: OWNER DO'KONI MA'LUMOTLARI
            $storeSellerId = $this->getStoreSellerId($seller);
            $banLogs = SellerBanLog::where('seller_id', $storeSellerId)
                ->orderBy('created_at', 'desc')
                ->get();

            $formattedBanLogs = $banLogs->map(function ($banLog) {
                $banLog->created_at_formatted = $banLog->created_at->format('d.m.Y, H:i');
                return $banLog;
            });

            return response()->json([
                'success' => true,
                'data' => $formattedBanLogs
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching seller ban logs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch ban logs'
            ], 500);
        }
    }

    public function markAsRead($id)
    {
        try {
            $seller = Auth::guard('seller')->user();
            if (!$seller) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            if (!$this->hasBanLogAccess($seller)) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Access denied. Ban logs available only for Admin and Owner.'
                ], 403);
            }

            $storeSellerId = $this->getStoreSellerId($seller);
            $banLog = SellerBanLog::where('seller_id', $storeSellerId)
                ->where('id', $id)
                ->first();

            if (!$banLog) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ban log not found'
                ], 404);
            }

            $banLog->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'Ban marked as read'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error marking seller ban log as read: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark ban log as read'
            ], 500);
        }
    }
    
    public function getCounts()
    {
        try {
            $seller = Auth::guard('seller')->user();
            
            if (!$seller) {
                return response()->json(['success' => false, 'message' => 'Seller not authenticated'], 401);
            }

            if (!$this->hasBanLogAccess($seller)) {
                return response()->json([
                    'success' => true,
                    'unread_count' => 0,
                    'warning_count' => 0,
                ], 200);
            }

            $storeSellerId = $this->getStoreSellerId($seller);
            $unreadCount = SellerBanLog::getUnreadCount($storeSellerId);
            $warningCount = SellerBanLog::getWarningCount($storeSellerId);

            return response()->json([
                'success' => true,
                'unread_count' => $unreadCount,
                'warning_count' => $warningCount,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching seller ban log counts: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch ban log counts'
            ], 500);
        }
    }
}