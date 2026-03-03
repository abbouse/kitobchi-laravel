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
    $version = ProjectSetting::findOrfail(1)->get();
        return response()->json(['status' => 'success', 'data' => $version], 201);
}
}