<?php

namespace App\Observers;

use App\Services\UserPositionService;

class UserProgressObserver
{
    public function __construct(
        private readonly UserPositionService $positionService,
    ) {}

    public function created($model): void
    {
        $userId = (int) ($model->user_id ?? 0);
        if ($userId > 0 && ($user = \App\Models\User::find($userId))) {
            $this->positionService->evaluateAndPromote($user, class_basename($model) . ':created');
        }
    }

    public function deleted($model): void
    {
        $userId = (int) ($model->user_id ?? 0);
        if ($userId > 0 && ($user = \App\Models\User::find($userId))) {
            $this->positionService->evaluateAndPromote($user, class_basename($model) . ':deleted');
        }
    }
}
