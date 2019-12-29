<?php

namespace Tests;

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
use DarkGhostHunter\Laraload\Http\Middleware\LaraloadMiddleware;

class PackageTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [LaraloadServiceProvider::class];
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

    public function testRegisterTerminableMiddleware()
    {
        $this->assertTrue(
            $this->app[Kernel::class]->hasMiddleware(LaraloadMiddleware::class)
        );
    }

    public function testDoesntWorkInTesting()
    {
        $condition = $this->mock(CountRequests::class);

        $condition->shouldNotReceive('__invoke');

        Route::get('/test', function () {
            return 'ok';
        });

        $condition->shouldReceive('__invoke');

        $this->get('/test')->assertSee('ok');

        $this->app->instance('env', 'testing');
    }

    public function testDoesntWorkWithErrorResponse()
    {
        $condition = $this->mock(CountRequests::class);

        $condition->shouldNotReceive('__invoke');

        Route::get('/test', function () {
            throw new \Exception;
        });

        $condition->shouldReceive('__invoke');

        $this->get('/test')->assertStatus(500);

        $this->app->instance('env', 'testing');
    }

    public function testReachesCallable()
    {
        $condition = $this->mock(CountRequests::class);
        $laraload = $this->mock(Laraload::class);

        $this->app->instance('env', 'production');

        Route::get('/test', function () {
            return 'ok';
        });

        $condition->shouldReceive('__invoke')
            ->andReturnTrue();
        $laraload->shouldReceive('generate')
            ->andReturnTrue();

        $this->get('/test')->assertSee('ok');

        $this->app->instance('env', 'testing');
    }

    public function testCallableWithMethod()
    {
        $laraload = $this->mock(Laraload::class);

        $laraload->shouldReceive('generate')
            ->andReturnTrue();

        $this->app->instance('env', 'production');

        $this->app->make('config')->set(
            'laraload.condition',
            ConditionCallable::class . '@handle'
        );

        Route::get('/test', function () {
            return 'ok';
        });

        $this->get('/test')->assertSee('ok');

        $this->assertEquals('bar', ConditionCallable::$called);

        $this->app->instance('env', 'testing');
    }

    public function testCallableWithMethodAndParameters()
    {
        $laraload = $this->mock(Laraload::class);

        $laraload->shouldReceive('generate')
            ->andReturnTrue();

        $this->app->instance('env', 'production');

        $this->app->make('config')->set(
            'laraload.condition', [
            ConditionCallable::class . '@handle', ['foo' => 'qux'],
        ]);

        Route::get('/test', function () {
            return 'ok';
        });

        $this->get('/test')->assertSee('ok');

        $this->app->instance('env', 'testing');

        $this->assertEquals('qux', ConditionCallable::$called);
    }

    public function testConditionWorks()
    {
        $condition = $this->mock(CountRequests::class);
        $laraload = $this->mock(Laraload::class);

        $laraload->shouldReceive('generate')
            ->andReturnTrue();

        $condition->shouldReceive('__invoke')
            ->with(600, 'test_key')
            ->andReturnTrue();

        $this->app->instance('env', 'production');

        $this->app->make('config')->set('laraload.condition', [
            CountRequests::class, [600, 'test_key'],
        ]);

        Route::get('/test', function () {
            return 'ok';
        });

        $this->get('/test')->assertSee('ok');

        $this->app->instance('env', 'testing');
    }

    public function testConditionsCallsLaraload()
    {
        $laraload = $this->mock(Laraload::class);

        $laraload->shouldReceive('generate');

        $this->app->instance('env', 'production');

        $this->app->make('config')->set('laraload.condition', [
            CountRequests::class, [1, 'test_key'],
        ]);

        Route::get('/test', function () {
            return 'ok';
        });

        $this->get('/test')->assertSee('ok');

        $this->app->instance('env', 'testing');
    }

    public function testLaraloadGeneratesScript()
    {
        $event = Event::fake();

        $laraload = $this->mock(Preloader::class);

        $laraload->shouldReceive('autoload')
            ->with(base_path('vendor/autoload.php'))
            ->andReturnSelf();
        $laraload->shouldReceive('output')
            ->with(storage_path('preload.php'))
            ->andReturnSelf();
        $laraload->shouldReceive('memory')
            ->with(32)
            ->andReturnSelf();
        $laraload->shouldReceive('exclude')
            ->with([])
            ->andReturnSelf();
        $laraload->shouldReceive('append')
            ->with([])
            ->andReturnSelf();
        $laraload->shouldReceive('overwrite')
            ->with()
            ->andReturnSelf();
        $laraload->shouldReceive('generate')
            ->andReturnFalse();

        $this->app->instance('env', 'production');

        $this->app->make('config')->set('laraload.condition', [
            CountRequests::class, [1, 'test_key'],
        ]);

        Route::get('/test', function () {
            return 'ok';
        });

        $this->get('/test')->assertSee('ok');

        $this->app->instance('env', 'testing');

        $event->assertDispatched(PreloadCalledEvent::class, function ($event) {
            return $event->success === false;
        });
    }

    public function testWorksOnlyOnSuccessCodes()
    {
        $laraload = $this->mock(Laraload::class);

        $laraload->shouldReceive('generate')->times(2);

        $this->app->instance('env', 'production');

        $this->app->make('config')->set('laraload.condition', [
            CountRequests::class, [1, 'test_key'],
        ]);

        $i = rand(100, 200);
        Route::get('/100', fn() => response('ok', $i));
        $this->get('/100')->assertStatus($i);

        $i = rand(200, 300);
        Route::get('/200', fn() => response('ok', $i));
        $this->get('/200')->assertStatus($i);

        $i = rand(300, 400);
        Route::get('/300', fn() => response('ok', $i));
        $this->get('/300')->assertStatus($i);

        $i = rand(400, 500);
        Route::get('/400', fn() => response('ok', $i));
        $this->get('/400')->assertStatus($i);

        $i = rand(500, 600);
        Route::get('/500', fn() => response('ok', $i));
        $this->get('/500')->assertStatus($i);

        $this->app->instance('env', 'testing');
    }
}
