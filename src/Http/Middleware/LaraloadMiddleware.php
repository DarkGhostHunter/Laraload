<?php

namespace DarkGhostHunter\Laraload\Http\Middleware;

use Closure;
use DarkGhostHunter\Laraload\Laraload;
use Illuminate\Config\Repository as Config;
use Illuminate\Container\Container;

class LaraloadMiddleware
{
    /**
     * @var \Illuminate\Config\Repository
     */
    protected Config $config;

    /**
     * Application container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected Container $container;

    /**
     * CountRequest constructor.
     *
     * @param  \Illuminate\Config\Repository  $config
     */
    public function __construct(Config $config)
    {
        $this->container = Container::getInstance();
        $this->config = $config;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    /**
     * Perform any final actions for the request lifecycle.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     *
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function terminate($request, $response)
    {
        if ($this->responseNotError($response) && $this->conditionIsTrue()) {
            $this->container->make(Laraload::class)->generate();
        }
    }

    /**
     * Returns if the Response is anything but an error or an invalid response.
     *
     * @param  \Psr\Http\Message\ResponseInterface|\Symfony\Component\HttpFoundation\Response  $response
     *
     * @return bool
     */
    protected function responseNotError($response): bool
    {
        return $response->getStatusCode() < 400;
    }

    /**
     * Checks if the given condition logic is true or false.
     *
     * @return bool
     */
    protected function conditionIsTrue(): bool
    {
        return (bool)$this->container->call($this->config->get('laraload.condition'));
    }
}
