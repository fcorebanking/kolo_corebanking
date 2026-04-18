<?php
namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

//use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     * ghjvjhhj
     *
     * @return void
     */
    public function register()
    {

        //  Passport::ignoreMigrations();
        //  Passport::ignoreRoutes();

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function cando($permission)
    {
        echo $permission;
    }
    public function boot()
    {

        Schema::defaultStringLength(191);

    }
}
