<?php

namespace Tests;

use Exception;
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

class PackageTest extends TestCase
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

    public function testPublishesConfig()
    {
        $this->artisan('vendor:publish', [
            '--provider' => 'DarkGhostHunter\Laraload\LaraloadServiceProvider',
        ])
            ->execute();

        $this->assertFileExists(base_path('config/laraload.php'));
        $this->assertFileEquals(base_path('config/laraload.php'), __DIR__ . '/../config/laraload.php');

        unlink(base_path('config/laraload.php'));
    }

    public function testDoesntRegisterTerminableMiddlewareInTesting()
    {
        $this->assertFalse(
            $this->app[Kernel::class]->hasMiddleware(LaraloadMiddleware::class)
        );
    }

    public function testDoesntWorkWithErrorResponse()
    {
        $condition = $this->mock(CountRequests::class);

        $this->app[Kernel::class]->pushMiddleware(LaraloadMiddleware::class);

        $condition->shouldNotReceive('__invoke');

        Route::get('/test', function () {
            throw new Exception;
        });

        $condition->shouldReceive('__invoke');

        $this->get('/test')->assertStatus(500);
    }

    public function testReachesCallable()
    {
        $condition = $this->mock(CountRequests::class);
        $laraload = $this->mock(Laraload::class);

        $this->app[Kernel::class]->pushMiddleware(LaraloadMiddleware::class);

        Route::get('/test', function () {
            return 'ok';
        });

        $condition->shouldReceive('__invoke')
            ->andReturnTrue();
        $laraload->shouldReceive('generate')
            ->andReturnTrue();

        $this->get('/test')->assertSee('ok');
    }

    public function testCallableWithMethod()
    {
        $this->app[Kernel::class]->pushMiddleware(LaraloadMiddleware::class);

        $laraload = $this->mock(Laraload::class);

        $laraload->shouldReceive('generate')
            ->andReturnTrue();

        $this->app->make('config')->set('laraload.condition', ConditionCallable::class . '@handle');

        Route::get('/test', function () {
            return 'ok';
        });

        $this->get('/test')->assertSee('ok');

        $this->assertEquals(true, ConditionCallable::$called);
    }

    public function testCallableWithMethodAndParameters()
    {
        $laraload = $this->mock(Laraload::class);

        $this->app[Kernel::class]->pushMiddleware(LaraloadMiddleware::class);

        $laraload->shouldReceive('generate')
            ->andReturnTrue();

        $this->app->make('config')->set(
            'laraload.condition', ConditionCallable::class . '@handle');

        Route::get('/test', function () {
            return 'ok';
        });

        $this->get('/test')->assertSee('ok');

        $this->assertTrue(ConditionCallable::$called);
    }

    public function testConditionWorks()
    {
        $condition = $this->mock(CountRequests::class);
        $laraload = $this->mock(Laraload::class);

        $this->app[Kernel::class]->pushMiddleware(LaraloadMiddleware::class);

        $laraload->shouldReceive('generate')
            ->andReturnTrue();

        $condition->shouldReceive('__invoke')
            ->withNoArgs()
            ->andReturnTrue();

        $this->app->make('config')->set('laraload.condition', CountRequests::class);

        Route::get('/test', function () {
            return 'ok';
        });

        $this->get('/test')->assertSee('ok');
    }

    public function testConditionsCallsLaraload()
    {
        $laraload = $this->mock(Laraload::class);

        $this->app[Kernel::class]->pushMiddleware(LaraloadMiddleware::class);

        $laraload->shouldReceive('generate');

        $this->app->make('config')->set('laraload.condition', CountRequests::class);

        Route::get('/test', function () {
            return 'ok';
        });

        $this->get('/test')->assertSee('ok');
    }

    public function testLaraloadGeneratesScript()
    {
        $event = Event::fake();

        $this->app[Kernel::class]->pushMiddleware(LaraloadMiddleware::class);

        $preload = $this->mock(Preloader::class);

        $preload->shouldReceive('memoryLimit')
            ->with(32)
            ->andReturnSelf();
        $preload->shouldReceive('exclude')
            ->with([])
            ->andReturnSelf();
        $preload->shouldReceive('append')
            ->with([])
            ->andReturnSelf();
        $preload->shouldReceive('writeTo')
            ->with(config('laraload.output'))
            ->andReturnTrue();

        $this->app->when(CountRequests::class)
            ->needs('$hits')
            ->give(1);

        $this->app->make('config')->set('laraload.condition', CountRequests::class);

        Route::get('/test', function () {
            return 'ok';
        });

        $this->get('/test')->assertSee('ok');

        $event->assertDispatched(PreloadCalledEvent::class, function ($event) {
            return $event->success;
        });
    }

    public function testUsesRequireInsteadOfCompile()
    {
        $this->app->when(CountRequests::class)
            ->needs('$hits')
            ->give(1);

        $this->app->make('config')->set('laraload.use_require', true);
        $this->app->make('config')->set('laraload.condition', CountRequests::class);

        $preloader = $this->mock(Preloader::class);

        $preloader->shouldReceive('memoryLimit')->andReturnSelf();
        $preloader->shouldReceive('exclude')->andReturnSelf();
        $preloader->shouldReceive('append')->andReturnSelf();
        $preloader->shouldReceive('useRequire')->with(config('laraload.autoload'))->andReturnSelf();
        $preloader->shouldReceive('writeTo')->with(config('laraload.output'))->andReturnTrue();

        $this->app[Kernel::class]->pushMiddleware(LaraloadMiddleware::class);

        Route::get('/test', fn() => response('ok'));
        $this->get('/test')->assertStatus(200);
    }

    public function testReceivesAppendedAndExcludedFiles()
    {
        $this->app->when(CountRequests::class)
            ->needs('$hits')
            ->give(1);

        $this->app->make('config')->set('laraload.condition', CountRequests::class);

        $preloader = $this->mock(Preloader::class);

        $preloader->shouldReceive('memoryLimit')->andReturnSelf();
        $preloader->shouldReceive('exclude')->with('foo')->andReturnSelf();
        $preloader->shouldReceive('append')->with('bar')->andReturnSelf();
        $preloader->shouldReceive('writeTo')->with(config('laraload.output'))->andReturnTrue();

        LaraloadFacade::exclude('foo');
        LaraloadFacade::append('bar');

        $this->app[Kernel::class]->pushMiddleware(LaraloadMiddleware::class);

        Route::get('/test', fn() => response('ok'));
        $this->get('/test')->assertStatus(200);
    }

    public function testWorksOnNonErrorCodes()
    {
        $laraload = $this->mock(Laraload::class);

        $this->app->when(CountRequests::class)
            ->needs('$hits')
            ->give(1);
        $laraload->shouldReceive('generate')->times(3);

        $this->app->make('config')->set('laraload.condition', CountRequests::class);
        $this->app[Kernel::class]->pushMiddleware(LaraloadMiddleware::class);

        $i = rand(100, 199);
        Route::get('/100', fn() => response('ok', $i));
        $this->get('/100')->assertStatus($i);

        $i = rand(200, 299);
        Route::get('/200', fn() => response('ok', $i));
        $this->get('/200')->assertStatus($i);

        $i = rand(300, 399);
        Route::get('/300', fn() => response('ok', $i));
        $this->get('/300')->assertStatus($i);

        $i = rand(400, 499);
        Route::get('/400', fn() => response('ok', $i));
        $this->get('/400')->assertStatus($i);

        $i = rand(500, 599);
        Route::get('/500', fn() => response('ok', $i));
        $this->get('/500')->assertStatus($i);
    }

    protected function tearDown() : void
    {
        parent::tearDown();
        ConditionCallable::$called = false;
    }
}
