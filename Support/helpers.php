<?php

function base_path(string $path = ''): string
{
    return BASE_DIR . ltrim($path, '/');
}

function dd($var){
    var_dump($var);
    die();
}