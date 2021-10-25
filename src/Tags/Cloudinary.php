<?php

namespace TFD\Cloudinary\Tags;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ItemNotFoundException;
use Statamic\Tags\Tags;
use TFD\Cloudinary\Converter\CloudinaryConverter;
use TFD\Cloudinary\Interfaces\CloudinaryInterface;

class Cloudinary extends Tags implements CloudinaryInterface
{

    protected $converter;

    public function __construct(CloudinaryConverter $converter)
    {
        $this->setConverter($converter);
    }

    public function setConverter($converter)
    {
        $this->converter = $converter;
    }

    public function setParameters($parameters)
    {
        parent::setParameters($parameters);

        $this->converter->setParams($parameters);
    }

    /**
     * Maps to {{ cloudinary:[field] }}.
     *
     * Where `field` is the variable containing the image ID
     *
     * @param  $method
     * @param  $args
     * @return string
     */
    public function wildcard()
    {
        if (! $this->converter->hasValidConfiguration()) {
            return false;
        }

        $tag = explode(':', $this->tag, 2)[1];
        $item = $this->context->value($tag);
        
        if ($this->isPair) {
            return $this->generate($item);
        }
        
        try {
            $this->converter->setAssetType($item);
        } catch (ItemNotFoundException $e) {
            Log::error($e->getMessage());
            
            return '';
        }

        return $this->output($this->converter->generateCloudinaryUrl($item));
    }

    /**
     * The {{ cloudinary }} tag.
     *
     * @return string|array
     */
    public function index()
    {
        return false;
    }

    /**
     * Maps to {{ cloudinary:generate }} ... {{ /glide:cloudinary }}.
     *
     * Generates the image and makes variables available within the pair.
     *
     * @return string
     */
    public function generate($items = null)
    {
        $items = $items ?? $this->params->get(['src', 'id', 'path']);

        $items = is_iterable($items) ? collect($items) : collect([$items]);

        return $items->map(function ($item) {
            try {
                $this->converter->setAssetType($item);
            } catch (ItemNotFoundException $e) {
                Log::error($e->getMessage());
                
                return '';
            }
            
            $data = ['url' => $this->converter->generateCloudinaryUrl($item)];
            [$width, $height] = $this->converter->getFinalDimensions($item);

            $data['width'] = $width;
            $data['height'] = $height;

            return $data;
        })->all();
    }

    /**
     * Output the tag.
     *
     * @param string $url
     * @return string
     */
    private function output($url)
    {
        if ($this->isPair) {
            return $this->parse(
                compact('url', 'width', 'height')
            );
        }
        if ($this->params->bool('tag')) {
            return "<img src=\"$url\" alt=\"{$this->params->get('alt')}\" />";
        }

        return $url;
    }
}
