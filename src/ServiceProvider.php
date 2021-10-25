<?php

namespace TFD\Cloudinary;

use Illuminate\Support\Facades\Blade;
use Statamic\Providers\AddonServiceProvider;
use TFD\Cloudinary\Components\Image;
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
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/cloudinary'),
        ], 'cloudinary-views');


        Blade::component('image', Image::class);
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'cloudinary');
        $this->loadViewComponentsAs('cloudinary', [
            Image::class,
        ]);
    }

    protected function bootAddonConfig()
    {
    }
}
