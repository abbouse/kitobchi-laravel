<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use MoonShine\Contracts\Core\DependencyInjection\ConfiguratorContract;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Laravel\DependencyInjection\MoonShine;
use MoonShine\Laravel\DependencyInjection\MoonShineConfigurator;
use MoonShine\Contracts\MoonShineAuthContract;

class MoonShineServiceProvider extends ServiceProvider
{
    public function boot(
        CoreContract $core,
        ConfiguratorContract $config,
    ): void
    {
        $core->autoload();
    }
}