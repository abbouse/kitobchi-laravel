<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PrizeGameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/** Ilova: "Sovg'alar g'ildiragi". */
class PrizeGameController extends Controller
{
    public function __construct(private PrizeGameService $game) {}

    public function state(): JsonResponse
    {
        return response()->json(['status' => 'success', 'data' => $this->game->state(Auth::guard('user')->user())]);
    }

    public function spin(): JsonResponse
    {
        $res = $this->game->spin(Auth::guard('user')->user());

        return $this->respond($res);
    }

    public function daily(): JsonResponse
    {
        return $this->respond($this->game->claimDaily((int) Auth::guard('user')->id()));
    }

    public function claimTask(string $key): JsonResponse
    {
        return $this->respond($this->game->claimTask((int) Auth::guard('user')->id(), $key));
    }

    public function rewards(): JsonResponse
    {
        return response()->json(['status' => 'success', 'data' => $this->game->rewards((int) Auth::guard('user')->id())]);
    }

    private function respond(array $res): JsonResponse
    {
        if (! ($res['ok'] ?? false)) {
            return response()->json(['status' => 'error', 'message' => $res['error'] ?? 'Xatolik', 'data' => $res], 422);
        }

        return response()->json(['status' => 'success', 'data' => $res]);
    }
}
