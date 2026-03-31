<?php

declare(strict_types=1);

use Statamic\Assets\Asset;
use TFD\Cloudinary\Converter\CloudinaryConverter;

it('returns empty string from buildTransformationSlug when args is null', function () {
    $converter = app(CloudinaryConverter::class);

    expect($converter->buildTransformationSlug(null))->toBe('');
});

it('returns empty string from buildTransformationSlug when args is an empty array', function () {
    $converter = app(CloudinaryConverter::class);

    expect($converter->buildTransformationSlug([]))->toBe('');
});

it('buildTransformationSlug accepts array input and includes width transformation', function () {
    $converter = app(CloudinaryConverter::class);
    $reflection = new ReflectionClass($converter);
    $property = $reflection->getProperty('asset_type');
    $property->setAccessible(true);
    $property->setValue($converter, 'image');

    expect($converter->buildTransformationSlug(['width' => 100]))->toContain('w_100');
});

it('rejects empty width or height cloudinary params', function () {
    $converter = app(CloudinaryConverter::class);

    expect($converter->isValidCloudinaryParam('w', ''))->toBeFalse();
    expect($converter->isValidCloudinaryParam('h', null))->toBeFalse();
    expect($converter->isValidCloudinaryParam('w', '200'))->toBeTrue();
});

it('resolves aspect ratio strings for final dimensions', function () {
    $converter = app(CloudinaryConverter::class);
    $converter->setParams(['width' => 400, 'aspect_ratio' => '16:9']);

    $reflection = new ReflectionClass($converter);
    $method = $reflection->getMethod('resolveAspectRatio');
    $method->setAccessible(true);

    $asset = Mockery::mock(Asset::class);
    $asset->shouldReceive('meta')->with('width')->andReturn(1920);
    $asset->shouldReceive('meta')->with('height')->andReturn(1080);

    $ratio = $method->invoke($converter, '16:9', $asset);

    expect($ratio)->toBeFloat();
    expect(round($ratio, 5))->toBe(round(16 / 9, 5));
});
