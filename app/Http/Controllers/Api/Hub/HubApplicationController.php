<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Hub;

use App\Http\Controllers\Controller;
use App\Models\HubApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Ochiq (login talab qilmaydi) ariza qabul qilish. Istagan odam ilovani
 * yuklab, xohlagan lavozimiga ariza qoldirishi mumkin.
 */
class HubApplicationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:32'],
            'region' => ['required', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:120'],
            'tashkent_availability' => ['required', Rule::in(HubApplication::TASHKENT_OPTIONS)],
        ]);

        $data['status'] = HubApplication::STATUS_NEW;

        $application = HubApplication::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Arizangiz qabul qilindi. Tez orada bog\'lanamiz.',
            'data' => ['id' => $application->id],
        ], 201);
    }
}
