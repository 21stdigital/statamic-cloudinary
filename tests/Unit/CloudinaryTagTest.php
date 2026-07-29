<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ItemNotFoundException;
use Statamic\Tags\Glide;
use Tests\Fixtures\RecordingGlide;
use TFD\Cloudinary\Converter\CloudinaryConverter;
use TFD\Cloudinary\Tags\Cloudinary;

/**
 * Build the tag with the given converter and pin a recording Glide fallback
 * onto it, so the fallback branches can be asserted without Glide's imaging
 * stack.
 *
 * @return array{0: Cloudinary, 1: RecordingGlide}
 */
function cloudinaryTagWithRecordingGlide(CloudinaryConverter $converter, array $context): array
{
    $properties = [
        'parser' => null,
        'content' => '',
        'context' => $context,
        'params' => ['width' => 100],
        'tag' => 'cloudinary:teaser_image',
        'tag_method' => 'teaser_image',
    ];

    $tag = new Cloudinary($converter);
    $tag->setProperties($properties);

    $glide = new RecordingGlide;
    $glide->setProperties($properties);

    $property = new ReflectionProperty($tag, 'glide');
    $property->setAccessible(true);
    $property->setValue($tag, $glide);

    return [$tag, $glide];
}

it('falls back to the glide wildcard tag when cloudinary is not configured', function ($argument) {
    config()->set('statamic.cloudinary.cloud_name', null);

    $converter = app(CloudinaryConverter::class);
    expect($converter->hasValidConfiguration())->toBeFalse();

    [$tag, $glide] = cloudinaryTagWithRecordingGlide($converter, ['teaser_image' => 'some-image.jpg']);

    expect($tag->wildcard($argument))->toBe(RecordingGlide::URL);
    expect($glide->wildcardCalls)->toBe([$argument]);
})->with([
    'field name' => 'teaser_image',
    // Statamic passes the method name, but a null argument must not break the
    // fallback either - the guard runs before the field is resolved.
    'null' => null,
]);

it('falls back to the glide wildcard tag when the asset cannot be found', function () {
    Log::shouldReceive('error')->once();

    $converter = Mockery::mock(CloudinaryConverter::class);
    $converter->shouldReceive('setParams');
    $converter->shouldReceive('hasValidConfiguration')->andReturnTrue();
    $converter->shouldReceive('setAssetType')
        ->andThrow(new ItemNotFoundException('Asset not found for string'));

    [$tag, $glide] = cloudinaryTagWithRecordingGlide($converter, [
        'teaser_image' => 'this-asset-does-not-exist-9f3a.jpg',
    ]);

    expect($tag->wildcard('teaser_image'))->toBe(RecordingGlide::URL);
    expect($glide->wildcardCalls)->toBe(['teaser_image']);
});

/*
 * The two tests above only catch the missing argument because the double keeps
 * Glide's arity. Pin that to the real tag instead of to a comment, so an
 * upstream signature change surfaces here rather than silently defusing them.
 */
it('keeps the recording glide double in step with the real glide signature', function () {
    $real = new ReflectionMethod(Glide::class, 'wildcard');
    $double = new ReflectionMethod(RecordingGlide::class, 'wildcard');

    expect($real->getNumberOfRequiredParameters())->toBe(1);
    expect($double->getNumberOfRequiredParameters())
        ->toBe($real->getNumberOfRequiredParameters());
});
