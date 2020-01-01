<?php

namespace DarkGhostHunter\Laraload\Http\Middleware;

use Closure;
use DarkGhostHunter\Laraload\Laraload;
use Illuminate\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application as App;

class LaraloadMiddleware
{
    /**
     * The application instance
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected App $app;

    /**
     * @var \Illuminate\Config\Repository
     */
    protected Config $config;

    /**
     * CountRequest constructor.
     *
     * @param  \Illuminate\Config\Repository $config
     * @param  \Illuminate\Contracts\Foundation\Application $app
     */
    public function __construct(Config $config, App $app)
    {
        $this->app = $app;
        $this->config = $config;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    /**
     * Perform any final actions for the request lifecycle.
     *
     * @param  \Symfony\Component\HttpFoundation\Request $request
     * @param  \Symfony\Component\HttpFoundation\Response $response
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function terminate($request, $response)
    {
        if ($this->responseIsFine($response) && $this->conditionIsTrue()) {
            app(Laraload::class)->generate();
        }
    }

    /**
     * Returns if the Response is anything but an error or an invalid response
     *
     * @param  \Symfony\Component\HttpFoundation\Response $response
     * @return bool
     */
    protected function responseIsFine($response)
    {
        $status = $response->getStatusCode();

        return $status > 100 && $status < 400;
    }

    /**
     * Checks if the given condition logic is true or false
     *
     * @return bool
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function conditionIsTrue() : bool
    {
        $condition = (array)$this->config->get('laraload.condition');

        return strpos($condition[0], '@')
            ? $this->app->call(... $condition)
            : $this->app->make($condition[0])(...$condition[1] ?? []);
    }

}
