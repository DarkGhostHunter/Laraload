<?php

namespace DarkGhostHunter\Laraload\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void append(array|string|callable $directories)
 * @method static void exclude(array|string|callable $directories)
 * @method static bool generate()
 */
class Laraload extends Facade
{
    /**
     * @inheritDoc
     */
    protected static function getFacadeAccessor()
    {
        return \DarkGhostHunter\Laraload\Laraload::class;
    }
}
