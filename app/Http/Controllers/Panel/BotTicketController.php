<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\BotTicket;
use App\Models\BotOperator;
use App\Models\BotOperatorStats;
use Illuminate\Http\Request;

class BotTicketController extends Controller
{
    private const STATUSES = [
        'queue'  => ['label' => 'Navbatda',    'class' => 'ob-a'],
        'active' => ['label' => 'Aktiv',       'class' => 'ob-b'],
        'closed' => ['label' => 'Yopilgan',    'class' => 'ob-p'],
        'rated'  => ['label' => 'Baholangan',  'class' => 'ob-c'],
    ];

    public function index(Request $request)
    {
        $q = BotTicket::with(['operator']);

        if ($s = $request->search) {
            $q->where(fn($x) => $x
                ->where('id', $s)
                ->where('name', 'like', "%$s%")
                ->orWhere('username', 'like', "%$s%")
                ->orWhere('user_id', $s)
            );
        }

        $tab = $request->get('tab', 'queue');
        if ($tab !== 'all') {
            $q->where('status', $tab);
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

        $tickets   = $q->latest()->paginate(25)->withQueryString();
        $operators = BotOperator::where('is_active', 1)->orderBy('name')->get();

        $counts = collect(array_keys(self::STATUSES))->mapWithKeys(fn($s) => [
            $s => BotTicket::where('status', $s)->count(),
        ])->all();
        $counts['all'] = BotTicket::count();

        $statuses = self::STATUSES;

        return view('panel.bot-tickets.index', compact('tickets', 'counts', 'tab', 'statuses', 'operators'));
    }

    public function show(BotTicket $botTicket)
    {
        $botTicket->load(['operator', 'attachments']);
        $operators = BotOperator::where('is_active', 1)->orderBy('name')->get();
        $statuses  = self::STATUSES;
        return view('panel.bot-tickets.show', compact('botTicket', 'operators', 'statuses'));
    }

    public function assign(Request $request, BotTicket $botTicket)
    {
        $request->validate(['operator_id' => 'required|exists:bot_operators,id']);
        $botTicket->update([
            'operator_id' => $request->operator_id,
            'status'      => 'active',
        ]);
        return back()->with('success', 'Operator tayinlandi.');
    }

    public function close(Request $request, BotTicket $botTicket)
    {
        $request->validate(['close_reason' => 'nullable|string|max:100']);
        $botTicket->update([
            'status'       => 'closed',
            'close_reason' => $request->close_reason,
        ]);
        return back()->with('success', "Murojaat yopildi.");
    }

    // ── Operatorlar ro'yxati ──────────────────────────────────────
    public function operators()
    {
        $operators = BotOperator::with('stats')->orderBy('name')->paginate(20);
        return view('panel.bot-tickets.operators', compact('operators'));
    }

    public function toggleOperator(BotOperator $botOperator)
    {
        $botOperator->update(['is_active' => ! $botOperator->is_active]);
        return back()->with('success', $botOperator->is_active ? 'Operator faollashtirildi.' : 'Operator bloklandi.');
    }
}