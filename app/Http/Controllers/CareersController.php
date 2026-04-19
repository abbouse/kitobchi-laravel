<?php

namespace App\Http\Controllers;

use App\Mail\CareerApplicationAcknowledgmentMail;
use App\Models\CareerApplication;
use App\Models\CareerApplicationMessage;
use App\Models\Vacancy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CareersController extends Controller
{
    public function index()
    {
        $vacancies = Vacancy::active()->ordered()->with('translations')->get();

        return view('careers.index', compact('vacancies'));
    }

    public function storeVacancy(Request $request, Vacancy $vacancy)
    {
        if (! $vacancy->is_active) {
            abort(404);
        }

        $data = $request->validate([
            'full_name' => 'required|string|max:120',
            'email' => 'required|email:rfc|max:255',
            'telegram_username' => 'required|string|max:120',
            'cover_message' => 'nullable|string|max:8000',
            'cv' => 'required|file|mimes:pdf,doc,docx|max:10240',
        ], trans('careers.validation'), trans('careers.attributes'));

        $tg = CareerApplication::normalizeTelegram($data['telegram_username']);

        $path = $request->file('cv')->store('career-cvs', 'public');
        $orig = $request->file('cv')->getClientOriginalName();

        $application = DB::transaction(function () use ($data, $vacancy, $tg, $path, $orig) {
            $app = CareerApplication::create([
                'type' => CareerApplication::TYPE_VACANCY,
                'vacancy_id' => $vacancy->id,
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'telegram_username' => $tg,
                'cover_message' => $data['cover_message'] ?? null,
                'cv_path' => $path,
                'cv_original_name' => $orig,
                'status' => CareerApplication::STATUS_NEW,
            ]);

            CareerApplicationMessage::create([
                'career_application_id' => $app->id,
                'admin_id' => null,
                'sender' => CareerApplicationMessage::SENDER_SYSTEM,
                'body' => 'Ariza sayt orqali yuborildi: «'.$vacancy->title.'».',
            ]);

            return $app;
        });

        $this->trySendAck($application);

        return redirect()
            ->route('careers.index', ['applied' => 1])
            ->with('career_success', __('careers.flash.application_sent'));
    }

    public function storeInquiry(Request $request)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:120',
            'email' => 'required|email:rfc|max:255',
            'telegram_username' => 'required|string|max:120',
            'message' => 'required|string|min:20|max:8000',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ], trans('careers.validation'), trans('careers.attributes'));

        $tg = CareerApplication::normalizeTelegram($data['telegram_username']);

        $path = null;
        $orig = null;
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('career-cvs', 'public');
            $orig = $request->file('attachment')->getClientOriginalName();
        }

        $application = DB::transaction(function () use ($data, $tg, $path, $orig) {
            $app = CareerApplication::create([
                'type' => CareerApplication::TYPE_INQUIRY,
                'vacancy_id' => null,
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'telegram_username' => $tg,
                'cover_message' => $data['message'],
                'cv_path' => $path,
                'cv_original_name' => $orig,
                'status' => CareerApplication::STATUS_NEW,
            ]);

            CareerApplicationMessage::create([
                'career_application_id' => $app->id,
                'admin_id' => null,
                'sender' => CareerApplicationMessage::SENDER_SYSTEM,
                'body' => 'Ochiq murojaat (vakansiyasiz) sayt orqali yuborildi.',
            ]);

            return $app;
        });

        $this->trySendAck($application);

        return redirect()
            ->route('careers.index', ['inquiry' => 1])
            ->with('career_success', __('careers.flash.inquiry_sent'));
    }

    private function trySendAck(CareerApplication $application): void
    {
        try {
            Mail::to($application->email)->send(new CareerApplicationAcknowledgmentMail($application));
        } catch (\Throwable $e) {
            Log::warning('CareerApplicationAcknowledgmentMail failed', [
                'id' => $application->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
