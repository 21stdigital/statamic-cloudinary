<?php

namespace TFD\Cloudinary\Components;

use Illuminate\View\Component;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ItemNotFoundException;
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

    public function setConverter($converter)
    {
        $this->converter = $converter;
    }

    public function render()
    {
        if (! $this->converter->hasValidConfiguration()) {
            return false;
        }

        return function (array $data) {
            /** @var ComponentAttributeBag */
            $attributeBag = $data['attributes'];
            $attributes = $attributeBag->getAttributes();
            
            if (!$attributeBag->has('src')) {
                return false;
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

            return view('cloudinary::components.image', $viewData->toArray())->render();
        };
    }
}
