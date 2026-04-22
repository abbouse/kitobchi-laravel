<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $q = Report::with('user:id,name,lastname,avatar,phone_number');
        $tab = $request->get('tab', 'pending');

        if (in_array($tab, ['pending', 'reviewed', 'dismissed'], true)) {
            $q->where('status', $tab);
        }

        if ($request->filled('type')) {
            $q->where('reportable_type', $request->type);
        }

        if ($s = $request->search) {
            $q->where(fn($sq) => $sq
                ->where('reason', 'like', "%$s%")
                ->orWhere('comment', 'like', "%$s%")
                ->orWhere('reportable_id', $s)
            );
        }

        $reports = $q->latest()->paginate(20)->withQueryString();

        $counts = [
            'pending'   => Report::where('status', 'pending')->count(),
            'reviewed'  => Report::where('status', 'reviewed')->count(),
            'dismissed' => Report::where('status', 'dismissed')->count(),
        ];
        $types = Report::select('reportable_type')->distinct()->pluck('reportable_type');

        return view('a122.complaints.index', compact('reports', 'counts', 'tab', 'types'));
    }

    public function show(Report $complaint)
    {
        $complaint->load('user:id,name,lastname,avatar,phone_number');

        $typeMap = [
            'conversation_message' => \App\Models\Message::class,
            'book_club' => \App\Models\BookClub::class,
        ];

        $reportable = null;
        if (isset($typeMap[$complaint->reportable_type])) {
            try {
                $reportable = $typeMap[$complaint->reportable_type]::find($complaint->reportable_id);
            } catch (\Throwable) {
                $reportable = null;
            }
        }

        $otherReports = Report::where('user_id', $complaint->user_id)
            ->where('id', '!=', $complaint->id)
            ->latest()
            ->take(5)
            ->get();

        $report = $complaint;

        return view('a122.complaints.show', compact('complaint', 'report', 'reportable', 'otherReports'));
    }

    public function updateStatus(Request $request, Report $complaint)
    {
        $request->validate(['status' => 'required|in:pending,reviewed,dismissed']);
        $complaint->update([
            'status' => $request->status,
        ]);

        return back()->with('success', match ($request->status) {
            'reviewed' => "Shikoyat ko'rib chiqildi.",
            'dismissed' => 'Shikoyat rad etildi.',
            default => 'Holat yangilandi.',
        });
    }

    public function destroy(Report $complaint)
    {
        $complaint->delete();

        return redirect()->route('admin.complaints.index')->with('success', "Shikoyat o'chirildi.");
    }
}
