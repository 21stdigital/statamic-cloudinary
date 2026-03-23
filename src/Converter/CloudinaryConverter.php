<?php

declare(strict_types=1);

namespace TFD\Cloudinary\Converter;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ItemNotFoundException;
use Statamic\Assets\Asset as AssetsAsset;
use Statamic\Facades\Asset;
use Statamic\Support\Str;

class CloudinaryConverter
{
    /**
     * @var Collection<string, mixed>
     */
    protected Collection $configuration;

    /**
     * @var array<string, mixed>
     */
    protected array $params = [];

    /**
     * @see https://cloudinary.com/documentation/transformation_reference
     *
     * @var Collection<string, string>
     */
    protected Collection $cloudinary_params;

    /**
     * @var Collection<string, mixed>
     */
    protected Collection $default_transformations;

    protected string $asset_type = '';

    public function __construct()
    {
        $this->setCloudinaryParams();
        $this->setConfiguration();
        $this->setDefaultTransformations();
    }

    protected function setCloudinaryParams(): void
    {
        $this->cloudinary_params = collect([
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
            'format' => 'f',
            'gravity' => 'g',
            'prefix' => 'p',
            'page' => 'pg',
            'video_sampling' => 'vs',
            'progressive' => 'fl_progressive',
        ]);
    }

    protected function setConfiguration(): void
    {
        $this->configuration = collect([
            'cloud_name' => config('statamic.cloudinary.cloud_name'),
            'cloudinary_upload_url' => config('statamic.cloudinary.upload_url'),
            'auto_mapping_folder' => Str::finish((string) config('statamic.cloudinary.auto_mapping_folder'), '/'),
            'api_key' => config('statamic.cloudinary.api_key'),
            'api_secret' => config('statamic.cloudinary.api_secret'),
            'upload_preset' => config('statamic.cloudinary.upload_preset'),
            'notification_url' => config('statamic.cloudinary.notification_url'),
            'cloudinary_url' => config('statamic.cloudinary.url'),
            'cloudinary_delivery_type' => config('statamic.cloudinary.delivery_type'),
            'external_url_prefix' => config('statamic.cloudinary.external_url_prefix'),
        ]);
    }

    protected function setDefaultTransformations(): void
    {
        /** @var array<string, mixed>|null $defaults */
        $defaults = config('statamic.cloudinary.default_transformations');
        $this->default_transformations = collect($defaults ?? []);
    }

    /**
     * @param  array<string, mixed>|Collection<string, mixed>|iterable<string, mixed>  $params
     */
    public function setParams(mixed $params): void
    {
        if ($params instanceof Collection) {
            $this->params = $params->all();
        } elseif (is_array($params)) {
            $this->params = $params;
        } elseif (is_iterable($params)) {
            $this->params = iterator_to_array($params);
        } else {
            $this->params = [];
        }
    }

    public function hasValidConfiguration(): bool
    {
        return
            $this->configuration->has('cloud_name') &&
            (bool) $this->configuration->get('cloud_name') &&
            $this->configuration->has('cloudinary_upload_url') &&
            (bool) $this->configuration->get('cloudinary_upload_url');
    }

    public function baseUrl(): string
    {
        return
            Str::ensureRight((string) $this->configuration->get('cloudinary_upload_url'), '/').
            Str::ensureRight((string) $this->configuration->get('cloud_name'), '/').
            Str::ensureRight($this->getAssetType(), '/').
            Str::ensureRight((string) $this->getDeliveryType(), '/');
    }

    /**
     * @see https://cloudinary.com/documentation/image_transformations#transformation_url_structure
     */
    public function setAssetType(mixed $item): void
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

    public function getAssetType(): string
    {
        return $this->asset_type;
    }

    public function getAsset(mixed $item): AssetsAsset
    {
        if ($item instanceof AssetsAsset) {
            return $item;
        }

        if ($asset = Asset::findByUrl(Str::ensureLeft((string) $item, '/'))) {
            assert($asset instanceof AssetsAsset);

            return $asset;
        }

        if ($asset = Asset::find($item)) {
            assert($asset instanceof AssetsAsset);

            return $asset;
        }

        throw new ItemNotFoundException('Asset not found for '.gettype($item));
    }

    private function getDeliveryType(): mixed
    {
        return $this->configuration->get('cloudinary_delivery_type');
    }

    /**
     * The URL generation.
     *
     * @param  string|AssetsAsset  $item  Either the ID or path of the image.
     */
    public function generateCloudinaryUrl(mixed $item): string
    {
        $item = $this->getAsset($item);
        $url = $this->normalizeItem($item);
        $manipulation_params = $this->getManipulationParams();
        $meta_params = $this->getMetaParams($item);

        $combined_params = $meta_params->merge($manipulation_params);

        // Transformations.
        if ($this->hasCombinedManipulationParams()) {
            $transformations_slug = $this->buildTransformationSlug($combined_params);

            if ($transformations_slug !== '') {
                $url = "{$transformations_slug}/{$url}";
            }
        }

        return $this->baseUrl().$url;
    }

    /**
     * The URL generation.
     *
     * @param  string|AssetsAsset  $item  Either the ID or path of the image.
     */
    public function generateKenBurnsUrl(mixed $item): string
    {
        $item = $this->getAsset($item);
        $url = $this->normalizeItem($item);
        $url = str_replace(['.jpg', '.jpeg', '.png'], '.mp4', $url);
        $transformations_slug =
            'q_auto,w_1920/e_zoompan:du_5;from_(g_auto;zoom_4);to_(g_auto;zoom_1.6)/e_boomerang/q_auto';

        $url = "{$transformations_slug}/{$url}";

        return $this->baseUrl().$url;
    }

    private function hasCombinedManipulationParams(): bool
    {
        return $this->getManipulationParams()->isNotEmpty() || $this->default_transformations->isNotEmpty();
    }

    private function getMetaParams(AssetsAsset $item): Collection
    {
        $params = collect();
        $manipulation_params = $this->getManipulationParams();

        // Set focus related parameters.
        $focus = $item->get('focus');
        if (is_string($focus) && $focus !== '') {
            $parts = explode('-', $focus);
            if (count($parts) === 3) {
                [$x_percentage, $y_percentage, $zoomStr] = $parts;
                if (is_numeric($x_percentage) && is_numeric($y_percentage) && is_numeric($zoomStr)) {
                    $xPct = (float) $x_percentage;
                    $yPct = (float) $y_percentage;
                    $zoom = (float) $zoomStr;

                    $x = (int) floor(($xPct / 100.0) * $this->getOriginalWidth($item));
                    $y = (int) floor(($yPct / 100.0) * $this->getOriginalHeight($item));
                    $zoom = floor($zoom * 10.0) / 10.0;

                    $params->put('x', $x);
                    $params->put('y', $y);
                    $params->put('zoom', $zoom);
                    $params->put('gravity', 'xy_center');
                }
            }
        } elseif ($manipulation_params->has('crop') && $manipulation_params->get('crop') === 'fill') {
            /**
             * When using `fit="crop_focal"` in statamic and NOT defining a focal point, the asset is cropped by glide from the center point.
             * In contrary, when using cloudinary, this is not the case.
             * The following code replicates this behavior.
             */
            $x = (int) floor(0.5 * $this->getOriginalWidth($item));
            $y = (int) floor(0.5 * $this->getOriginalHeight($item));
            $zoom = 1;

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
     * @return Collection<string, mixed>
     */
    public function getManipulationParams(): Collection
    {
        $params = collect();

        $excluded = ['src', 'id', 'path', 'tag', 'alt'];

        foreach ($this->params as $param => $value) {
            if (! in_array($param, $excluded, true)) {
                switch ($param) {
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

    private function getOriginalWidth(AssetsAsset $item): int
    {
        $width = $item->meta('width');

        return is_numeric($width) ? (int) $width : 0;
    }

    private function getOriginalHeight(AssetsAsset $item): int
    {
        $height = $item->meta('height');

        return is_numeric($height) ? (int) $height : 0;
    }

    private function getOriginalAspectRatio(AssetsAsset $item): float
    {
        $width = $this->getOriginalWidth($item);
        $height = $this->getOriginalHeight($item);

        if ($width === 0 || $height === 0) {
            return 1.0;
        }

        return $width / $height;
    }

    /**
     * @return array{0: int, 1: int}
     */
    public function getFinalDimensions(mixed $item): array
    {
        $item = $this->getAsset($item);
        $width = (int) $this->getManipulationParams()->get('width');
        $height = (int) $this->getManipulationParams()->get('height');
        $aspect_ratio = $this->resolveAspectRatio(
            $this->getManipulationParams()->get('aspect_ratio'),
            $item
        );

        if ($width !== 0 && $height !== 0) {
            return [$width, $height];
        }

        if ($width !== 0) {
            return [$width, (int) round($width / $aspect_ratio)];
        }

        if ($height !== 0) {
            return [(int) round($height * $aspect_ratio), $height];
        }

        return [0, 0];
    }

    /**
     * Resolve aspect ratio from manipulation params (e.g. "16:9") or fall back to the asset.
     */
    private function resolveAspectRatio(mixed $aspectRatio, AssetsAsset $item): float
    {
        if ($aspectRatio === null || $aspectRatio === '') {
            return $this->getOriginalAspectRatio($item);
        }

        if (is_string($aspectRatio) && str_contains($aspectRatio, ':')) {
            [$w, $h] = explode(':', $aspectRatio, 2);
            if (is_numeric($w) && is_numeric($h) && (float) $h !== 0.0) {
                return (float) $w / (float) $h;
            }
        }

        if (is_numeric($aspectRatio)) {
            return (float) $aspectRatio;
        }

        return $this->getOriginalAspectRatio($item);
    }

    private function normalizeItem(AssetsAsset $item): string
    {
        $path = ltrim($item->path(), '/');

        $path = Str::ensureLeft($path, (string) $this->configuration->get('auto_mapping_folder'));

        if ($this->configuration->get('external_url_prefix')) {
            $path = Str::replace(Str::ensureRight((string) $this->configuration->get('external_url_prefix'), '/'), '', $path);
        }

        return $path;
    }

    /**
     * Build a Cloudinary transformation slug from arguments.
     *
     * @param  Collection<string, mixed>|array<string, mixed>|null  $args
     */
    public function buildTransformationSlug(Collection|array|null $args = null): string
    {
        if ($args === null) {
            return '';
        }

        $args = collect($args);
        if ($args->isEmpty()) {
            return '';
        }

        $default_transformations = $this->getDefaultTransformationByType($this->getAssetType());
        $args = collect($default_transformations)->merge($args);

        if ($args->get('gravity') && $args->get('crop') === 'fit') {
            $args->forget('gravity');
        }

        $fetch_format = null;
        if ($args->get('fetch_format')) {
            $fetch_format = $this->cloudinary_params->get('fetch_format').'_'.$args->get('fetch_format');
            $args->forget('fetch_format');
        }

        $quality = null;
        if ($args->get('quality')) {
            $quality = $this->cloudinary_params->get('quality').'_'.$args->get('quality');
            $args->forget('quality');
        }

        $slug = $args
            ->map(function ($value, string $key): ?string {
                if (
                    ! $this->cloudinary_params->has($key) ||
                    ! $this->isValidCloudinaryParam((string) $this->cloudinary_params->get($key), $value)
                ) {
                    Log::warning('Unknown Cloudinary parameter ['.$key.'] for Cloudinary transformation slug');

                    return null;
                }

                $shortKey = (string) $this->cloudinary_params->get($key);

                if ($key === 'progressive') {
                    return $value === true ? $shortKey : $shortKey.':'.$value;
                }

                return $shortKey.'_'.$value;
            })
            ->filter();

        $transformation_slug = $slug->implode(urlencode(','));
        if ($quality !== null) {
            $transformation_slug = $transformation_slug.'/'.$quality;
        }
        if ($fetch_format !== null) {
            $transformation_slug = $transformation_slug.'/'.$fetch_format;
        }

        return $transformation_slug;
    }

    /**
     * @return mixed
     */
    public function getDefaultTransformationByType(?string $type)
    {
        return $this->default_transformations->get($type);
    }

    /**
     * Check if the value is valid.
     */
    public function isValidCloudinaryParam(string $key = '', mixed $value = null): bool
    {
        if (($key === 'w' || $key === 'h') && ($value === null || $value === '')) {
            return false;
        }

        return true;
    }
}
