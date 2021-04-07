<?php

namespace DarkGhostHunter\Laraload;

use DarkGhostHunter\Preloader\Preloader;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Config\Repository as Config;

class Laraload
{
    /**
     * Configuration array.
     *
     * @var array
     */
    protected array $config;

    /**
     * Preloader instance.
     *
     * @var \DarkGhostHunter\Preloader\Preloader
     */
    protected Preloader $preloader;

    /**
     * Event Dispatcher.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected Dispatcher $dispatcher;

    /**
     * Callback to use to append files.
     *
     * @var callable
     */
    protected $append;

    /**
     * Callback to use to exclude files.
     *
     * @var callable
     */
    protected $exclude;

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
     * Registers a callback to use to append files to the Preloader.
     *
     * @param  array|string|callable  $append
     *
     * @return void
     */
    public function append($append): void
    {
        $this->append = $append;
    }

    /**
     * Registers a callback to use to exclude files from the Preloader.
     *
     * @param  array|string|callable  $exclude
     *
     * @return void
     */
    public function exclude($exclude): void
    {
        $this->exclude = $exclude;
    }

    /**
     * Generates the Preloader Script.
     *
     * @return bool
     */
    public function generate(): bool
    {
        $preloader = $this->preloader
            ->ignoreNotFound($this->config['ignore-not-found'])
            ->memoryLimit($this->config['memory'])
            ->exclude($this->exclude)
            ->append($this->append);

        if ($this->config['use_require']) {
            $preloader->useRequire($this->config['autoload']);
        }

        $this->dispatcher->dispatch(new Events\PreloadCalledEvent(
            $result = $preloader->writeTo($this->config['output'])
        ));

        return $result;
    }
}
