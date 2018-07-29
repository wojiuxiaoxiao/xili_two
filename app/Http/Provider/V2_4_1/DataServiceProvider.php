<?php

namespace App\Http\Provider\V2_4_1;

use App\Http\Provider\CommonService;
use Illuminate\Support\ServiceProvider;

class DataServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('InterlocutionService',function(){
            return new InterlocutionService();

        });
        $this->app->bind('UserCenterService',function(){
            return new UserCenterService();

        });
        $this->app->bind('AnswerService', function () {
           return new AnswerService();
        });
        $this->app->bind('CommonService', function () {
            return new CommonService();
        });
    }
}
