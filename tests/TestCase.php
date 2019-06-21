<?php

namespace BlackBits\ApiConsumer\Tests;

use BlackBits\ApiConsumer\ApiConsumer;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{


    protected function setUp()
    {
        parent::setUp();

        $app = new Container();
        $app->singleton('app', Container::class);
        $app->singleton('config', Repository::class);

        $app['config']->set('filesystems.default', 'local');
        $app['config']->set('filesystems.default.local', [
            'driver' => 'local',
            'root' => __DIR__.'/../storage',
        ]);


//        $app['config']->set('database.default', 'test');
//        $app['config']->set('database.connections.test', [
//            'driver' => 'sqlite',
//            'database' => ':memory:'
//        ]);
//        $app->bind('db', function($app){
//            return new DatabaseManager
//        })


    }


}
