<?php

namespace App\Controller;

use App\Core\BaseController;
use App\Core\Route;
use App\Core\Response;
use App\Model\User;

class NewController extends BaseController
{


    #[Route('/aaa/{id}', name: 'shibi')]
    function test($id)
    {

        return new Response('ok, brawo ' . $id);
    }

    #[Route('/calculate/{number1}/{number2?}')]
    function calculate($number1, $number2 = null)
    {

        if (!isset($number2)) {
            $number2 = 0;
        }
        return $this->render('show.html', ['title' => 'Calculate', "content" => "Calculate =" . $number1 + $number2]);
    }

    #[Route('/zari')]
    function Url()
    {
        $user = new User();
        $user->setName('Mani');
        $user->setAge('22');
        //$user->findAll();




        $url = $this->generateUrl('shibi', ['id' => 1223232]);
        // echo $url;
        //$array = ['asdf' => 'ako', 'bob' => 'minawa', 'kekw' => ['as' => 123, 'test' => 122, 'final' => ['opa' => 'final text']]];
        //return $this->render('zari.html', ['url' => $url, 'text' => 'zari', 'age' => 123]);
        //return new Response('asdf');
        return $this->redirect($url);
    }
    #[Route('/db', name: 'asdf')]
    function DBTest()
    {
        $user = new User();
        $result = [];
        // insert ok
        // $user->setName('Stefko');
        // $user->setAge('29');
        // $result = $user->insert();

        // findAll() ok
        //$users = $user->findAll();

        // findById() ok
        $result = $user->findById(4);

        // update() ok
        // $user->setName('kekw');
        // $user->setAge('888');
        // $result = $user->update(3);

        //delete() ok  
        // $result = $user->delete(3);


        //customSql() ok
        // $sql = 'SELECT name FROM user WHERE id = 1';
        // $result = $user->customSQL($sql);


        //return new Response($result);
        return $this->json($result);
    }
    #[Route('/db/create/{name}/{age}')]
    function stefko($name, $age)
    {
        $user = new User();
        $user->setName($name);
        $user->setAge($age);
        $result = $user->insert();
        echo ($result . " " . $user->getId());

        return new Response("???");
    }

    #[Route('/test')]
    function stefkod()
    {
        return $this->redirectToRoute('sadsadsadasd');
    }
    #[Route('/optional/{value?}')]
    function optional_test($value)
    {
        return $this->redirectToRoute('sadsadsadasd');
    }
};
