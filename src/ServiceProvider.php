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

        $this->
            bootAddonConfig();
    }

    protected function bootAddonConfig()
    {
    }
}
