<?php

use App\Controller\NewController;
use App\Controller\TestController;
use App\Core\Response;

class ControllerTest extends \PHPUnit\Framework\TestCase
{
    public function testTestingJson()
    {
        $controller = new TestController();

        $response = $controller->testingJson();

        $this->assertInstanceOf(Response::class, $response);

        $content = $response->getContent();

        $data = json_decode($content, true);

        $this->assertArrayHasKey('asdf', $data);
        $this->assertEquals('asdf', $data['asdf']);
        $this->assertArrayHasKey('obi4', $data);
        $this->assertEquals('test', $data['obi4']);
    }

    public function testTestResponseReturn()
    {
        $controller = new TestController();
        $response = $controller->testResponseReturn();
        $this->assertNotInstanceOf(Response::class, $response);
    }

    public function testtestCustomText()
    {
        $controller = new TestController();
        $response = $controller->testCustomText('Trying');
        $this->assertInstanceOf(Response::class, $response);

        $content = $response->getContent();
        $this->assertEquals('This is custom text Trying', $content);
    }
}
