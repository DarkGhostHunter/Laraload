<?php

namespace DarkGhostHunter\Laraload\Events;

class PreloadCalledEvent
{
    /**
     * Generation status
     *
     * @var bool true on success, false on failure
     */
    public bool $success;

    /**
     * PreloadCalledEvent constructor.
     *
     * @param  bool  $success
     */
    public function __construct(bool $success)
    {
        $this->success = $success;
    }
}
