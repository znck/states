<?php
namespace Znck\States;

use Illuminate\Support\ServiceProvider;

class StatesServiceProvider extends ServiceProvider
{
    protected $defer = true;

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->publishes([
            dirname(__DIR__).'/config/states.php' => config_path('states.php'),
        ]);
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/config/states.php', 'states');
        $this->app->singleton('command.states.update', UpdateStatesCommand::class);
        $this->app->singleton(
            'translator.states',
            function () {
                $locale = $this->app['config']['app.locale'];

                $loader = new FileLoader($this->app['files'], dirname(__DIR__).'/data');

                $trans = new Translator($loader, $locale);

                return $trans;
            }
        );
        $this->commands('command.states.update');
    }

    public function provides()
    {
        return ['translator.states', 'command.states.update'];
    }
}
