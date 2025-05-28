<?php

use App\Core\YamlParser as Yaml;
use App\Core\Router;
use App\Controller\NewPhpRouteImp;

class NewRouteImpTest extends \PHPUnit\Framework\TestCase
{


    public function testParseYamlString()
    {
        $expected = [
            'info' =>
            [
                'path' => '/phpInfo',
                'controller' => 'App\Controller\NewPhpRouteImp',
                'action' => 'phpInfo'
            ],
            'test_yaml_parameter' =>
            [
                'path' => '/yamlparam/{param}',
                'controller' => 'App\Controller\NewPhpRouteImp',
                'action' => 'yamlParam'
            ]
        ];

        $result = Yaml::parseFile(dirname(__DIR__) . '/config/routes.yaml');

        $this->assertEquals($expected, $result);
    }


    public function testPHPRouteConfig()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Error 404: The route name 'izwajdane12' is already in use.");

        $router = new Router();
        $router->add('izwajdane12', '/minus/{param1}/{param2}')
            ->controller(NewPhpRouteImp::class, 'minusNa2Chisla');
    }
}
