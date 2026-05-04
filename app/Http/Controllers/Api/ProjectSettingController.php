<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProjectSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProjectSettingController extends Controller
{
    public function getVersions()
    {
        $s = ProjectSetting::findOrFail(1);

        return response()->json([
            'status' => 'success',
            'ok'     => true,
            'data'   => [[
                'business_version_ios'     => $s->business_version_ios,
                'business_version_android' => $s->business_version_android,
                'courier_version_ios'      => $s->courier_version_ios,
                'courier_version_android'  => $s->courier_version_android,
                'market_version_ios'       => $s->market_version_ios,
                'market_version_android'   => $s->market_version_android,
                'contacts' => [
                    'kitobchi' => [
                        'phone' => $s->kitobchi_phone,
                        'email' => $s->kitobchi_email,
                    ],
                    'business' => [
                        'phone' => $s->business_phone,
                        'email' => $s->business_email,
                    ],
                    'courier' => [
                        'phone' => $s->courier_phone,
                        'email' => $s->courier_email,
                    ],
                ],
                'telegram' => [
                    'enabled' => (bool) ($s->telegram_login_enabled ?? false),
                    'client_id' => $s->telegram_client_id,
                    'redirect_uri_ios' => $s->telegram_redirect_uri_ios ?: 'https://app8515375616-login.tg.dev',
                    'redirect_uri_android' => $s->telegram_redirect_uri_android ?: 'https://app8515375616-login.tg.dev',
                    'scopes' => $s->telegram_scopes ?: 'openid profile phone',
                ],
            ]],
        ], 200);
    }
}
