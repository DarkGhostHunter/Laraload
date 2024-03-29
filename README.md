## This package has been superseeded by [Laragear/Preload](https://github.com/Laragear/Preload).

Please migrate to the new package.

---

# Laraload

Effortlessly create a PHP Preload Script for your Laravel project.

## Requirements

* Laravel 6.x, 7.x or 8.x (Lumen _may_ work too)
* PHP 7.4.3, PHP 8.0 or later
* `ext-opcache`

> The Opcache extension is not enforced by the package. Just be sure to enable it in your project's PHP main process.

## Installation

Call composer and you're done.

```bash
composer require darkghosthunter/laraload
```

## What is Preloading? What does this?

Preloading is a new feature for PHP. It "compiles" a list of files into memory, thus making the application code _fast_ without warming up. For that to work, it needs to read a PHP script that "uploads" these files into memory, at process startup.

This allows the first Requests to avoid cold starts, where all the scripts must be loaded by PHP at that moment. Since this step is moved to the process startup, first Requests become _faster_ as all needed scripts are already in memory.

This package wraps the Preloader package that generates a preload file. Once it's generated, you can point the generated list into your `php.ini`:

```ini
opcache.preload = 'www/app/storage/preload.php';
```

After that, the next time PHP starts, this list of files will be preloaded.

## Usage

By default, this package constantly recreates your preload script each 500 requests in `storage/preload.php`. That's it. But you want the details, don't you?

1. A global terminable middleware checks for non-error response.
2. Then it calls a custom *Condition* class.
3. If the *Condition* evaluates to `true`, the Preload Script is generated.
4. A `PreloadCalledEvent` is fired with the generation status.

## Configuration

Some people may not be happy with the "default" behaviour. Luckily, you can configure your own way to generate the script.

First publish the configuration file:

```bash
php artisan vendor:publish --provider="DarkGhostHunter\Laraload\LaraloadServiceProvider"
```

Let's check the config array:

```php
<?php

return [
    'enable' => env('LARALOAD_ENABLE'),
    'condition' => \DarkGhostHunter\Laraload\Conditions\CountRequests::class,
    'output' =>  storage_path('preload.php'),
    'memory' => 32,
    'use_require' => false,
    'autoload' => base_path('vendor/autoload.php'),
    'ignore-not-found' => true,
];
```

#### Enable

```php
<?php

return [
    'enable' => env('LARALOAD_ENABLE'),
];
```

By default, Laraload will be automatically enabled on production environments. You can forcefully enable or disable it using an environment variable set to `true` or `false`, respectively.

```dotenv
LARALOAD_ENABLE=true
```

#### Condition

```php
<?php

return [
    'condition' => 'App\MyCustomCondition@handle',
];
```

This package comes with a _simple_ condition class that returns `true` every 500 requests, which triggers the script generation. 

You can define your own condition class to generate the Preload script. This will be called after the request is handled to the browser, and it will be resolved by the Service Container.

The condition is called the same way as a Controller action: as an invokable class or using _Class@action_ notation.

#### Output

```php
<?php

return [
    'output' => '/var/www/preloads/my_preload.php',
];
```
 
By default, the script is saved in your storage path, but you can change the filename and path to save it as long PHP has permissions to write on it. Double-check your file permissions.

#### Memory Limit

```php
<?php

return [
    'memory' => 64,
];
```

For most applications, 32MB is fine as a preload limit, but you may fine-tune it for your project specifically.

#### Method

```php
<?php

return [
    'use_require' => true,
    'autoload' => base_path('vendor/autoload.php'),
];
```

Opcache allows to preload files using `require_once` or `opcache_compile_file()`.

Laraload uses `opcache_compile_file()` for better manageability on the files preloaded. Some unresolved links may output warnings at startup, but nothing critical.

Using `require_once` will "execute" all files, resolving all the links (imports, parent classes, traits, interfaces, etc.) before compiling it, and may output heavy errors on files that shouldn't be executed. Depending on your application, you may want to use one over the other.

If you plan use `require_once`, ensure you have set the correct path to the Composer Autoloader, since it will be used to resolve classes, among other files.

### Ignore not found files

```php
<?php

return [
    'ignore-not-found' => true,
];
```

Version 2.1.0 and onward ignores non-existent files by default. This may work for files created by Laravel at runtime and actively cached by Opcache, but that on deployment are absent, like [real-time facades](https://laravel.com/docs/facades#real-time-facades).

You can disable this for any reason, which will throw an Exception if any file is missing, but is recommended leaving it alone unless you know what you're doing. 

### Include & Exclude directories

For better manageability, you can now append or exclude files from directories using the [Symfony Finder](https://symfony.com/doc/current/components/finder.html), which is included in this package, to retrieve a list of files inside of them with better filtering options.

To do so, add an `array` of directories, or register a callback receiving a Symfony Finder instance to further filter which files you want to append or exclude. You can do this in your App Service Provider by using the `Laravel` facade (or injecting Laraload).

```php

use Symfony\Component\Finder\Finder;
use Illuminate\Support\ServiceProvider;
use DarkGhostHunter\Laraload\Facades\Laraload;

class AppServiceProvider extends ServiceProvider
{
    // ...
    
    public function boot()
    {
        Laraload::append(function (Finder $find) {
            $find->in('www/app/vendor/name/package/src')->name('*.php');
        });
        
        Laraload::exclude(function (Finder $find) {
            $find->in('www/app/resources/views')->name('*.blade.php');
        });
    }
}
```

### FAQ

* **Can I disable Laraload?**

[Yes.](#enable)

* **Do I need to restart my PHP Server to pick up the changes?**

Absolutely. Generating the script is not enough, PHP won't pick up the changes if the script path is empty or the PHP process itself is not restarted **completely**. You can schedule a server restart with CRON or something.

* **The package returns errors when I used it!**
  
Check you're using the latest PHP stable version (critical), and Opcache is enabled. Also, check your storage directory is writable.

As a safe-bet, you can use the safe preloader script in `darkghosthunter/preloader/helpers/safe-preloader.php` and debug the error.

If you're sure this is an error by the package, [open an issue](https://github.com/DarkGhostHunter/Laraload/issues/new) with full details and stack trace. If it's a problem on the Preloader itself, [issue it there](https://github.com/DarkGhostHunter/Preloader/issues).

* **Why I can't use something like `php artisan laraload:generate` instead? Like a [Listener](https://laravel.com/docs/events) or [Scheduler](https://laravel.com/docs/scheduling)?**

Opcache is not enabled when using PHP CLI, and if it is, it will gather wrong statistics. You must let the live application generate the list automatically _on demand_.

* **Does this excludes the package itself from the list?**

It does not: since the underlying Preloader package may be not heavily requested, it doesn't matter if its excluded or not. The files in Laraload are also not excluded from the list, since these are needed to trigger the Preloader itself without hindering performance. 

* **I activated Laraload but my application still doesn't feel _fast_. What's wrong?**

Laraload creates a preloading script, but **doesn't load the script into Opcache**. Once the script is generated, you must include it in your `php.ini` - currently there is no other way to do it. This will take effect only at PHP process startup.

If you still _feel_ your app is slow, remember to benchmark your app, cache your config and views, check your database queries and API calls, and queue expensive logic, among other things. You can also use [Laravel Octane](https://github.com/laravel/octane) on [RoadRunner](https://roadrunner.dev/).

* **How the list is created?**

Basically: the most hit files in descending order. Each file consumes memory, so the list is _soft-cut_ when the files reach a given memory limit (32MB by default).

* **You said "_soft-cut_", why is that?**

Each file is loaded using `opcache_compile_file()`. If the last file is a class with links outside the list, PHP will issue some warnings, which is normal and intended, but it won't compile the linked files if these were not added before. 

* **Can I just put all the files in my project?**

You shouldn't. Including all the files of your application may have diminishing returns compared to, for example, only the most requested. You can always benchmark your app yourself to prove this is wrong for your exclusive case.

* **Can I use a Closure for my condition?**

No, you must use your default condition class or your own class, or use `Class@method` notation.

* **Can I deactivate the middleware? Or check only XXX status?**

Nope. If you are looking for total control, [use directly the Preloader package](https://github.com/DarkGhostHunter/Preloader/).

* **Does the middleware works on unit testing?**

Nope. The middleware is not registered if the application is running under Unit Testing environment.

* **How can I know when a Preload script is successfully generated?**

When the Preload script is called, you will receive a `PreloadCalledEvent` instance with the compilation status (`true` on success, `false` on failure). You can [add a Listener](https://laravel.com/docs/events#registering-events-and-listeners) to dispatch an email or a Slack notification.

If there is a bigger problem, your application logger will catch the exception.

* **Why now I need to use a callback to append/exclude files, instead of a simple array of files?**

This new version uses Preloader 2, which offers greater flexibility to handle files inside a directory. This approach is incompatible with just issuing directly an array of files, but is more convenient in the long term. Considering that appending and excluding files mostly requires pin-point precision, it was decided to leave it as method calls for this kind of flexibility.

* **How can I change the number of hits, cache or cache key for the default condition?**

While I encourage you to create your own condition, you can easily change them by adding a [container event](https://laravel.com/docs/8.x/container#container-events) to your `AppServiceProvider.php`, under the `register()` method.

```php
$this->app->when(\DarkGhostHunter\Laraload\Conditions\CountRequests::class)
     ->needs('$hits')
     ->give(1500);
```

## License

This package is licenced by the [MIT License](LICENSE).
