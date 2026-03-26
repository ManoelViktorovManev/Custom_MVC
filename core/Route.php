<?php

namespace App\Core;

/**
 * Attribute used to define a route for a controller method.
 *
 * This PHP 8+ attribute allows you to annotate controller methods with
 * routing information, including the URL path, HTTP methods, and
 * an optional route name.
 *
 * Usage example:
 * ```php
 * #[Route('/users', name: 'user_list', methods: ['GET', 'POST'])]
 * public function listUsers() { ... }
 * ```
 *
 * @since 1.0
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class Route
{

    public function __construct(
        public string $path,
        public ?string $name = null,
        public array $methods = ['GET']
    ) {}
}
