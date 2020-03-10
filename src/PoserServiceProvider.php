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
        $this->registerHelpers();
        $this->publishes([__DIR__ . '/config/poser.php' => config_path('poser.php')], 'poser');

        if ($this->app->runningInConsole()) {
            $this->commands([CreatePoserFactory::class]);
        }
    }

    protected function registerHelpers()
    {
        include_once __DIR__ . "/helpers.php";
    }
}
