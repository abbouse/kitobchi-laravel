<?php

namespace App\Http\Controllers\Panel;

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
        CareerApplication::STATUS_NEW => ['label' => 'Yangi',       'class' => 'ob-a'],
        CareerApplication::STATUS_REVIEWED => ['label' => 'Ko‘rilgan',   'class' => 'ob-b'],
        CareerApplication::STATUS_REPLIED => ['label' => 'Javob yuborilgan', 'class' => 'ob-c'],
        CareerApplication::STATUS_CLOSED => ['label' => 'Yopilgan',    'class' => 'ob-p'],
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

        $applications = $q->paginate(25)->withQueryString();

        $counts = [
            'all' => CareerApplication::count(),
            'vacancy' => CareerApplication::where('type', CareerApplication::TYPE_VACANCY)->count(),
            'inquiry' => CareerApplication::where('type', CareerApplication::TYPE_INQUIRY)->count(),
            'new' => CareerApplication::where('status', CareerApplication::STATUS_NEW)->count(),
        ];

        $statuses = self::STATUSES;

        return view('panel.career-applications.index', compact('applications', 'counts', 'tab', 'statuses'));
    }

    public function show(CareerApplication $careerApplication)
    {
        $careerApplication->load(['vacancy', 'messages.admin']);

        if ($careerApplication->read_at === null) {
            $careerApplication->update(['read_at' => now()]);
        }

        $statuses = self::STATUSES;

        return view('panel.career-applications.show', compact('careerApplication', 'statuses'));
    }

    public function updateStatus(Request $request, CareerApplication $careerApplication)
    {
        $data = $request->validate([
            'status' => 'required|string|in:'.implode(',', array_keys(self::STATUSES)),
        ]);

        $careerApplication->update(['status' => $data['status']]);

        return back()->with('success', 'Holat yangilandi.');
    }

    public function sendReply(Request $request, CareerApplication $careerApplication)
    {
        $data = $request->validate([
            'body' => 'required|string|min:5|max:12000',
        ]);

        $admin = $request->user('panel');

        CareerApplicationMessage::create([
            'career_application_id' => $careerApplication->id,
            'admin_id' => $admin?->id,
            'sender' => CareerApplicationMessage::SENDER_ADMIN,
            'body' => $data['body'],
        ]);

        try {
            Mail::to($careerApplication->email)->send(
                new CareerApplicantReplyMail($careerApplication, $data['body'])
            );
        } catch (\Throwable $e) {
            Log::error('CareerApplicantReplyMail failed', [
                'application_id' => $careerApplication->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Xabar saqlandi, lekin email yuborilmadi. SMTP sozlamalarini tekshiring.');
        }

        if ($careerApplication->status !== CareerApplication::STATUS_CLOSED) {
            $careerApplication->update(['status' => CareerApplication::STATUS_REPLIED]);
        }

        return back()->with('success', 'Javob nomzodning emailiga yuborildi.');
    }

    public function downloadCv(CareerApplication $careerApplication): StreamedResponse
    {
        if (! $careerApplication->cv_path || ! Storage::disk('public')->exists($careerApplication->cv_path)) {
            abort(404);
        }

        return Storage::disk('public')->download(
            $careerApplication->cv_path,
            $careerApplication->cv_original_name ?: 'cv.pdf'
        );
    }
}
