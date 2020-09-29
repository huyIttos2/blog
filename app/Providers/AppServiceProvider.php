<?php

namespace App\Providers;

use App\Http\ViewComposers\CategoriesAllComposer;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        view()->composer('layouts.frontend.partial.footer', CategoriesAllComposer::class);
    }
}
