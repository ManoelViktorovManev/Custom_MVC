<?php

namespace App\Controller;

use App\Core\BaseController;
use App\Core\Response;

class NewPhpRouteImp extends BaseController
{


    public function minusNa2Chisla($param1, $param2): Response
    {

        return $this->json([
            'Param1' => $param1,
            'param2' => $param2,
            "result" => $param1 - $param2
        ]);
    }
    public function revers($param): Response
    {
        return $this->json([
            'Param' => $param,
            'Reverse' => -$param,
        ]);
    }

    public function yamlTest(): Response
    {
        return $this->json([
            "Yaml" => true
        ]);
    }
    public function phpInfo(): Response
    {
        return new Response(
            phpinfo()
        );
    }

    public function yamlParam($param): Response
    {
        return new Response(
            $param
        );
    }
};
