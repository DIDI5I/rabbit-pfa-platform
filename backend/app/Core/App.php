<?php

namespace App\Core;

class App
{
    protected static Container $container;

    public static function bind(Container $container): void
    {
        static::$container = $container;
    }

    public static function resolve(string $key)
    {
        return static::$container->resolve($key);
    }

    public static function container(): Container
    {
        return static::$container;
    }
}