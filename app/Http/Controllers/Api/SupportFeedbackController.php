<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BotTicket;
use App\Services\Support\SupportInboxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ilovadagi support chat: yopilgan murojaatga "yaxshi / yomon" baho.
 */
class SupportFeedbackController extends Controller
{
    /**
     * GET support/feedback?conversation_id= — baholanmagan yopilgan murojaat bormi.
     */
    public function pending(Request $request): JsonResponse
    {
        $data = $request->validate([
            'conversation_id' => ['required', 'integer'],
        ]);

        $ticket = $this->latestTicket((int) $request->user()->id, (int) $data['conversation_id']);

        return response()->json([
            'status' => 'success',
            'data' => $ticket ? $this->payload($ticket) : null,
        ]);
    }

    /**
     * POST support/feedback {ticket_id, value: good|bad}
     */
    public function store(Request $request, SupportInboxService $inbox): JsonResponse
    {
        $data = $request->validate([
            'ticket_id' => ['required', 'integer'],
            'value' => ['required', 'in:good,bad'],
        ]);

        $ticket = BotTicket::query()
            ->whereKey($data['ticket_id'])
            ->where('source_type', 'shop_chat')
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $ticket) {
            return response()->json(['status' => 'error', 'message' => 'Murojaat topilmadi.'], 404);
        }

        if (in_array($ticket->status, SupportInboxService::CUSTOMER_OPEN, true)) {
            return response()->json(['status' => 'error', 'message' => 'Murojaat hali yakunlanmagan.'], 422);
        }

        if ($ticket->feedback) {
            return response()->json(['status' => 'error', 'message' => 'Bu murojaat allaqachon baholangan.'], 422);
        }

        $inbox->recordCustomerFeedback($ticket, $data['value']);

        return response()->json([
            'status' => 'success',
            'message' => 'Rahmat! Bahoingiz qabul qilindi.',
            'data' => $this->payload($ticket->fresh()),
        ]);
    }

    private function latestTicket(int $userId, int $conversationId): ?BotTicket
    {
        return BotTicket::query()
            ->where('source_type', 'shop_chat')
            ->where('user_id', $userId)
            ->where('source_conversation_id', $conversationId)
            ->latest('id')
            ->first();
    }

    private function payload(BotTicket $ticket): array
    {
        $open = in_array($ticket->status, SupportInboxService::CUSTOMER_OPEN, true);

        return [
            'ticket_id' => (int) $ticket->id,
            'status' => $open ? 'open' : 'closed',
            'feedback' => $ticket->feedback,
            'can_rate' => ! $open && ! $ticket->feedback && $ticket->feedback_requested_at !== null,
        ];
    }
}
