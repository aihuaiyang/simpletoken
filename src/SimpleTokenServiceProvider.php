<?php

namespace Huaiyang\SimpleToken;

use Illuminate\Support\ServiceProvider;

class SimpleTokenServiceProvider extends ServiceProvider
{

    protected $defer = true;

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //配置文件
        $path = realpath(__DIR__.'/config/simpletoken.php');

        $this->publishes([$path => config_path('simpletoken.php')]);
        $this->mergeConfigFrom($path, 'simpletoken');

    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->app->singleton('simpletoken', function () {
            return new SimpleToken();
        });
    }

    public function provides()
    {
        return ['simpletoken'];
    }
}
