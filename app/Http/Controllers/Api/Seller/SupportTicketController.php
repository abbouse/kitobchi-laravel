<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\SellerSupportTicket;
use App\Models\SellerSupportTicketMessage;
use App\Services\Support\SupportInboxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupportTicketController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:seller');
    }

    public function index(Request $request): JsonResponse
    {
        $sellerId = $this->storeSellerId();
        $status = (string) $request->query('status', 'all');

        $tickets = SellerSupportTicket::query()
            ->where('seller_id', $sellerId)
            ->with('latestMessage')
            ->withCount('messages')
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest('last_message_at')
            ->latest('id')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $tickets->getCollection()->map(fn (SellerSupportTicket $ticket) => $this->ticketListPayload($ticket))->values(),
            'pagination' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'total' => $tickets->total(),
            ],
            'counts' => [
                'all' => SellerSupportTicket::query()->where('seller_id', $sellerId)->count(),
                'open' => SellerSupportTicket::query()->where('seller_id', $sellerId)->where('status', 'open')->count(),
                'answered' => SellerSupportTicket::query()->where('seller_id', $sellerId)->where('status', 'answered')->count(),
                'waiting' => SellerSupportTicket::query()->where('seller_id', $sellerId)->where('status', 'waiting')->count(),
                'closed' => SellerSupportTicket::query()->where('seller_id', $sellerId)->where('status', 'closed')->count(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject' => ['nullable', 'string', 'max:160'],
            'message' => ['required', 'string', 'min:2', 'max:5000'],
        ]);

        $seller = Auth::guard('seller')->user();
        $sellerId = $this->storeSellerId();
        $subject = trim((string) ($data['subject'] ?? ''));
        if ($subject === '') {
            $subject = mb_substr(trim($data['message']), 0, 90);
        }

        $ticket = SellerSupportTicket::create([
            'seller_id' => $sellerId,
            'subject' => $subject,
            'status' => 'open',
            'last_message_at' => now(),
            'admin_unread_count' => 1,
        ]);

        $message = SellerSupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_type' => 'seller',
            'sender_id' => $seller?->id,
            'message' => $data['message'],
        ]);
        app(SupportInboxService::class)->afterSellerMessage($message, true);

        return response()->json([
            'status' => 'success',
            'message' => 'Murojaat yuborildi.',
            'data' => $this->ticketDetailPayload($ticket->fresh(['messages', 'seller'])),
        ], 201);
    }

    public function show(SellerSupportTicket $ticket): JsonResponse
    {
        abort_unless((int) $ticket->seller_id === $this->storeSellerId(), 403);

        if ((int) $ticket->seller_unread_count > 0) {
            $ticket->update(['seller_unread_count' => 0]);
            // Operator panelida "o'qildi" belgisi yangilanadi
            try {
                $inbox = app(SupportInboxService::class);
                $inbox->broadcastThread($inbox->shopSummary($ticket->fresh()));
            } catch (\Throwable) {
            }
        }
        $ticket->load(['messages.admin', 'seller']);

        return response()->json([
            'status' => 'success',
            'data' => $this->ticketDetailPayload($ticket),
        ]);
    }

    public function reply(Request $request, SellerSupportTicket $ticket): JsonResponse
    {
        abort_unless((int) $ticket->seller_id === $this->storeSellerId(), 403);

        $data = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:5000'],
        ]);

        if ($ticket->status === 'closed') {
            return response()->json(['status' => 'error', 'message' => 'Yopilgan murojaatga javob yozib bo‘lmaydi.'], 422);
        }

        $seller = Auth::guard('seller')->user();

        $message = SellerSupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_type' => 'seller',
            'sender_id' => $seller?->id,
            'message' => $data['message'],
        ]);

        $ticket->update([
            'status' => 'open',
            'last_message_at' => now(),
            'admin_unread_count' => $ticket->admin_unread_count + 1,
            'seller_unread_count' => 0,
        ]);
        app(SupportInboxService::class)->afterSellerMessage($message, true);

        return response()->json([
            'status' => 'success',
            'message' => 'Xabar yuborildi.',
            'data' => $this->ticketDetailPayload($ticket->fresh(['messages.admin', 'seller'])),
        ]);
    }

    public function close(Request $request, SellerSupportTicket $ticket): JsonResponse
    {
        abort_unless((int) $ticket->seller_id === $this->storeSellerId(), 403);

        $request->validate([
            'close_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $ticket->update([
            'status' => 'closed',
            'closed_at' => now(),
            'close_reason' => $request->input('close_reason') ?: 'seller_closed',
        ]);

        $message = SellerSupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_type' => 'system',
            'sender_id' => null,
            'message' => 'Do‘kon murojaatni yopdi.',
            'is_internal' => true,
        ]);
        app(SupportInboxService::class)->afterSellerMessage($message, true);

        return response()->json([
            'status' => 'success',
            'message' => 'Murojaat yopildi.',
            'data' => $this->ticketDetailPayload($ticket->fresh(['messages.admin', 'seller'])),
        ]);
    }

    /**
     * Yopilgan murojaatga baho: good | bad (bir marta).
     */
    public function feedback(Request $request, SellerSupportTicket $ticket): JsonResponse
    {
        abort_unless((int) $ticket->seller_id === $this->storeSellerId(), 403);

        $data = $request->validate([
            'value' => ['required', 'in:good,bad'],
        ]);

        if ($ticket->status !== 'closed') {
            return response()->json(['status' => 'error', 'message' => 'Baho faqat yopilgan murojaatga qo‘yiladi.'], 422);
        }

        if ($ticket->feedback) {
            return response()->json(['status' => 'error', 'message' => 'Bu murojaat allaqachon baholangan.'], 422);
        }

        app(SupportInboxService::class)->recordShopFeedback($ticket, $data['value']);

        return response()->json([
            'status' => 'success',
            'message' => 'Rahmat! Bahoingiz qabul qilindi.',
            'data' => $this->ticketDetailPayload($ticket->fresh(['messages.admin', 'seller'])),
        ]);
    }

    private function storeSellerId(): int
    {
        $seller = Auth::guard('seller')->user();
        abort_unless($seller, 401);

        return (int) ($seller->parent_id ?: $seller->id);
    }

    private function ticketListPayload(SellerSupportTicket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'subject' => $ticket->subject ?: 'Kitobchi bilan suhbat',
            'status' => $ticket->status,
            'messages_count' => (int) ($ticket->messages_count ?? 0),
            'unread_count' => (int) $ticket->seller_unread_count,
            'feedback' => $ticket->feedback,
            'can_rate' => $ticket->status === 'closed' && ! $ticket->feedback,
            'last_message' => $ticket->latestMessage && ! $ticket->latestMessage->is_internal ? $ticket->latestMessage->message : null,
            'last_message_at' => optional($ticket->last_message_at ?: $ticket->updated_at)->format('d.m.Y H:i'),
            'created_at' => optional($ticket->created_at)->format('d.m.Y H:i'),
        ];
    }

    private function ticketDetailPayload(SellerSupportTicket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'subject' => $ticket->subject ?: 'Kitobchi bilan suhbat',
            'status' => $ticket->status,
            'close_reason' => $ticket->close_reason,
            'feedback' => $ticket->feedback,
            'can_rate' => $ticket->status === 'closed' && ! $ticket->feedback,
            'closed_at' => optional($ticket->closed_at)->format('d.m.Y H:i'),
            'created_at' => optional($ticket->created_at)->format('d.m.Y H:i'),
            'messages' => $ticket->messages->reject(fn (SellerSupportTicketMessage $message) => (bool) $message->is_internal)->map(fn (SellerSupportTicketMessage $message) => [
                'id' => $message->id,
                'sender_type' => $message->sender_type,
                'sender_name' => $message->sender_type === 'admin'
                    ? ($message->admin?->name ?: 'Kitobchi support')
                    : ($message->sender_type === 'seller' ? 'Siz' : 'Tizim'),
                'message' => $message->message,
                'created_at' => optional($message->created_at)->format('d.m.Y H:i'),
            ])->values(),
        ];
    }
}
