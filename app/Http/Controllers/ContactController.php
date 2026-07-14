<?php

namespace App\Http\Controllers;

use App\Mail\ContactInquiryMail;
use App\Models\ProjectSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function index()
    {
        return view('contact.index', ['contacts' => $this->contacts()]);
    }

    public function store(Request $request)
    {
        // Honeypot — botlar to'ldiradigan yashirin maydon
        if (trim((string) $request->input('website', '')) !== '') {
            return redirect()->route('contact.index');
        }

        $data = $request->validate([
            'full_name' => 'required|string|max:120',
            'phone' => 'required|string|max:32',
            'email' => 'nullable|email:rfc|max:255',
            'topic' => 'required|in:kitobchi,business,express,other',
            'message' => 'required|string|min:10|max:6000',
        ], trans('contact.validation'), trans('contact.attributes'));

        try {
            Mail::to(config('services.contact_inbox'))
                ->send(new ContactInquiryMail(
                    fullName: (string) $data['full_name'],
                    phone: (string) $data['phone'],
                    email: (string) ($data['email'] ?? ''),
                    topic: (string) $data['topic'],
                    messageBody: (string) $data['message'],
                    locale: app()->getLocale(),
                ));
        } catch (\Throwable $error) {
            // Xabar yo'qolmasin — to'liq log qilamiz
            Log::error('[Contact] Murojaat emaili yuborilmadi', [
                'error' => $error->getMessage(),
                'payload' => $data,
            ]);

            return back()->withInput()->with('contact_error', __('contact.flash.failed'));
        }

        return redirect()
            ->route('contact.index')
            ->with('contact_success', __('contact.flash.sent'));
    }

    /**
     * Boshqaruv sozlamalaridagi (ProjectSetting) rasmiy kontaktlar —
     * har bir ilovaga xizmat qiladigan raqam va emaillar.
     */
    private function contacts(): array
    {
        $settings = ProjectSetting::query()->first();

        return [
            'kitobchi' => [
                'phone' => (string) ($settings?->kitobchi_phone ?? ''),
                'email' => (string) ($settings?->kitobchi_email ?? ''),
            ],
            'business' => [
                'phone' => (string) ($settings?->business_phone ?? ''),
                'email' => (string) ($settings?->business_email ?? ''),
            ],
            'express' => [
                'phone' => (string) ($settings?->courier_phone ?? ''),
                'email' => (string) ($settings?->courier_email ?? ''),
            ],
        ];
    }
}
