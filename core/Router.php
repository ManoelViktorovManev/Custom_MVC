<?php

namespace App\Core;

use App\Core\Route;


class Router
{
    private array $routes = [];
    private array $routesName = [];
    private static $instance;


    /**
     * Constructor for Router.
     *
     * Initializes the router by loading all route definitions from controllers.
     */
    public function __construct()
    {
        $this->routes = $this->loadRoutes();
    }

    /**
     * Retrieves the singleton instance of the Router.
     *
     * Ensures a single instance of `Router` is used across the application.
     *
     * @return Router The singleton Router instance.
     */
    public static function getInstance(): Router
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Loads route definitions by scanning controller files.
     *
     * Iterates over all controllers to extract route attributes from controller methods.
     * Each route contains path, allowed methods (GET, POST, etc.), the controller, action,
     * and optionally, a route name for easier reference.
     *
     * @return array An array of loaded routes with their details.
     */
    private function loadRoutes(): array
    {
        $routes = [];
        $controllers = glob('controller/*.php'); // Scan controller files and get every file.

        foreach ($controllers as $controllerFile) {

            $controllerClass = 'App\\Controller\\' . basename($controllerFile, '.php');

            $reflectionClass = new \ReflectionClass($controllerClass);

            foreach ($reflectionClass->getMethods() as $method) {
                $attributes = $method->getAttributes(Route::class);

                foreach ($attributes as $attribute) {
                    $route = $attribute->newInstance();

                    $routes[] = [
                        'path' => $route->path,   // user url path => /path
                        'methods' => $route->methods, //GET, POST ... 
                        'controller' => $controllerClass, // Namespace of Controller => App\Controller\...
                        'action' => $method->getName(), // Method from Controller => App\Controller\...::method()
                        'name' => $route->name, //route name => /path, name: 'something'
                    ];
                    if (isset($route->name)) {
                        // store the name attributes
                        $this->routesName[$route->name] = end($routes);
                    }
                }
            }
        }

        return $routes;
    }

    /**
     * Matches a given URL and HTTP method to a defined route.
     *
     * Uses regex patterns to match URL parameters in the route definition, extracting values
     * and verifying if the HTTP method matches. If a match is found, returns the route data and
     * any extracted parameters.
     *
     * @param string $url The URL to match.
     * @param string $method The HTTP method (e.g., GET, POST) to match.
     * @return array|null An associative array with route and parameters if matched, or null if no match.
     *
     */
    public function match(string $url, string $method): ?array
    {
        foreach ($this->routes as $route) {
            $routePattern = preg_replace('/{(\w+)\?}/', '(?P<\1>[^/]*?)', $route['path']);  // Optional parameter regex
            $routePattern = preg_replace('/{(\w+)}/', '(?P<\1>[^/]+)', $routePattern);     // Regular parameters


            $routePattern = '#^' . $routePattern . '/?$#';

            if (preg_match($routePattern, $url, $matches) && in_array($method, $route['methods'])) {
                $params = [];

                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = isset($value) && $value !== '' ? $value : null;
                    }
                }
                return [
                    'route' => $route,  // Връщаме целия маршрут
                    'params' => $params // Връщаме параметрите, които са намерени в URL-то
                ];
            }
        }
        return null; // No match found
    }

    /**
     * Retrieves a route by its name.
     *
     * Allows routes to be referenced by name, providing an easier way to retrieve
     * route definitions without needing to know the exact path.
     *
     * @param string $name The name of the route to retrieve.
     * @return array|null The route definition if the name exists, or null if not found.
     *
     */
    public function fromNameToRoute($name)
    {
        if (key_exists($name, $this->routesName)) {
            return $this->routesName[$name];
        }
        return null;
    }
};
