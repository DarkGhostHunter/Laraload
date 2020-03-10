<?php

namespace Tests\Stubs;

class ConditionCallable
{
    public static bool $called = false;

    public function handle()
    {
        static::$called = true;

        return true;
    }
}
