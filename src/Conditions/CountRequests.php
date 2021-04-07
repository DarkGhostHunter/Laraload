<?php

namespace DarkGhostHunter\Laraload\Conditions;

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
    public function __construct(Cache $cache, int $hits = 500, string $cacheKey = 'laraload|request_hits')
    {
        $this->cache = $cache;
        $this->hits = $hits;
        $this->cacheKey = $cacheKey;
    }

    /**
     * Recreates the Preload script each given number of requests.
     *
     * @return bool
     */
    public function __invoke(): bool
    {
        // Increment the count by one. If it doesn't exists, we will start with 1.
        $count = $this->cache->increment($this->cacheKey);

        // Each number of hits return true
        if ($count && $count % $this->hits === 0) {
            $this->cache->set($this->cacheKey, 0);
            return true;
        }

        return false;
    }
}
