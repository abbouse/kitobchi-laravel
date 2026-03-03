<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SergiX44\Nutgram\Nutgram;

class FrontController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Nutgram $bot): void
    {
        try {
            $bot->run();
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
        }
    }
}