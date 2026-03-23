<?php

declare(strict_types=1);

namespace TFD\Cloudinary\Components;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;
use TFD\Cloudinary\Converter\CloudinaryConverter;
use TFD\Cloudinary\Interfaces\CloudinaryInterface;

class Image extends Component implements CloudinaryInterface
{
    protected $converter;

    public function __construct(CloudinaryConverter $converter)
    {
        $this->setConverter($converter);
    }

    public function setConverter(CloudinaryConverter $converter): void
    {
        $this->converter = $converter;
    }

    public function render(): mixed
    {
        if (! $this->converter->hasValidConfiguration()) {
            return '';
        }

        return function (array $data) {
            /** @var ComponentAttributeBag */
            $attributeBag = $data['attributes'];
            $attributes = $attributeBag->getAttributes();

            if (! $attributeBag->has('src')) {
                return '';
            }

            $this->converter->setParams($attributes);

            $item = $attributeBag->get('src');
            $item = $this->converter->getAsset($item);

            try {
                $this->converter->setAssetType($item);
            } catch (ItemNotFoundException $e) {
                Log::error($e->getMessage());

                return '';
            }

            [$width, $height] = $this->converter->getFinalDimensions($item);

            $attributes = collect($attributes);

            $viewData = $attributes->merge(collect([
                'url' => $this->converter->generateCloudinaryUrl($item),
                'width' => $width,
                'height' => $height,
            ]));

            return view('cloudinary::components.cloudinary-image', $viewData->toArray())->render();
        };
    }
}
