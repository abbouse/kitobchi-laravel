<?php

namespace App\Observers;

use App\Services\UserPositionService;
use Illuminate\Support\Facades\DB;

/**
 * Klub faolligi (post, izoh, layk, sevimli) o'zgarganda daraja va nishonlarni
 * qayta tekshiradi. Layk uchun LAYK OLGAN odam ham tekshiriladi ("Klub yulduzi").
 */
class UserProgressObserver
{
    public function __construct(
        private readonly UserPositionService $positionService,
    ) {}

    public function created($model): void
    {
        $this->evaluate($model, ':created');
    }

    public function deleted($model): void
    {
        $this->evaluate($model, ':deleted');
    }

    private function evaluate($model, string $suffix): void
    {
        $ids = [(int) ($model->user_id ?? 0)];
        try {
            if ($model instanceof \App\Models\BookClubLikes && $model->post_id) {
                $ids[] = (int) DB::table('book_club')->where('id', $model->post_id)->value('user_id');
            } elseif ($model instanceof \App\Models\BookClubCommentLike && $model->comment_id) {
                $ids[] = (int) DB::table('book_club_comments')->where('id', $model->comment_id)->value('user_id');
            }
        } catch (\Throwable) {
        }

        foreach (array_unique(array_filter($ids)) as $userId) {
            if ($user = \App\Models\User::find($userId)) {
                $this->positionService->evaluateAndPromote($user, class_basename($model).$suffix);
            }
        }
    }
}
