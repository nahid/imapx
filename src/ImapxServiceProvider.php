<?php

namespace Nahidz\Imapx;

use Illuminate\Support\ServiceProvider;

class ImapxServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
          __DIR__.'/config' => base_path('config'),
      ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->make('Nahidz\Imapx\Imapx');
        $this->mergeConfigFrom(
       __DIR__.'/config/imapx.php', 'imapx'
        );
    }

    public function provides()
    {
        return ['Nahidz\Imapx\ImapxServiceProvider'];
    }
}
