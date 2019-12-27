<?php

namespace DarkGhostHunter\Laraload\Conditions;

use DarkGhostHunter\Laraload\Laraload;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Cache\Repository as Cache;

class CountRequests
{
    /**
     * Cache repository
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected Cache $cache;

    /**
     * Configuration repository
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected Config $config;

    /**
     * CountRequest constructor.
     *
     * @param  \Illuminate\Contracts\Cache\Repository $cache
     * @param  \Illuminate\Contracts\Config\Repository $config
     */
    public function __construct(Cache $cache, Config $config)
    {
        $this->cache = $cache;
        $this->config = $config;
    }

    /**
     * Recreates the Preload script each given number of requests.
     *
     * @param  int $hits
     * @param  string $cacheKey
     * @return bool
     */
    public function __invoke(int $hits = 500, string $cacheKey = 'laraload|request_hits')
    {
        // Increment the count by one. If it doesn't exists, we will start with 1.
        $count = $this->cache->increment($cacheKey);

        // Each number of hits return true
        return $count && $count % $hits === 0;
    }
}
