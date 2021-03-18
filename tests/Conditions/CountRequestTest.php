<?php

namespace Tests\Conditions;

use Exception;
use Illuminate\Contracts\Cache\Repository;
use Orchestra\Testbench\TestCase;
use Tests\Stubs\ConditionCallable;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Event;
use DarkGhostHunter\Laraload\Laraload;
use DarkGhostHunter\Preloader\Preloader;
use DarkGhostHunter\Laraload\LaraloadServiceProvider;
use DarkGhostHunter\Laraload\Conditions\CountRequests;
use DarkGhostHunter\Laraload\Events\PreloadCalledEvent;
use DarkGhostHunter\Laraload\Facades\Laraload as LaraloadFacade;
use DarkGhostHunter\Laraload\Http\Middleware\LaraloadMiddleware;

class CountRequestTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [LaraloadServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Laraload' => LaraloadFacade::class
        ];
    }

    public function testReaches500AndResets()
    {
        $cache = $this->mock(Repository::class);

        $cache->shouldReceive('increment')->with('laraload|request_hits')
            ->andReturn(500);

        $cache->shouldReceive('set')->with('laraload|request_hits', 0)
            ->andReturnNull();

        (new CountRequests($cache))();
    }

    public function testUsesNonDefaultConfig()
    {
        $cache = $this->mock(Repository::class);

        $cache->shouldReceive('increment')->with('foo')
            ->andReturn(6000);

        $cache->shouldReceive('set')->with('foo', 0)
            ->andReturnNull();

        (new CountRequests($cache, 6000, 'foo'))();
    }
}
