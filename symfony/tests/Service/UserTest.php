<?php

namespace App\Tests\Service;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserTest extends KernelTestCase
{
    public function testSomething()
    {
        $user = new User();
        $user->setEmail('test@email');
        $user->setPassword('testPassword');
        $user->setName('testName');

        $this->assertEquals('test@email', $user->getEmail());
        $this->assertEquals('testPassword', $user->getPassword());
        $this->assertEquals('testName', $user->getName());
        $this->assertEquals(false, $user->isAdmin());

        //test admin
        $user->setAdmin(true);
        $this->assertEquals(true, $user->isAdmin());
        $user->setAdmin(false);

        //test password
        $user->setPassword(('f'));
        $this->assertFalse('f' == $user->getEmail());
        $user->setPassword(('testPassword'));   
        $this->assertEquals('testPassword', $user->getPassword());

        //test register date
        $user->setRegisterDate(new \DateTime());
        $this->assertTrue(new \DateTime()>=$user->getRegisterDate()); 

        //test email
        $user->setEmail('');
        $this->assertFalse('' == $user->getEmail());
        $user->setEmail('test@email');
        $this->assertEquals('test@email', $user->getEmail());
    }
}

