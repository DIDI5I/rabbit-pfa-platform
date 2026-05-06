<?php

namespace App\Core;

class Route
{
    public string $method;
    public string $path;
    public $handler;
    public array $middleware = [];

    public function __construct(string $method, string $path, $handler)
    {
        $this->method = $method;
        $this->path = $path;
        $this->handler = $handler;
    }

    public static function build(string $method, string $path, $handler): self
    {
        return new self($method, $path, $handler);
    }

    public function only(array $middleware): self
    {
        $this->middleware = $middleware;

        return $this;
    }
}