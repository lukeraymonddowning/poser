<?php

namespace Lukeraymonddowning\Poser;

use Illuminate\Support\ServiceProvider;

class PoserServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/poser.php',
            'poser'
        );
    }

    public function boot()
    {
        $this->publishes([__DIR__ . '/config/poser.php' => config_path('poser.php')], 'poser');

        if ($this->app->runningInConsole()) {
            $this->commands([CreatePoserFactory::class]);
        }
    }
}
