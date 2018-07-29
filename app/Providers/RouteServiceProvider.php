<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        //

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        $this->mapWebRoutes();

        $this->mapYunshuiv1Routes();

        $this->mapYunshuiv2Routes();

        $this->mapYunshuiv2_1Routes();

        $this->mapYunshuiv2_1_1Routes();

        $this->mapYunshuiv2_2Routes();

        $this->mapYunshuiv2_3Routes();

        $this->mapYunshuiv2_4Routes();

        $this->mapYunshuiv2_4_1Routes();

        $this->mapYunshuiv2_5Routes();
        //
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
             ->namespace($this->namespace)
             ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
             ->middleware('api')
             ->namespace($this->namespace)
             ->group(base_path('routes/api.php'));
    }

    /**
     * Define the "yunshuiv1" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapYunshuiv1Routes()
    {
        Route::middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/yunshuiv1.php'));
    }

    /**
     * Define the "yunshuiv2" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapYunshuiv2Routes()
    {
        Route::middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/yunshuiv2.php'));
    }

    /**
     * Define the "yunshuiv2.1" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapYunshuiv2_1Routes()
    {
        Route::middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/yunshuiv2_1.php'));
    }

    /**
     * Define the "yunshuiv2.1.1" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapYunshuiv2_1_1Routes()
    {
        Route::middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/yunshuiv2_1_1.php'));
    }

    /**
     * Define the "yunshuiv2.2" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapYunshuiv2_2Routes()
    {
        Route::middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/yunshuiv2_2.php'));
    }

    /**
     * Define the "yunshuiv2.3" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapYunshuiv2_3Routes()
    {
        Route::middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/yunshuiv2_3.php'));
    }

    /**
     * Define the "yunshuiv2.4" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapYunshuiv2_4Routes()
    {
        Route::middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/yunshuiv2_4.php'));
    }

    /**
     * Define the "yunshuiv2.4.1" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapYunshuiv2_4_1Routes()
    {
        Route::middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/yunshuiv2_4_1.php'));
    }

    /**
     * Define the "yunshuiv2.5" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapYunshuiv2_5Routes()
    {
        Route::middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/yunshuiv2_5.php'));
    }


}
