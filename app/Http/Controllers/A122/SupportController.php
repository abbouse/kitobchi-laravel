<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\BotOperator;
use App\Models\BotTicket;
use Illuminate\Http\Request;

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
        $q = BotTicket::with(['operator']);

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
        $botTicket->load(['operator', 'attachments']);
        $operators = BotOperator::where('is_active', 1)->orderBy('name')->get();
        $statuses = self::STATUSES;

        return view('a122.support.show', compact('botTicket', 'operators', 'statuses'));
    }

    public function assign(Request $request, BotTicket $ticket)
    {
        $request->validate(['operator_id' => 'required|exists:bot_operators,id']);

        $ticket->update([
            'operator_id' => $request->operator_id,
            'status' => 'active',
        ]);

        return back()->with('success', 'Operator tayinlandi.');
    }

    public function close(BotTicket $ticket)
    {
        $ticket->update([
            'status' => 'closed',
            'closed_at' => now(),
            'close_reason' => request('close_reason'),
        ]);

        return back()->with('success', "Ticket yopildi.");
    }
}
