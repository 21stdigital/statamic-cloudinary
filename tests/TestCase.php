<?php

declare(strict_types=1);
namespace Tests;

use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('statamic.cloudinary', require dirname(__DIR__).'/config/cloudinary.php');
    }
}
