<?php

namespace App\Core;

/**
 * Base controller class for all application controllers.
 *
 * This abstract class provides common helper methods that can be used
 * by all concrete controllers in the application. It centralizes
 * frequently used response-related logic such as:
 *
 * - Returning JSON responses
 * - Rendering HTML views with injected parameters
 * - Performing HTTP redirects
 * - Generating URLs from named routes
 *
 * By extending this class, controllers can focus solely on handling
 * request logic and business rules, while response formatting and
 * routing utilities are handled consistently across the application.
 *
 * This class is intended to be extended and should not be instantiated
 * directly.
 *
 * @since 1.0 
 */
abstract class BaseController
{

    /**
     * Converts data to JSON format and returns it as a `Response` with JSON headers.
     *
     * This method takes an array or data structure, encodes it to JSON, and sets the
     * Content-Type to "application/json" in the HTTP headers.
     *
     * @param mixed $data The data to be converted to JSON format.
     * @param int $statusCode HTTP status code for the response (default: 200).
     * @return Response The JSON-encoded data with JSON-specific headers.
     * @since 1.0
     */
    public function json($data, $statusCode = 200): Response
    {
        $response = new Response(json_encode($data), $statusCode);
        $response->setHeader('Content-Type', 'application/json');
        return $response;
    }


    /**
     * Renders an HTML view and injects variables into it.
     *
     * This method renders an HTML file, allowing variables to be passed into the template.
     * These variables can then be accessed within the HTML view using double braces (e.g., `{{ variableName }}`).
     *
     * @param string $view The path to the view file located in the /view directory.
     * @param array $params Key-value pairs of variables to be injected into the view.
     * @return Response The rendered HTML view content wrapped in a `Response` object.
     * @since 1.0
     */
    public function render($view, $params = []): Response
    {
        //output buffering
        ob_start();
        extract($params);

        include dirname(__DIR__) . "/view/$view";

        $content = ob_get_clean();
        $this->arrayChecker($params, $content);
        return new Response($content);
    }


    /**
     * Replaces array values recursively in the content template.
     *
     * This helper method iterates through a multi-dimensional array of parameters, replacing any 
     * placeholders in the view's HTML with actual values. Nested array elements are accessed 
     * via dot notation (e.g., `{{ user.name }}`).
     *
     * @param array $array The parameters array.
     * @param string &$content The HTML content to modify.
     * @param string $currentKey Key used to generate dot notation keys for nested arrays.
     * @return void
     * @since 1.0
     *
     */
    private function arrayChecker($array, &$content, $currentKey = '')
    {
        foreach ($array as $key => $value) {
            // check if $curentKey is set.
            $fullKey = $currentKey ? "$currentKey.$key" : $key;
            if (is_array($value)) {
                $this->arrayChecker($value, $content, $fullKey);
                continue;
            }
            $content = str_replace("{{ $fullKey }}", htmlspecialchars($value), $content);
        }
    }

    /**
     * Redirects to a named route with optional parameters.
     *
     * This method generates a URL for a given route name by replacing placeholders in the route
     * path with provided parameter values. A `Response` is returned with a 302 status code to redirect
     * the user to the constructed URL.
     *
     * @param string $route The name of the route to redirect to.
     * @param array $params Parameters to replace placeholders in the route path.
     * @return Response A 302 redirect `Response` to the constructed URL.
     * @throws Exception If the route name does not exist.
     * @since 1.0
     */
    public function redirectToRoute($route, $params = []): Response
    {
        $router = Router::getInstance();
        $getRouteNameInstance = $router->fromNameToRoute($route);
        if ($getRouteNameInstance) {
            $path = $getRouteNameInstance['path'];
            foreach ($params as $key => $value) {
                $path = str_replace("{" . $key . "}", $value, $path);
            }
            return new Response('', 302, ['Location' => $path]);
        }

        throw new \Exception("The route name '$route' is not existing");
    }


    /**
     * Redirects to a specified URL path.
     *
     * This method generates a redirect response to a specified URL path, using a 302 status code.
     * Unlike `redirectToRoute`, this method does not rely on named routes.
     *
     * @param string $route The URL path to redirect to.
     * @return Response A 302 redirect `Response` to the specified URL path.
     * @since 1.0
     * 
     */
    public function redirect($route): Response
    {
        // route => url path itself
        return new Response('', 302, ['Location' => $route]);
    }

    /**
     * Generates a URL path for a named route with optional parameters.
     *
     * This method constructs a URL path by retrieving the route associated with the given route name
     * and replacing placeholders in the route path with provided parameter values.
     *
     * @param string $route The name of the route.
     * @param array $parameters Parameters to replace placeholders in the route path.
     * @return string The generated URL path.
     * @throws Exception If the route name does not exist.
     * @since 1.0
     */
    public function generateUrl($route, $parameters = []): string
    {
        $router = Router::getInstance();
        $getRouteNameInstance = $router->fromNameToRoute($route);
        if ($getRouteNameInstance) {
            $path = $getRouteNameInstance['path'];
            foreach ($parameters as $key => $value) {
                // Replace each placeholder in the route path
                $path = str_replace("{" . $key . "}", $value, $path);
            }
            return $path;
        }
        throw new \Exception("The route name '$route' is not existing");
    }
};
