<?php

use App\Core\Router;
use App\Controller\NewPhpRouteImp;

class RouterTest extends \PHPUnit\Framework\TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        // Create new Router instance
        $this->router = Router::getInstance();
    }

    public function testSingletonInstance()
    {
        // $this->assertEquals(true, true);
        $router1 = Router::getInstance();
        $router2 = Router::getInstance();

        $this->assertSame($router1, $router2, "Router::getInstance() should return the same instance.");
    }

    public function testAddRoute()
    {
        $this->router->add('home', '/home')->controller(NewPhpRouteImp::class, "phpInfo");

        $route = $this->router->fromNameToRoute('home');

        $this->assertNotNull($route, "Route should be added successfully.");
        $this->assertEquals('/home', $route['path'], "Route path should match.");
        $this->assertEquals(['GET'], $route['methods'], "Route methods should match.");
    }

    // public function testMatchRoute()
    // {
    //     $this->router->add('profile', '/user/{id}', ['GET']);

    //     $matchedRoute = $this->router->match('/user/42', 'GET');

    //     $this->assertNotNull($matchedRoute, "Route should be matched successfully.");
    //     $this->assertEquals('/user/{id}', $matchedRoute['route']['path'], "Matched route path should be correct.");
    //     $this->assertEquals('42', $matchedRoute['params']['id'], "Matched parameter should be correct.");
    // }

    // public function testFromNameToRoute()
    // {
    //     $this->router->add('dashboard', '/dashboard', ['GET']);

    //     $route = $this->router->fromNameToRoute('dashboard');

    //     $this->assertNotNull($route, "Route should be found by name.");
    //     $this->assertEquals('/dashboard', $route['path'], "Retrieved route path should be correct.");
    // }

    // public function testDuplicateRouteNameThrowsException()
    // {
    //     $this->router->add('unique', '/unique-path', ['GET']);

    //     $this->expectException(\Exception::class);
    //     $this->router->add('unique', '/different-path', ['POST']);
    // }
}
