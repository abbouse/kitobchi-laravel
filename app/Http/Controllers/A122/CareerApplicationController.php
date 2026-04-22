<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Mail\CareerApplicantReplyMail;
use App\Models\CareerApplication;
use App\Models\CareerApplicationMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CareerApplicationController extends Controller
{
    private const STATUSES = [
        CareerApplication::STATUS_NEW => ['label' => 'Yangi', 'class' => 'ob-a'],
        CareerApplication::STATUS_REVIEWED => ['label' => 'Ko‘rilgan', 'class' => 'ob-b'],
        CareerApplication::STATUS_REPLIED => ['label' => 'Javob yuborilgan', 'class' => 'ob-c'],
        CareerApplication::STATUS_CLOSED => ['label' => 'Yopilgan', 'class' => 'ob-p'],
    ];

    public function index(Request $request)
    {
        $tab = $request->get('tab', 'all');
        $q = CareerApplication::with('vacancy')->latest();

        if ($tab === 'vacancy') {
            $q->where('type', CareerApplication::TYPE_VACANCY);
        } elseif ($tab === 'inquiry') {
            $q->where('type', CareerApplication::TYPE_INQUIRY);
        }

        if ($s = trim((string) $request->search)) {
            $q->where(function ($x) use ($s) {
                $x->where('full_name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('telegram_username', 'like', "%{$s}%");
                if (ctype_digit($s)) {
                    $x->orWhere('id', (int) $s);
                }
            });
        }

        $applications = $q->latest()->paginate(25)->withQueryString();

        $counts = [
            'all' => CareerApplication::count(),
            'vacancy' => CareerApplication::where('type', CareerApplication::TYPE_VACANCY)->count(),
            'inquiry' => CareerApplication::where('type', CareerApplication::TYPE_INQUIRY)->count(),
            'new' => CareerApplication::where('status', CareerApplication::STATUS_NEW)->count(),
        ];

        $statuses = self::STATUSES;

        return view('a122.job-applications.index', compact('applications', 'counts', 'tab', 'statuses'));
    }

    public function show(CareerApplication $application)
    {
        $careerApplication = $application;
        $careerApplication->load(['vacancy', 'messages.admin']);

        if ($careerApplication->read_at === null) {
            $careerApplication->update(['read_at' => now()]);
        }

        $statuses = self::STATUSES;

        return view('a122.job-applications.show', compact('careerApplication', 'statuses'));
    }

    public function updateStatus(Request $request, CareerApplication $application)
    {
        $request->validate(['status' => 'required|string|in:' . implode(',', array_keys(self::STATUSES))]);
        $application->update(['status' => $request->status]);

        return back()->with('success', 'Holat yangilandi.');
    }

    public function sendReply(Request $request, CareerApplication $application)
    {
        $data = $request->validate(['body' => 'required|string|min:5|max:12000']);
        $admin = $request->user('panel');

        CareerApplicationMessage::create([
            'career_application_id' => $application->id,
            'admin_id' => $admin?->id,
            'sender' => CareerApplicationMessage::SENDER_ADMIN,
            'body' => $data['body'],
        ]);

        try {
            Mail::to($application->email)->send(new CareerApplicantReplyMail($application, $data['body']));
        } catch (\Throwable $e) {
            Log::error('CareerApplicantReplyMail failed', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Xabar saqlandi, lekin email yuborilmadi.');
        }

        if ($application->status !== CareerApplication::STATUS_CLOSED) {
            $application->update(['status' => CareerApplication::STATUS_REPLIED]);
        }

        return back()->with('success', 'Javob nomzodning emailiga yuborildi.');
    }

    public function downloadCv(CareerApplication $application): StreamedResponse
    {
        if (! $application->cv_path || ! Storage::disk('public')->exists($application->cv_path)) {
            abort(404);
        }

        return Storage::disk('public')->download(
            $application->cv_path,
            $application->cv_original_name ?: 'cv.pdf'
        );
    }
}
