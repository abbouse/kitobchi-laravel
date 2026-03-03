<?php

namespace App\Http\Controllers\Api\Courier;

use App\Http\Controllers\Controller;
use App\Models\CourierBanLog;
use App\Models\Couriers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CourierBanLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:courier');
    }
    public function index()
    {
        try {
            $courier = Auth::guard('courier')->user();
            if (!$courier) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            $banLogs = CourierBanLog::where('courier_id', $courier->id)
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
            Log::error('Error fetching courier ban logs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch ban logs'
            ], 500);
        }
    }

    public function markAsRead($id)
    {
        try {
            $courier = Auth::guard('courier')->user();
            if (!$courier) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            $banLog = CourierBanLog::where('courier_id', $courier->id)
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
            Log::error('Error marking courier ban log as read: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark ban log as read'
            ], 500);
        }
    }
    
    public function getCounts()
    {
        try {
            $courier = Auth::guard('courier')->user();
            
            if (!$courier) {
                return response()->json(['success' => false, 'message' => 'Courier not authenticated'], 401);
            }
            $unreadCount = CourierBanLog::getUnreadCount($courier->id);
            $warningCount = CourierBanLog::getWarningCount($courier->id);

            return response()->json([
                'success' => true,
                'unread_count' => $unreadCount,
                'warning_count' => $warningCount,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching courier ban log counts: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch ban log counts'
            ], 500);
        }
    }
}