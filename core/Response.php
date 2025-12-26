<?php

namespace App\Core;

/**
 * Represents an HTTP response.
 *
 * This class encapsulates all data required to send a response back to the client,
 * including the response body, HTTP status code, and headers.
 *
 * It provides a simple API for building responses and sending them in a
 * framework-controlled manner.
 *
 * @since 1.0
 */
class Response
{
    protected $content;
    protected $statusCode;
    protected $headers;

    /**
     * Constructor for Response.
     *
     * Initializes the response with content, status code, and headers.
     *
     * @param string $content The response body content.
     * @param int $statusCode The HTTP status code (e.g., 200 for OK, 404 for Not Found).
     * @param array $headers An associative array of headers (e.g., ['Content-Type' => 'text/html']).
     * @since 1.0
     */
    public function __construct($content = '', $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     * Retrieves the response content.
     *
     * @return string The content of the response.
     * @since 1.0
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Sets the response content.
     *
     * @param string $content The content/body of the response.
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Retrieves the response status code.
     *
     * @return int The HTTP status code of the response.
     * @since 1.0
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Sets the response status code.
     *
     * @param int $statusCode The HTTP status code to be set (e.g., 200, 404).
     * @since 1.0
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
    }

    /**
     * Retrieves a specific header or all headers.
     *
     * @param string|null $name The name of the header to retrieve (optional).
     * @return mixed The header value if `$name` is specified, or all headers as an array if null.
     * @since 1.0
     */
    public function getHeader($name = null)
    {
        if (isset($name)) {
            return $this->headers[$name];
        }
        return $this->headers;
    }

    /**
     * Sets a header for the response.
     *
     * @param string $name The name of the header (e.g., 'Content-Type').
     * @param string $value The value of the header (e.g., 'application/json').
     * @since 1.0
     */
    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
    }

    /**
     * Sends the response to the client.
     *
     * Loops through the headers and sends each one to the client, followed by
     * the response content. Terminates script execution after sending the response.
     * @since 1.0
     *
     */
    public function executeResponse()
    {
        http_response_code($this->getStatusCode());

        foreach ($this->getHeader() as $name => $value) {
            header("$name: $value", true);
        }
        echo ($this->getContent());
        exit;
    }
}
