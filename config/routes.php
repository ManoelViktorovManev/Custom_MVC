<?php

namespace App\Config;

use App\Core\Router;
use App\Controller\NewPhpRouteImp;

return function (Router $routes): void {
    $routes->add('izwajdane12', '/minus/{param1}/{param2}')
        ->controller(NewPhpRouteImp::class, 'minusNa2Chisla');

    $routes->add('obratno', '/reverse/{param}')
        ->controller(NewPhpRouteImp::class, 'revers');
};
