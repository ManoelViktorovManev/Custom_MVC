<?php

namespace App\Controller;

use App\Core\BaseController;
use App\Core\Route;
use App\Core\Response;

//App\Controller\TestController
class TestController extends BaseController
{
    public function __construct() {}

    // /asdf ok
    // /asdf/{id} 
    // /asdf name: kekw_wtf


    #[Route('/normal/test')]
    public function testingNormalResponse()
    {
        // ok
        return new Response('Test text');
    }

    #[Route('/json')]
    public function testingJson()
    {
        // ok
        $data = ['1' => 2, '2' => 3];
        return $this->json([
            'asdf' => 'asdf',
            'obi4' => 'test'
        ]);
    }

    #[Route('/render')]
    public function testingRender()
    {
        // ok
        //'/user/allusers.html.twig'
        $array_data = [
            'some' => 'asdf',
            'test' => 'keks'
        ];

        // can`t parse $array as value
        return $this->render('show.html');
    }


    #[Route('/redirectToRoute')]
    public function testingRedirectToRoute()
    {

        // need to add code
        return $this->redirectToRoute('redirect_route');
    }

    #[Route('/redirect')]
    public function testingRedirect()
    {
        //fix it
        return $this->redirect('/render');
    }

    #[Route('/rand_number/{id}')]
    public function rand_number($id)
    {
        $number = random_int(0, $id);
        // ok
        return new Response('Succesfully we handle regex with random number = ' . $number);
    }
    #[Route('/asdf/f', name: 'redirect_route')]
    public function asdfff()
    {
        //return $this->arrayChecker()
        // need to add code
        return new Response('Succesfully we handle route name.');
    }

    #[Route('/asdf/f', name: 'redirect_route')]
    public function testResponseReturn()
    {
        //return $this->arrayChecker()
        // need to add code
        return 0;
    }

    #[Route('/test/{text}', name: 'redirect_route')]
    public function testCustomText($text)
    {

        return new Response("This is custom text " . $text);
    }
    #[Route('/multiply/{num1}/{num2?}')]
    public function multi($num1, $num2 = null)
    {
        if (!isset($num2)) {
            $num2 = 1;
        }
        $multiply = $num1 * $num2;
        return $this->render('show.html', ['content' => "Multiply $num1*$num2=$multiply"]);
    }
};
