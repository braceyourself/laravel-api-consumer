<?php

namespace BlackBits\ApiConsumer;

use BlackBits\ApiConsumer\Commands\ApiConsumerEndpointMakeCommand;
use BlackBits\ApiConsumer\Commands\ApiConsumerMakeCollectionCallback;
use BlackBits\ApiConsumer\Commands\ApiConsumerMakeCommand;
use BlackBits\ApiConsumer\Commands\ApiConsumerShapeMakeCommand;
use BlackBits\ApiConsumer\Support\ApiResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class ApiConsumerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/api-consumers.php' => config_path('api-consumers.php'),
            ], 'config');

            $this->commands([
                ApiConsumerMakeCommand::class,
                ApiConsumerEndpointMakeCommand::class,
                ApiConsumerShapeMakeCommand::class,
                ApiConsumerMakeCollectionCallback::class
            ]);
        }


        Collection::macro('validate', function(array $rules){
//            /** @var $col Collection */
//            $col = $this;
//            return $col->values(function(){
//
//            });
        });
        ApiResponse::macro('validate', function(array $rules, array $messages = []){
            return Validator::make($this->data(), $rules, $messages)->validate();
        });
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/api-consumers.php', 'api-consumers');
    }
}
