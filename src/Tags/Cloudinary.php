<?php

declare(strict_types=1);

namespace TFD\Cloudinary\Tags;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ItemNotFoundException;
use Statamic\Tags\Glide;
use Statamic\Tags\Tags;
use TFD\Cloudinary\Converter\CloudinaryConverter;
use TFD\Cloudinary\Interfaces\CloudinaryInterface;

class Cloudinary extends Tags implements CloudinaryInterface
{
    protected CloudinaryConverter $converter;

    private ?Glide $glide = null;

    public static function resetStaticState() {}

    public function __construct(CloudinaryConverter $converter)
    {
        $this->setConverter($converter);
    }

    public function setConverter(CloudinaryConverter $converter): void
    {
        $this->converter = $converter;
    }

    public function setParameters($parameters): static
    {
        parent::setParameters($parameters);

        $this->converter->setParams($parameters);

        return $this;
    }

    public function getGlideFallback()
    {
        if ($this->params->has('aspect_ratio')) {
            $aspect_ratio = $this->params->get('aspect_ratio');
            $this->params->forget('aspect_ratio');
            if (is_string($aspect_ratio) && str_contains($aspect_ratio, ':')) {
                [$ratio_width, $ratio_height] = explode(':', $aspect_ratio, 2);
                $aspect_ratio = (float) $ratio_width / (float) $ratio_height;
            }
            if ($this->params->has('width')) {
                $this->params->put('height', $this->params->get('width') / $aspect_ratio);
            } elseif ($this->params->has('height')) {
                $this->params->put('width', $this->params->get('height') * $aspect_ratio);
            }
        }

        if ($this->params->get('format') === 'auto') {
            $this->params->put('format', 'jpg');
        }

        if (! $this->glide) {
            $this->glide = new Glide;
            $this->glide->setProperties([
                'parser' => $this->parser,
                'content' => $this->content,
                'context' => $this->context,
                'params' => $this->params,
                'tag' => $this->tag,
                'tag_method' => $this->method,
            ]);
        }

        return $this->glide;
    }

    /**
     * Maps to {{ cloudinary:[field] }}.
     *
     * Where `field` is the variable containing the image ID
     *
     * @return string|array<int, array<string, mixed>|string>
     */
    public function wildcard($tag)
    {
        if (! $this->converter->hasValidConfiguration()) {
            return $this->getGlideFallback()->wildcard();
        }

        $tag = $tag ?? explode(':', $this->tag, 2)[1];
        if ($tag === 'ken_burns' || $tag === 'kenburns') {
            return $this->kenBurns();
        }

        $item = $this->context->value($tag);

        if ($this->isPair) {
            return $this->generate($item);
        }

        try {
            $this->converter->setAssetType($item);
        } catch (ItemNotFoundException $e) {
            Log::error($e->getMessage());

            return $this->getGlideFallback()->wildcard();
        }

        return $this->output($this->converter->generateCloudinaryUrl($item));
    }

    /**
     * The {{ cloudinary }} tag.
     *
     * @return string|array|bool
     */
    public function index()
    {
        if (! $this->converter->hasValidConfiguration()) {
            return $this->getGlideFallback()->index();
        }

        if (! ($src = $this->params->get('src'))) {
            return $this->sourceIsEmptyError();
        }

        $item = $this->converter->getAsset($src);

        try {
            $this->converter->setAssetType($item);
        } catch (ItemNotFoundException $e) {
            Log::error($e->getMessage());

            return $this->getGlideFallback()->index();
        }

        return $this->output($this->converter->generateCloudinaryUrl($item));
    }

    /**
     * The {{ cloudinary:ken_burns }} tag.
     *
     * @return string|array|bool
     */
    public function kenBurns()
    {
        if (! $this->converter->hasValidConfiguration()) {
            return $this->getGlideFallback()->index();
        }

        if (! ($src = $this->params->get('src'))) {
            return $this->sourceIsEmptyError();
        }

        $item = $this->converter->getAsset($src);

        try {
            $this->converter->setAssetType($item);
        } catch (ItemNotFoundException $e) {
            Log::error($e->getMessage());

            return $this->getGlideFallback()->index();
        }

        return $this->output($this->converter->generateKenBurnsUrl($item));
    }

    /**
     * Maps to {{ cloudinary:generate }} ... {{ /glide:cloudinary }}.
     *
     * Generates the image and makes variables available within the pair.
     *
     * @return array<int, array<string, mixed>|string>
     */
    public function generate($items = null)
    {
        $items = $items ?? $this->params->get(['src', 'id', 'path']);

        $items = is_iterable($items) ? collect($items) : collect([$items]);

        return $items
            ->map(function ($item) {
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
            })
            ->all();
    }

    /**
     * Output the tag.
     */
    private function output(string $url): string
    {
        if ($this->isPair) {
            $src = $this->params->get('src')
                ?? $this->params->get('id')
                ?? $this->params->get('path');

            if ($src === null || $src === '') {
                return $this->parse(compact('url'));
            }

            try {
                $item = $this->converter->getAsset($src);
                [$width, $height] = $this->converter->getFinalDimensions($item);

                return $this->parse(compact('url', 'width', 'height'));
            } catch (ItemNotFoundException $e) {
                Log::error($e->getMessage());

                return $this->parse(compact('url'));
            }
        }
        if ($this->params->bool('tag')) {
            return "<img src=\"$url\" alt=\"{$this->params->get('alt')}\" />";
        }

        return $url;
    }

    /**
     * Error Log for src is empty.
     */
    private function sourceIsEmptyError(): bool
    {
        $additionalInformation = [];

        if (isset($this->context['id'])) {
            $additionalInformation[] = 'field_id:['.$this->context['id'].']';
        }

        if (isset($this->context['page']) && is_object($this->context['page']) && isset($this->context['page']->id)) {
            $additionalInformation[] = 'page_id:['.$this->context['page']->id.']';
        }

        $additionalInformation = implode(', ', $additionalInformation);

        Log::error("Cloudinary parameter \"src\" is empty | Context: $additionalInformation");

        return false;
    }
}
