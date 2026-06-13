<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\SellerSupportTicket;
use App\Models\SellerSupportTicketMessage;
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

        SellerSupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_type' => 'seller',
            'sender_id' => $seller?->id,
            'message' => $data['message'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Murojaat yuborildi.',
            'data' => $this->ticketDetailPayload($ticket->fresh(['messages', 'seller'])),
        ], 201);
    }

    public function show(SellerSupportTicket $ticket): JsonResponse
    {
        abort_unless((int) $ticket->seller_id === $this->storeSellerId(), 403);

        $ticket->update(['seller_unread_count' => 0]);
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

        SellerSupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_type' => 'seller',
            'sender_id' => $seller?->id,
            'message' => $data['message'],
        ]);

        $ticket->update([
            'status' => 'open',
            'last_message_at' => now(),
            'admin_unread_count' => $ticket->admin_unread_count + 1,
        ]);

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
            'close_reason' => $request->input('close_reason'),
        ]);

        return response()->json(['status' => 'success', 'message' => 'Murojaat yopildi.']);
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
            'last_message' => $ticket->latestMessage?->message,
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
            'closed_at' => optional($ticket->closed_at)->format('d.m.Y H:i'),
            'created_at' => optional($ticket->created_at)->format('d.m.Y H:i'),
            'messages' => $ticket->messages->map(fn (SellerSupportTicketMessage $message) => [
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
