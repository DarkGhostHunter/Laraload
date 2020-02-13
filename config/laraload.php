<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Autoloader location
    |--------------------------------------------------------------------------
    |
    | The autoloader location. By default, the autoloader is located inside the
    | "vendor" directory on the root project path. Some projects may have a
    | different path, which you can override here if it's not standard.
    |
    */

    'autoload' => base_path('vendor/autoload.php'),

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
    | them), and `opcache_compile_file`. Depending on your app, you may want
    | one over the other depending on the link resolution to other files.
    |
    | Supported: "require", "compile"
    |
    */

    'method' => 'require',

    /*
    |--------------------------------------------------------------------------
    | Included & Excluded files
    |--------------------------------------------------------------------------
    |
    | You can include or exclude files in the Preload script as you wish. The
    | included files won't count to the memory limit, but the excluded files
    | will subtract themselves from the memory limit of the overall list.
    |
    */

    'include' => [
        //
    ],

    'exclude' => [
        //
    ],
];
