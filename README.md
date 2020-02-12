![Goh Rhy Yan - Unsplash #y8CtjK0ej6A](https://images.unsplash.com/photo-1496327249223-c84a3c1db090?ixlib=rb-1.2.1&auto=format&fit=crop&w=1200&h=400&q=80)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/darkghosthunter/laraload.svg?style=flat-square)](https://packagist.org/packages/darkghosthunter/laraload) [![License](https://poser.pugx.org/darkghosthunter/laraload/license)](https://packagist.org/packages/darkghosthunter/laraload)
![](https://img.shields.io/packagist/php-v/darkghosthunter/laraload.svg)
 ![](https://github.com/DarkGhostHunter/Laraload/workflows/PHP%20Composer/badge.svg)
[![Coverage Status](https://coveralls.io/repos/github/DarkGhostHunter/Laraload/badge.svg?branch=master)](https://coveralls.io/github/DarkGhostHunter/Laraload?branch=master)

# Laraload

Effortlessly create a PHP 7.4 Preload script for your Laravel project.

## Requirements

* Laravel 6.x
* PHP 7.4 or later
* `ext-opcache`

> The Opcache extension is not enforced by the package. Just be sure to enable it in your project's PHP main process.

## Installation

Call composer and you're done.

```bash
composer require darkghosthunter/laraload
```

## What is Preloading? What does this?

Preloading is a new feature for PHP 7.4 and Opcache. It "compiles" a list of files into memory, thus making the application code fast. For that to work, it needs to read a PHP script that uploads the files, at startup.

This package wraps the Preloader package that generates a preload file. Once it's generated, you can point the generated list into your `php.ini`.

## Usage

By default, this package constantly recreates your preload script each 500 requests in `storage/preload.php`. That's it. But you want the details, don't you?

1. A global terminable middleware checks if the Response code is between 200 and 400.
2. Then it calls a custom *Condition* class.
2. The *Condition* evaluates if the script should be generated.
3. If the Condition returns `true`, the script is generated.
4. A `PreloadCalledEvent` is called with the generation status.

## Configuration

Some people may not be happy with the "default" behaviour. Luckily, you can configure your own way to generate the script.

First publish the configuration file:

```bash
php artisan vendor:publish --provider="DarkGhostHunter\Laraload\LaraloadServiceProvider"
```

Let's check config array:

```php
<?php

return [
    'autoload' => base_path('vendor/autoload.php'),
    'condition' => 'DarkGhostHunter\Laraload\Conditions\CountRequests',
    'output' => storage_path('preload.php'),
    'memory' => 32,
    'include' => [],
    'exclude' => [],
];
```

#### Autoload

Most of Laravel applications will have their Composer Autoload file in `vendor/autoload.php`. You can override this with your own absolute path:

```php
<?php

return [
    'autoload' => '/path/to/my/vendor/autoload.php',
];
```

#### Condition

This package comes with a _simple_ condition class that returns `true` every 500 requests, which triggers the script generation. 

You can define your own callable as a condition to generate the Preload script. This will be called after the request is handled to the browser.

```php
<?php

return [
    'condition' => [ 
        \DarkGhostHunter\Laraload\Conditions\CountRequests::class, [
            'hits' => 1000,
            'key' => 'custom_cache_key'
        ],
    ],
];
```

Alternatively, you can issue any callable using `MyClass@method` notation, or an invokable class.

#### Output

By default, the script is saved in your storage path, but you can change the filename and path to save it inside the storage path, as long PHP has permissions to write on it.

```php
<?php

return [
    'output' => '/var/www/preloads/my_preload.php',
];
```

#### Memory Limit

For most applications, 32MB is fine as a preload limit, but you may fine-tune it for your project specifically.

```php
<?php

return [
    'memory' => 63.5,
];
```

#### Method

Opcache allows to preload files using `require_once` or `opcache_compile_file()`.

Requiring a file will *execute* it, resolving all the links (parent classes, traits, interfaces, etc.) before compiling it, while `opcache_compile_file()` will only compile. The latter may output warnings since some links may not be preloaded if they're are out of the list.

Depending on your application, you may want to use one over the other. 

```php
<?php

return [
    'method' => 'compile',
];
```

#### Include & Exclude

You can include and exclude particular files from the Preload script. Each item in the list is passed to the `glob()` function.

```php
<?php

return [
    'include' => [
        '/very/important/files/*.php'
    ],
    'exclude' => [
        '/not/so/very/important/file.php',
        '/and/this/one/too/*.php'
    ],
];
```

Included files won't count for the memory limit, meaning, these can exceed it. 

Excluded files **will** count for the memory limit, meaning, these will free their memory consumption from the list so other files can go in.

### FAQ

* Why I can't use something like `php artisan laraload:generate` instead? Like a [Listener](https://laravel.com/docs/events) or [Scheduler](https://laravel.com/docs/scheduling)?

Opcache is not enabled when using PHP CLI. You must let the live application generate the list automatically _on demand_.

* Does this excludes the package itself from the list?

Only the underlying Preloader package. The files in this one are needed to trigger the Preloader itself without hindering performance. But you do you.

* How the list is created?

Basically: the most hit files in descending order. Each file consumes memory, so the list is _soft-cut_ when the files reach that limit.

* You said "_soft-cut_", why is that?

Each file is loaded using `require_once`, which also loads its other file links. If the last file is a class with links outside the list, these will be called to avoid unresolved dependencies.

* Can I just put all the files in my project?

Yes, but including all the files of your application may have diminishing returns compared to, for example, only the most used. You can always benchmark your app yourself. 

* Can I use a Closure for my condition?

Yes, but remember that Closures cannot be serialized when caching the configuration using `config:cache` or `optimize`. It's always recommended to use your class or function name.

* Can I deactivate the middleware? Or check only XXX status?

Nope. If you are looking for total control, [use directly the Preloader package](https://github.com/DarkGhostHunter/Preloader/).

* Does the middleware works on testing?

Nope. The middleware is not registered if the application is running under Unit Testing environment.

* How can I know when a Preload script is successfully generated? 

When the Preload script is called, you will receive a `PreloadCalledEvent` instance with the compilation status (`true` on success, `false` on failure). You can [add a Listener](https://laravel.com/docs/events#registering-events-and-listeners) to dispatch an email or a Slack notification.

## License

This package is licenced by the [MIT License](LICENSE).
