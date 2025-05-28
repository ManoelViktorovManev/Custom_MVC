<?php

namespace App\Core;

use App\Core\Router;
use App\Core\Response;

use Exception;

class App
{
    private Router $router;
    private DataBaseComponent $dbComponent;
    private EntityManipulation $entity;

    public function __construct()
    {
        try {
            $this->dbComponent = DataBaseComponent::getInstance();
            $this->entity = EntityManipulation::getInstance($this->dbComponent);
            $this->router = new Router();
            $this->checkForExistingResponse();
        } catch (Exception $e) {
            echo "<h1>" . ($e->getMessage()) . "</h1>";
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
     *
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
     */
    public function checkForExistingResponse()
    {

        $userRequestUrl = $this->getServerRoute();
        $userRequestMethod = $this->getServerMethod();
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

            $controller_name = $route['route']['controller'];
            $path = $route['route']['path'];

            // We check if it returns Response object always
            if ($response instanceof Response) {
                // $this->log->setMessage('info', "Successfully executet $controller_name::$functionToBeCalled() for route $path");
                $this->executeResponse($response);
            } else {
                // HANDLE IF RESPONSE IS NOT RESPONSE OBJECT
                // $this->log->setMessage('error', "Class method $controller_name::$functionToBeCalled() for route $path is not returning Response object");
                throw new \Exception("Class method $controller_name::$functionToBeCalled() for route $path is not returning Response object");
            }
            return;
        }
        // IF THERE IS NO SUCH FILE FINDED
        // $this->log->setMessage('error', "Error 404: Not existing route $userRequestUrl");
        throw new \Exception("Error 404: Not existing route $userRequestUrl");
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
     */
    public function executeResponse(Response $response)
    {
        $response->executeResponse();
    }
};
