<?php

namespace TFD\Cloudinary;

use Statamic\Providers\AddonServiceProvider;
use TFD\Cloudinary\Tags\Cloudinary;

class ServiceProvider extends AddonServiceProvider
{
    protected $tags = [
        Cloudinary::class,
    ];

    public function boot()
    {
        parent::boot();

        $this->bootAddonConfig();

        $this->publishes([
            __DIR__.'/../config/cloudinary.php' => config_path('statamic/cloudinary.php'),
        ], 'cloudinary-config');
    }

    protected function bootAddonConfig()
    {
    }
}
