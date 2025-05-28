<?php

use App\Core\Response;

class ResponseTest extends \PHPUnit\Framework\TestCase
{
    public function testResponseInitialization()
    {
        $response = new Response('Hello', 200, ['Content-Type' => 'text/plain']);
        $this->assertEquals('Hello', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/plain', $response->getHeader('Content-Type'));
    }

    public function testSetters()
    {
        $response = new Response();
        $response->setContent('Updated');
        $response->setStatusCode(404);
        $response->setHeader('Content-Type', 'application/json');

        $this->assertEquals('Updated', $response->getContent());
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeader('Content-Type'));
    }

    // public function testExecuteResponse()
    // {
    //     // Create Response instance
    //     $response = new Response("Test Content", 200, ["Content-Type" => "text/plain"]);

    //     // Start output buffering
    //     ob_start();

    //     // Call executeResponse in a controlled way
    //     try {
    //         $response->executeResponse();
    //     } catch (\Exception $e) {
    //         // Catch the forced exit() call (simulating its effect)
    //     }

    //     // Get the buffered output
    //     $output = ob_get_clean();

    //     // Assertions
    //     $this->assertEquals("Test Content", $output, "Response content should be echoed correctly.");
    // }
}
