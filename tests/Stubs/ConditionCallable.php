<?php

namespace Tests\Stubs;

class ConditionCallable
{
    public static $called;

    public function handle($foo = 'bar')
    {
        static::$called = $foo;

        return true;
    }
}
