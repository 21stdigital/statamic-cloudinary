<?php

declare(strict_types=1);

namespace TFD\Cloudinary\Interfaces;

use TFD\Cloudinary\Converter\CloudinaryConverter;

interface CloudinaryInterface
{
    public function setConverter(CloudinaryConverter $converter): void;
}
