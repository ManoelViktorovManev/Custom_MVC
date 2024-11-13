<?php

use App\Core\Router;

class RouteTest extends \PHPUnit\Framework\TestCase
{

    public function testRouteMatchesWithoutOptionalParameter()
    {
        $router = new Router();
        $result = $router->match('/json', 'GET');

        $this->assertEquals('App\Controller\TestController', $result['route']['controller']);
        $this->assertEquals('testingJson', $result['route']['action']);

        $this->assertNull($result['route']['name']);
        $this->assertEmpty($result['params']);
    }

    public function testRouteMatchesWithOptionalParameter()
    {
        $router = new Router();
        $result = $router->match('/optional/', 'GET');
        //print_r($result);
        $this->assertEquals('/optional/{value?}', $result['route']['path']);
        $this->assertEmpty($result['params']['value']);
    }

    public function testRouteMatchesWithOptionalParameterSet()
    {
        $router = new Router();
        $result = $router->match('/optional/123', 'GET');
        //print_r($result);
        $this->assertEquals('/optional/{value?}', $result['route']['path']);
        $this->assertEquals(123, $result['params']['value']);
    }

    public function testNoMatchForDifferentRoute()
    {
        $router = new Router();
        $result = $router->match('/nonexistent', 'GET');
        $this->assertNull($result);
    }
}
