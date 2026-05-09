<?php

namespace App\Observers;

use App\Models\Books;
use App\Services\ProductStockAlertService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class BookStockObserver implements ShouldHandleEventsAfterCommit
{
    public function saved(Books $book): void
    {
        app(ProductStockAlertService::class)->notifyForBook($book);
    }
}
