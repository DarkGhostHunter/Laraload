<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Condition logic
    |--------------------------------------------------------------------------
    |
    | The custom condition logic you want to execute to generate (or not) the
    | Preload script. You can use any class using it's name, or Class@method
    | notation. This will be executed using the Service Container's "call".
    |
    */

    'condition' => \DarkGhostHunter\Laraload\Conditions\CountRequests::class,

    /*
    |--------------------------------------------------------------------------
    | Output
    |--------------------------------------------------------------------------
    |
    | Once the Preload script is generated, it will written to the storage
    | path of your application, since it should have permission to write.
    | You can change the script output for anything as long is writable.
    |
    */

    'output' => storage_path('preload.php'),

    /*
    |--------------------------------------------------------------------------
    | Memory Limit
    |--------------------------------------------------------------------------
    |
    | The Preloader script can be configured to handle a limited number of
    | files based on their memory consumption. The default is a safe bet
    | for most apps, but you can change it for your app specifically.
    |
    */

    'memory' => 32,

    /*
    |--------------------------------------------------------------------------
    | Upload method
    |--------------------------------------------------------------------------
    |
    | Opcache supports preloading files by using `require_once` (which executes
    | and resolves each file link), and `opcache_compile_file` (which not). If
    | you want to use require ensure the Composer Autoloader path is correct.
    |
    */

    'use_require' => false,
    'autoload'    => base_path('vendor/autoload.php'),

    /*
    |--------------------------------------------------------------------------
    | Appended & Excluded directories
    |--------------------------------------------------------------------------
    |
    | You can include or exclude directories in the Preload list as you wish.
    | The included files won't count to the memory limit, but the excluded
    | will subtract themselves from the memory limit of the overall list.
    |
    */

    'append' => [
        // 'path/to/directory'
    ],

    'exclude' => [
        // 'path/to/directory'
    ],
];
