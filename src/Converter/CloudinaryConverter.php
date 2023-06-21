<?php

namespace TFD\Cloudinary\Converter;

use Illuminate\Support\Collection;
use Statamic\Support\Str;
use Statamic\Assets\Asset as AssetsAsset;
use Statamic\Facades\Asset;
use Illuminate\Support\ItemNotFoundException;

class CloudinaryConverter
{
    protected static $config = null;

    /**
     * @var Collection
     */
    protected $configuration;

    /**
     * @see https://cloudinary.com/documentation/transformation_reference
     *
     * @var array|Collection
     */
    protected $cloudinary_params = [
        'angle' => 'a',
        'aspect_ratio' => 'ar',
        'background' => 'b',
        'border' => 'bo',
        'crop' => 'c',
        'color' => 'co',
        'dpr' => 'dpr',
        'duration' => 'du',
        'effect' => 'e',
        'end_offset' => 'eo',
        'flags' => 'fl',
        'height' => 'h',
        'overlay' => 'l',
        'opacity' => 'o',
        'quality' => 'q',
        'radius' => 'r',
        'start_offset' => 'so',
        'named_transformation' => 't',
        'underlay' => 'u',
        'video_codec' => 'vc',
        'width' => 'w',
        'x' => 'x',
        'y' => 'y',
        'zoom' => 'z',
        'audio_codec' => 'ac',
        'audio_frequency' => 'af',
        'bit_rate' => 'br',
        'color_space' => 'cs',
        'default_image' => 'd',
        'delay' => 'dl',
        'density' => 'dn',
        'fetch_format' => 'f',
        'gravity' => 'g',
        'prefix' => 'p',
        'page' => 'pg',
        'video_sampling' => 'vs',
        'progressive' => 'fl_progressive',
    ];

    /**
     * @var Collection
     */
    protected $default_transformations;

    /**
     * @var string
     */
    protected $asset_type;

    public function __construct()
    {
        $this->setCloudinaryParams();
        $this->setConfiguration();
        $this->setDefaultTransformations();
    }

    protected function setCloudinaryParams()
    {
        $this->cloudinary_params = collect($this->cloudinary_params);
    }

    protected function setConfiguration()
    {
        $this->configuration = collect([
            'cloud_name' => config('statamic.cloudinary.cloud_name'),
            'cloudinary_upload_url' => config('statamic.cloudinary.upload_url'),
            'auto_mapping_folder' => config('statamic.cloudinary.auto_mapping_folder'),
            'api_key' => config('statamic.cloudinary.api_key'),
            'api_secret' => config('statamic.cloudinary.api_secret'),
            'upload_preset' => config('statamic.cloudinary.upload_preset'),
            'notification_url' => config('statamic.cloudinary.notification_url'),
            'cloudinary_url' => config('statamic.cloudinary.url'),
            'cloudinary_delivery_type' => config('statamic.cloudinary.delivery_type'),
        ]);
    }

    protected function setDefaultTransformations()
    {
        $this->default_transformations = collect(config('statamic.cloudinary.default_transformations'));
    }

    public function setParams($params)
    {
        $this->params = $params;
    }

    public function hasValidConfiguration()
    {
        return $this->configuration->has('cloud_name') &&
            $this->configuration->get('cloud_name') &&
            $this->configuration->has('cloudinary_upload_url') &&
            $this->configuration->get('cloudinary_upload_url');
    }

    public function baseUrl($item)
    {
        return Str::ensureRight($this->configuration->get('cloudinary_upload_url'), '/') .
            Str::ensureRight($this->configuration->get('cloud_name'), '/') .
            $this->getAssetType() .
            '/' .
            $this->getDeliveryType() .
            '/';
    }

    /**
     * @see https://cloudinary.com/documentation/image_transformations#transformation_url_structure
     *
     * @param $item
     * @return void
     */
    public function setAssetType($item)
    {
        $item = $this->getAsset($item);

        if ($item->isImage()) {
            $this->asset_type = 'image';
        } elseif ($item->isVideo() || $item->isAudio()) {
            $this->asset_type = 'video';
        } else {
            $this->asset_type = 'raw';
        }
    }

    public function getAssetType()
    {
        return $this->asset_type;
    }

    public function getAsset($item)
    {
        if ($item instanceof AssetsAsset) {
            return $item;
        }

        if ($asset = Asset::findByUrl(Str::ensureLeft($item, '/'))) {
            return $asset;
        }

        if ($asset = Asset::find($item)) {
            return $asset;
        }

        throw new ItemNotFoundException('Asset not found for ' . gettype($item));
    }

    private function getDeliveryType()
    {
        return $this->configuration->get('cloudinary_delivery_type');
    }

    /**
     * The URL generation.
     *
     * @param  string $item  Either the ID or path of the image.
     * @return string
     */
    public function generateCloudinaryUrl($item)
    {
        $item = $this->getAsset($item);
        $url = $this->normalizeItem($item);
        $manipulation_params = $this->getManipulationParams($item);
        $meta_params = $this->getMetaParams($item);

        $combined_params = $meta_params->merge($manipulation_params);

        // Transformations.
        if ($this->hasCombinedManipulationParams()) {
            $transformations_slug = $this->buildTransformationSlug($combined_params);

            if (!empty($transformations_slug)) {
                $url = "{$transformations_slug}/{$url}";
            }
        }

        return $this->baseUrl($item) . $url;
    }
    /**
     * The URL generation.
     *
     * @param  string $item  Either the ID or path of the image.
     * @return string
     */
    public function generateKenBurnsUrl($item)
    {
        $item = $this->getAsset($item);
        $url = $this->normalizeItem($item);
        $url = str_replace(['.jpg', '.jpeg', '.png'], '.mp4', $url);
        $transformations_slug =
            'q_auto,w_1920/e_zoompan:du_5;from_(g_auto;zoom_4);to_(g_auto;zoom_1.6)/e_boomerang/q_auto';

        $url = "{$transformations_slug}/{$url}";

        return $this->baseUrl($item) . $url;
    }

    private function hasCombinedManipulationParams()
    {
        return $this->getManipulationParams()->isNotEmpty() || $this->default_transformations->isNotEmpty();
    }

    private function getMetaParams($item)
    {
        $params = collect();

        // Set focus related parameters.
        if ($item->get('focus')) {
            [$x_percentage, $y_percentage, $zoom] = explode('-', $item->get('focus'));

            $x = floor(($x_percentage / 100) * $this->getOriginalWidth($item));
            $y = floor(($y_percentage / 100) * $this->getOriginalHeight($item));
            $zoom = floor($zoom * 10) / 10;

            $params->put('x', $x);
            $params->put('y', $y);
            $params->put('zoom', $zoom);
            $params->put('gravity', 'xy_center');
        }

        return $params;
    }

    /**
     * Get the tag parameters applicable to image manipulation.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getManipulationParams()
    {
        $params = collect();

        foreach ($this->params as $param => $value) {
            if (!in_array($param, ['src', 'id', 'path', 'tag', 'alt'])) {
                switch ($param) {
                    case 'format':
                        $params->put('fetch_format', $value);
                        break;

                    case 'square':
                        $params->put('width', $value);
                        $params->put('aspect_ratio', '1:1');
                        break;

                    case 'fit':
                        switch ($value) {
                            case 'crop_focal':
                                $params->put('crop', 'fill');
                                break;

                            case 'contain':
                                $params->put('crop', 'fit');
                                break;

                            case 'max':
                                $params->put('crop', 'limit');
                                break;

                            case 'stretch':
                                $params->put('crop', 'scale');
                                break;
                        }
                        break;

                    default:
                        $params->put($param, $value);
                        break;
                }
            }
        }

        return $params;
    }

    private function getOriginalWidth(AssetsAsset $item)
    {
        return $item->meta('width');
    }

    private function getOriginalHeight(AssetsAsset $item)
    {
        return $item->meta('height');
    }

    private function getOriginalAspectRatio(AssetsAsset $item)
    {
        $width = $this->getOriginalWidth($item);
        $height = $this->getOriginalHeight($item);

        if (!$width || !$height) {
            return 1;
        }

        return $width / $height;
    }

    public function getFinalDimensions($item)
    {
        $width = (int) $this->getManipulationParams()->get('width');
        $height = (int) $this->getManipulationParams()->get('height');
        $aspect_ratio =
            (int) $this->getManipulationParams()->get('aspect_ratio') ?: $this->getOriginalAspectRatio($item);

        if ($width && $height) {
            return [$width, $height];
        }

        if ($width) {
            return [$width, round($width / $aspect_ratio)];
        }

        if ($height) {
            return [round($height * $aspect_ratio), $height];
        }

        return [0, 0];
    }

    /**
     * Normalize an item to be passed into the manipulator.
     *
     * @param  string $item  An asset ID, asset URL, or external URL.
     * @return string|Statamic\Contracts\Assets\Asset
     */
    private function normalizeItem($item)
    {
        // Double colons indicate an asset ID.
        if (Str::contains($item, '::')) {
            $item = Asset::find($item);
        }

        $item = Str::ensureLeft(Str::removeLeft($item, '/'), $this->configuration->get('auto_mapping_folder'));

        return $item;
    }

    /**
     * The list of allowed file formats based on the configured driver.
     *
     * @return array
     */
    private function allowedFileFormats()
    {
        return ['jpeg', 'jpg', 'png', 'gif', 'tif', 'bmp', 'psd', 'webp'];
    }

    /**
     * Build a Cloudinary transformation slug from arguments.
     *
     * @param  array $args
     * @return string
     */
    public function buildTransformationSlug($args = null)
    {
        if (!$args || !$args instanceof Collection) {
            return '';
        }

        $default_transformations = $this->getDefaultTransformationByType($this->getAssetType());
        $args = collect($default_transformations)->merge($args);

        if ($args->get('gravity') && $args->get('crop') === 'fit') {
            $args->forget('gravity');
        }

        $fetch_format = null;
        if ($args->get('fetch_format')) {
            $fetch_format = $this->cloudinary_params->get('fetch_format') . '_' . $args->get('fetch_format');
            $args->forget('fetch_format');
        }

        $quality = null;
        if ($args->get('quality')) {
            $quality = $this->cloudinary_params->get('quality') . '_' . $args->get('quality');
            $args->forget('quality');
        }

        $slug = $args
            ->map(function ($value, $key) {
                if (
                    $this->cloudinary_params->has($key) &&
                    $this->isValidCloudinaryParam($this->cloudinary_params->get($key), $value)
                ) {
                    switch ($key) {
                        case 'progressive':
                            if (true === $value) {
                                return $this->cloudinary_params->get($key);
                            } else {
                                return $this->cloudinary_params->get($key) . ':' . $value;
                            }
                            break;
                        default:
                            return $this->cloudinary_params->get($key) . '_' . $value;
                    }
                } else {
                    Log::warning('Unknown Cloudinary parameter [' . $key . '] for asset ');
                }
            })
            ->filter();

        $transformation_slug = $slug->implode(urlencode(','));
        if ($quality) {
            $transformation_slug = $transformation_slug . '/' . $quality;
        }
        if ($fetch_format) {
            $transformation_slug = $transformation_slug . '/' . $fetch_format;
        }

        return $transformation_slug;
    }

    public function getDefaultTransformationByType($type)
    {
        return $this->default_transformations->get($type);
    }

    /**
     * Check if the value is valid.
     *
     * @param string $key
     * @param string $value
     * @return bool
     */
    public function isValidCloudinaryParam($key = '', $value = '')
    {
        if (('w' === $key || 'h' === $key) && empty($value)) {
            return false;
        }

        return true;
    }
}
