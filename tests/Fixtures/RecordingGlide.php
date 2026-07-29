<?php

declare(strict_types=1);
namespace Tests\Fixtures;

use Statamic\Tags\Glide;

/**
 * A Glide tag that records how it was called instead of generating URLs.
 *
 * `wildcard()` deliberately keeps Glide's required `$method` parameter: a
 * fallback called without an argument has to raise `ArgumentCountError`,
 * which is the regression this double exists to catch. Do not give it a
 * default value.
 */
class RecordingGlide extends Glide
{
    public const URL = '/img/glide-fallback.jpg';

    /**
     * @var array<int, mixed>
     */
    public array $wildcardCalls = [];

    public function wildcard($method)
    {
        $this->wildcardCalls[] = $method;

        return self::URL;
    }
}
