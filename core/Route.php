<?php

namespace App\Core;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Route
{

    private $route;
    public function __construct(
        public string $path,
        public ?string $name = null,
        public array $methods = ['GET']
    ) {}
}
