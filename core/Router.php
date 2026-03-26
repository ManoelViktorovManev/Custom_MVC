<?php

namespace App\Core;

/**
 * Router class responsible for managing HTTP routes.
 *
 * The Router handles:
 * - Loading route definitions from controller attributes, config files, and YAML.
 * - Matching incoming URLs and HTTP methods to routes.
 * - Generating and retrieving routes by name.
 * - Managing route-controller associations.
 *
 * Implements a singleton pattern to ensure a single router instance across the application.
 *
 * @since 1.0
 */
class Router
{
    private array $routes = [];
    private array $routesName = [];
    private static $instance;


    /**
     * Router Constructor.
     *
     * Initializes the router by loading all predefined route definitions.
     * It loads routes from:
     * - Controllers (via `loadRoutes()` method).
     * - `config/routes.php` if it exists and is callable.
     * - `config/routes.yaml` using a YAML parser.
     * 
     * The loaded routes are stored in `$this->routes`.
     * @since 1.0
     */
    public function __construct()
    {
        // get Routes from Atributes
        $this->routes = $this->loadRoutes();

        // get Routes from /config/routes.php
        $routesConfig = require __DIR__ . '/../config/routes.php';
        if (is_callable($routesConfig)) {
            $routesConfig($this);
        }

        // get Routes from /config/routes.yaml
        $yamlRoutes = YamlParser::parseFile(dirname(__DIR__) . '/config/routes.yaml');
        foreach ($yamlRoutes as $name => $route) {
            $this->add($name, $route['path'])
                ->controller($route['controller'], $route['action']);
        }
    }

    /**
     * Retrieves the singleton instance of the Router.
     *
     * Ensures a single instance of `Router` is used across the application.
     *
     * @return Router The singleton Router instance.
     * @since 1.0
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
     * @since 1.0
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
                        if (array_key_exists($route->name, $this->routesName)) {
                            $element = $this->routesName[$route->name];
                            $controller = $element['controller'];
                            $action = $element['action'];
                            $method_of_current_class = $method->getName();
                            throw new \Exception(
                                "Error 404: The route name '{$route->name}' is already in use. 
                                Defined in: $controller::$action(). 
                                Conflict detected in: $controllerClass::$method_of_current_class(). 
                                Please choose a unique route name."
                            );
                        }
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
     * @since 1.0
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
     * @since 1.0
     */
    public function fromNameToRoute($name)
    {
        if (key_exists($name, $this->routesName)) {
            return $this->routesName[$name];
        }
        return null;
    }

    /**
     * Adds a new route to the routing system.
     *
     * This method registers a route with a unique name, a specified path, and supported HTTP methods.
     * If a route name already exists, an exception is thrown to enforce uniqueness.
     *
     * @param string $name The unique name of the route.
     * @param string $path The URL path pattern for the route.
     * @param array $methods The allowed HTTP methods for the route (default: GET).
     * @return $this Returns the current Router instance for method chaining.
     * 
     * @throws \Exception If the route name is already in use, an error is thrown.
     * @since 2.0
     */
    public function add($name, $path, $methods = ["GET"])
    {
        if (array_key_exists($name, $this->routesName)) {
            $element = $this->routesName[$name];
            $controller = $element['controller'];
            $action = $element['action'];
            throw new \Exception(
                "Error 404: The route name '{$name}' is already in use. 
                Defined in: $controller::$action(). 
                Please choose a unique route name."
            );
        }
        $this->routes[] = ['path' => $path, 'name' => $name, 'methods' => $methods];
        return $this;
    }

    /**
     * Associates a controller and method with the most recently added route.
     *
     * This method assigns a controller and method (action) to the last registered route.
     * It also updates the `routesName` array for quick access by route name.
     *
     * @param string $controller The fully qualified class name of the controller.
     * @param string $method_name The method in the controller that handles the route.
     * @return $this Returns the current Router instance for method chaining.
     * @since 2.0
     */
    public function controller($controller, $method_name)
    {
        $lastRoute = array_key_last($this->routes);
        if ($lastRoute !== null) {
            $this->routes[$lastRoute]['controller'] = $controller;
            $this->routes[$lastRoute]['action'] = $method_name;
            $this->routesName[$this->routes[$lastRoute]['name']] = end($this->routes);
        }
        return $this;
    }
};
