<?php

namespace DarkGhostHunter\Laraload\Conditions;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;

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
     * Number of hits to
     *
     * @var int
     */
    protected int $hits;

    /**
     * @var string
     */
    protected string $cacheKey;

    /**
     * CountRequest constructor.
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $cache
     * @param  int  $hits
     * @param  string  $cacheKey
     */
    public function __construct(Cache $cache, Config $config)
    {
        $this->cache = $cache;
        $this->config = $config;
    }

    /**
     * Recreates the Preload script each given number of requests.
     *
     * @return bool
     */
    public function __invoke()
    {
        $this->hits = $this->config->get('laraload.hits', 500);
        $this->cacheKey = $this->config->get('laraload.cache_key', 'laraload|request_hits');

        // Increment the count by one. If it doesn't exists, we will start with 1.
        $count = $this->cache->increment($this->cacheKey);

        // Each number of hits return true
        $status = $count && ( $count > $this->hits );

        // Reset counter to zero
        if($status){
            $this->cache->put($this->cacheKey, 0);
        }
        return $status;
    }
}
