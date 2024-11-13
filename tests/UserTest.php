<?php

use App\Model\User;

class UserTest extends \PHPUnit\Framework\TestCase
{

    public function testcreateUser()
    {
        $user = new User();
        $user->setName("Gosho");
        $user->setAge("22");
        $result = $user->insert();


        $this->assertTrue($result);
        $this->assertNotNull($user->getId());
    }

    public function testDeleteUser()
    {
        $user = new User();
        $user->setName("Gosho");
        $user->setAge("22");
        $user->insert();

        $newCreatedUserId = $user->getId();

        $result = $user->delete($newCreatedUserId);
        $this->assertTrue($result);
    }

    public function testUpdateUser()
    {
        $user = new User();
        $user->setName("Testing");
        $user->setAge("1234567");
        $user->insert();

        $newCreatedUserId = $user->getId();
        $user->setName('Changing_name');
        $user->setAge(988);


        $result = $user->update($newCreatedUserId);

        $this->assertTrue($result > 0); // $result>0 if update method have done an update

        $this->assertEquals('Changing_name', $user->getName());
        $this->assertEquals(988, $user->getAge());
    }
}
