<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\BotOperator;
use App\Models\BotTicket;
use App\Services\SessionService;
use App\Services\SupportChatBridgeService;
use App\Services\TelegramSupportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SupportController extends Controller
{
    private const STATUSES = [
        'queue'  => ['label' => 'Navbatda', 'class' => 'ob-a'],
        'active' => ['label' => 'Aktiv', 'class' => 'ob-b'],
        'closed' => ['label' => 'Yopilgan', 'class' => 'ob-p'],
        'rated'  => ['label' => 'Baholangan', 'class' => 'ob-c'],
    ];

    public function index(Request $request)
    {
        $q = BotTicket::with(['operator', 'latestMessage'])->withCount('messages');

        $tab = $request->get('tab', 'queue');
        if ($tab !== 'all') {
            $q->where('status', $tab);
        }

        if ($s = $request->search) {
            $q->where(fn($x) => $x
                ->where('id', $s)
                ->orWhere('name', 'like', "%$s%")
                ->orWhere('username', 'like', "%$s%")
                ->orWhere('user_id', $s)
            );
        }

        if ($request->filled('operator_id')) {
            $q->where('operator_id', $request->operator_id);
        }
        if ($request->date_from) {
            $q->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $q->whereDate('created_at', '<=', $request->date_to);
        }

        $tickets = $q->latest()->paginate(25)->withQueryString();
        $operators = BotOperator::where('is_active', 1)->orderBy('name')->get();

        $counts = [
            'all'    => BotTicket::count(),
            'queue'  => BotTicket::where('status', 'queue')->count(),
            'active' => BotTicket::where('status', 'active')->count(),
            'closed' => BotTicket::where('status', 'closed')->count(),
            'rated'  => BotTicket::where('status', 'rated')->count(),
        ];
        $statuses = self::STATUSES;

        return view('a122.support.index', compact('tickets', 'counts', 'tab', 'statuses', 'operators'));
    }

    public function show(BotTicket $ticket)
    {
        $botTicket = $ticket;
        $botTicket->load(['operator', 'attachments', 'messages.admin', 'messages.operator']);
        $operators = BotOperator::where('is_active', 1)->orderBy('name')->get();
        $statuses = self::STATUSES;

        return view('a122.support.show', compact('botTicket', 'operators', 'statuses'));
    }

    public function assign(Request $request, BotTicket $ticket)
    {
        $request->validate(['operator_id' => 'required|exists:bot_operators,id']);
        $operator = BotOperator::query()->findOrFail((int) $request->operator_id);

        $prevOperatorId = $ticket->operator_id;
        if ($prevOperatorId && (int) $prevOperatorId !== (int) $operator->telegram_id) {
            BotOperator::where('telegram_id', $prevOperatorId)->update(['status' => SessionService::OP_ONLINE, 'updated_at' => now()]);
        }

        $ticket->update([
            'operator_id' => $operator->telegram_id,
            'status' => SessionService::STATUS_ACTIVE,
        ]);
        BotOperator::where('telegram_id', $operator->telegram_id)->update(['status' => SessionService::OP_BUSY, 'updated_at' => now()]);

        $operatorLabel = $operator->name ?: ($operator->username ? '@'.$operator->username : (string) $operator->telegram_id);
        SessionService::saveSystemMessage($ticket->id, "Operator tayinlandi: {$operatorLabel}");

        return back()->with('success', 'Operator tayinlandi.');
    }

    public function close(BotTicket $ticket)
    {
        SessionService::closeTicket($ticket->id, request('close_reason') ?: 'admin_panel_closed');
        SessionService::saveSystemMessage(
            $ticket->id,
            'Ticket admin paneldan yopildi'.(request('close_reason') ? ': '.request('close_reason') : '.')
        );

        if ($ticket->source_type !== 'shop_chat') {
            try {
                $bot = app(\SergiX44\Nutgram\Nutgram::class);
                \App\Handlers\UserHandler::sendRatingRequest($bot, (int) $ticket->user_id, (int) $ticket->id);
            } catch (\Throwable) {}
        }

        return back()->with('success', "Ticket yopildi.");
    }

    public function reply(
        Request $request,
        BotTicket $ticket,
        SupportChatBridgeService $supportChatBridgeService,
        TelegramSupportService $telegramSupportService
    )
    {
        $isShopChat = $ticket->source_type === 'shop_chat' && $ticket->source_conversation_id;

        $data = $request->validate([
            'message' => 'required|string|max:'.($isShopChat ? 5000 : 12000),
        ]);

        $admin = auth('panel')->user();

        try {
            if ($isShopChat) {
                $supportChatBridgeService->sendReplyToConversation($ticket, $data['message'], null, $admin?->id);
                return back()->with('success', 'Javob foydalanuvchiga yuborildi.');
            } else {
                $chatId = (int) $ticket->user_id;

                if ($chatId <= 0) {
                    throw new \RuntimeException('Foydalanuvchi chat ID topilmadi.');
                }

                $msg = SessionService::saveMessage(
                    ticketId: $ticket->id,
                    sentBy: 'admin',
                    message: $data['message'],
                    messageType: 'text',
                    adminId: $admin?->id,
                    isDelivered: false
                );
                $response = $telegramSupportService->sendText($chatId, $data['message']);
                $telegramMessageId = (int) data_get($response, 'result.message_id');

                $msg->update([
                    'is_delivered' => true,
                    'delivery_error' => null,
                    'telegram_message_id' => $telegramMessageId ?: null,
                ]);
                $ticket->update([
                    'status' => in_array($ticket->status, ['closed', 'rated']) ? 'active' : $ticket->status,
                    'updated_at' => now(),
                ]);

                return back()->with('success', 'Javob foydalanuvchiga yuborildi.');
            }
        } catch (\Throwable $e) {
            if (isset($msg)) {
                $msg->update([
                    'delivery_error' => $e->getMessage(),
                ]);
            }

            Log::warning('Admin support reply yuborilmadi', [
                'ticket_id' => $ticket->id,
                'source_type' => $ticket->source_type,
                'user_id' => $ticket->user_id,
                'admin_id' => $admin?->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Javobni yuborib bo‘lmadi: '.$e->getMessage());
        }
    }
}
