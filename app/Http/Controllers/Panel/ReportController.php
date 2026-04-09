<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $q   = Report::with('user:id,name,lastname,avatar,phone_number');
        $tab = $request->get('tab', 'pending');

        if (in_array($tab, ['pending', 'reviewed', 'dismissed'])) {
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

        return view('panel.reports.index', compact('reports', 'counts', 'tab', 'types'));
    }

    public function show(Report $report)
    {
        $report->load('user:id,name,lastname,avatar,phone_number');

        $typeMap = [
            'conversation_message' => \App\Models\Message::class,
            'book_club'            => \App\Models\BookClub::class,
        ];

        $reportable = null;
        if (isset($typeMap[$report->reportable_type])) {
            try {
                $reportable = $typeMap[$report->reportable_type]::find($report->reportable_id);
            } catch (\Exception $e) {}
        }

        $otherReports = Report::where('user_id', $report->user_id)
            ->where('id', '!=', $report->id)
            ->latest()->take(5)->get();

        return view('panel.reports.show', compact('report', 'reportable', 'otherReports'));
    }

    /**
     * Status o'zgartirish: pending → reviewed | dismissed
     */
    public function updateStatus(Request $request, Report $report)
    {
        $request->validate(['status' => 'required|in:pending,reviewed,dismissed']);
        $report->update(['status' => $request->status]);

        return back()->with('success', match($request->status) {
            'reviewed'  => 'Shikoyat ko\'rib chiqildi.',
            'dismissed' => 'Shikoyat rad etildi.',
            default     => 'Holat yangilandi.',
        });
    }

    public function destroy(Report $report)
    {
        $report->delete();
        return redirect()->route('panel.reports.index')
            ->with('success', 'Shikoyat o\'chirildi.');
    }
}