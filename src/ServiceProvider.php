<?php

declare(strict_types=1);

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

    /**
     * Use 'cloudinary' so the parent's bootViews() loads addon views under this namespace.
     */
    protected $viewNamespace = 'cloudinary';

    /**
     * Disable parent's default config (merge/publish to config/cloudinary.php).
     * We use config('statamic.cloudinary') and publish to config/statamic/cloudinary.php in bootAddon().
     */
    protected $config = false;

    /**
     * Boot the addon. This runs after Statamic has booted (called from the parent's
     * Statamic::booted() callback). Prefer bootAddon() over overriding boot() so
     * that addon logic runs in the correct lifecycle.
     */
    public function bootAddon(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/cloudinary.php',
            'statamic.cloudinary'
        );

        $this->publishes([
            __DIR__.'/../config/cloudinary.php' => config_path('statamic/cloudinary.php'),
        ], 'cloudinary-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/cloudinary'),
        ], 'cloudinary-views');

        Blade::component('image', Image::class);
        Blade::component('cloudinary-image', Image::class);
        $this->loadViewComponentsAs('cloudinary-image', [
            Image::class,
        ]);
    }
}
