<?php

namespace App\Core;

/**
 * Application core class responsible for bootstrapping the MVC workflow.
 *
 * This class initializes core components such as the database, entity layer,
 * and router, then resolves the current HTTP request to a matching route.
 * If a route is found, the corresponding controller action is executed and
 * its response is sent to the client.
 *
 * @since   1.0
 */
class App
{
    private Router $router;
    private DataBaseComponent $dbComponent;

    /**
     * App constructor.
     *
     * Initializes core application components:
     * - Database connection
     * - Entity manipulation layer
     * - Router
     *
     * Then immediately attempts to resolve and execute the current request.
     * Any exception thrown during initialization or routing is caught and
     * returned as an HTTP 404 response.
     * @since   1.0
     */
    public function __construct()
    {
        try {
            $this->dbComponent = DataBaseComponent::getInstance();
            EntityManipulation::getInstance($this->dbComponent);
            $this->router = new Router();
            $this->checkForExistingResponse();
        } catch (\Exception $e) {
            $message = $e->getMessage();
            error_log("\033[31m$message\033[0m");
            $this->executeResponse(new Response($message, 404));
        }
    }

    /**
     * Retrieves the URL path requested by the user.
     *
     * This function returns the full request URI, including any path and query parameters.
     * It is useful for identifying which route the user is attempting to access.
     *
     * @return string The requested URL path (e.g., "/home").
     *
     * @since   1.0
     */
    public function getServerRoute()
    {
        return $_SERVER['REQUEST_URI'];
    }

    /**
     * Retrieves the HTTP request method used by the user.
     *
     * This function returns the HTTP method of the request, such as "GET", "POST", "PUT", or "DELETE".
     * It is useful for handling different types of requests and routing logic based on the request method.
     *
     * @return string The HTTP method of the current request (e.g., "GET", "POST").
     * @since   1.0
     */
    public function getServerMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Handles user requests by matching them to defined routes and executing the appropriate controller action.
     *
     * This method checks if the requested URL and HTTP method correspond to any existing route. If a match is found, 
     * it initializes the specified controller and executes the designated action. It logs the execution result and 
     * ensures that the controller action returns a valid `Response` object. If no route matches the request, or if 
     * the controller action fails to return a `Response`, appropriate errors are logged, and exceptions are thrown.
     *
     * @throws \Exception If no route matches the requested URL or if the controller action does not return a `Response` object.
     * @return void
     *
     * Workflow:
     * - **Step 1**: Retrieve the user's requested URL and HTTP method.
     * - **Step 2**: Attempt to match the request with a defined route using `Router::match()`.
     * - **Step 3**: If a route is found:
     *      - Instantiate the controller and call the action method.
     *      - Check that the action returns a `Response` object. 
     *      - Log a success message and execute the response.
     * - **Step 4**: If no matching route is found or an invalid response is returned, log an error and throw an exception.
     *
     * @since   1.0
     */
    public function checkForExistingResponse()
    {

        $userRequestUrl = $this->getServerRoute();
        $userRequestMethod = $this->getServerMethod();

        if ($userRequestMethod == "OPTIONS") {
            http_response_code(200);
            exit;
        }
        $route = $this->router->match($userRequestUrl, $userRequestMethod);
        /*
            $route can be:
                array => return [
                    'route' => $route,  // Returns the route object (See App\Core\Router::match())
                    'params' => $params // Returns the parameters to be called on the method
                ]; 
                or null
        */
        if ($route) {
            $controller = new $route['route']['controller']();
            $functionToBeCalled = $route['route']['action'];

            // Call the controller method
            $response = call_user_func_array([$controller, $functionToBeCalled], $route['params']);

            // We check if it returns Response object always
            if ($response instanceof Response) {

                // it is not succsessfull
                if ($response->getStatusCode() >= 400) {
                    $message = $response->getContent();
                    error_log("\033[31m$message\033[0m");
                }
                $this->executeResponse($response);
            } else {
                // HANDLE IF RESPONSE IS NOT RESPONSE OBJECT
                $controller_name = $route['route']['controller'];
                $path = $route['route']['path'];
                error_log("\033[31mClass method $controller_name::$functionToBeCalled() for route $path is not returning Response object\033[0m");
                $this->executeResponse(new Response("Class method $controller_name::$functionToBeCalled() for route $path is not returning Response object", 404));
            }
            return;
        }
        // IF THERE IS NO SUCH FILE FINDED

        error_log("\033[31mNot existing route $userRequestUrl\033[0m");
        $this->executeResponse(new Response("Error 404: Not existing route $userRequestUrl", 404));
    }


    /**
     * Executes the given response by calling its `executeResponse` method.
     *
     * This function accepts a `Response` object and triggers its `executeResponse`
     * method, which is responsible for sending the response to the client.
     *
     * @param Response $response The response object that will be executed.
     * 
     * @return void This function does not return any value.
     * @since   1.0
     */
    public function executeResponse(Response $response)
    {
        $response->executeResponse();
    }
};
