<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ContractGenerator;
use App\Support\Contracts\ContractTemplates;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/** Saytdagi "Huquqiy hujjatlar" bo'limi: shartnoma namunalarini bugungi sana bilan yuklab olish. */
class ContractController extends Controller
{
    public function __construct(private ContractGenerator $contracts)
    {
    }

    public function index(): JsonResponse
    {
        $items = array_map(fn ($t) => $t + [
            'download_url' => url('/api/v1/kitobchi/contracts/'.$t['slug'].'/download'),
        ], $this->contracts->publicTemplates());

        return response()->json(['status' => 'success', 'data' => $items]);
    }

    public function download(string $slug): Response
    {
        if (! ContractTemplates::get($slug) || ! $this->contracts->isPublic($slug)) {
            abort(404);
        }
        $date = CarbonImmutable::now('Asia/Tashkent');
        $docx = $this->contracts->render($slug, $this->contracts->values($slug, $date));

        return response($docx, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="'.$this->contracts->filename($slug, $date).'"',
            'Cache-Control' => 'no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
