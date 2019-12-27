<?php

namespace DarkGhostHunter\Laraload;

use DarkGhostHunter\Preloader\Preloader;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Config\Repository as Config;

class Laraload
{
    /**
     * Configuration array
     *
     * @var array
     */
    protected array $config;

    /**
     * Preloader instance
     *
     * @var \DarkGhostHunter\Preloader\Preloader
     */
    protected Preloader $preloader;

    /**
     * Event Dispatcher
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected Dispatcher $dispatcher;

    /**
     * Laraload constructor.
     *
     * @param  \Illuminate\Contracts\Config\Repository $config
     * @param  \Illuminate\Contracts\Events\Dispatcher $dispatcher
     * @param  \DarkGhostHunter\Preloader\Preloader $preloader
     */
    public function __construct(Config $config, Dispatcher $dispatcher, Preloader $preloader)
    {
        $this->config = $config->get('laraload');
        $this->preloader = $preloader;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Generates a Script
     *
     * @return void
     */
    public function generate()
    {
        $status = $this->preloader
            ->autoload($this->config['autoload'])
            ->output($this->config['output'])
            ->memory($this->config['memory'])
            ->exclude($this->config['exclude'])
            ->append($this->config['include'])
            ->overwrite()
            ->generate();

        $this->dispatcher->dispatch(new Events\PreloadCalledEvent($status));
    }
}
