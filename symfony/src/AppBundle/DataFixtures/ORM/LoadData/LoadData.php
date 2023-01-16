<?php
// src/AppBundle/DataFixtures/ORM/LoadData.php

namespace AppBundle\DataFixtures\ORM\LoadData;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
// import de la classe Faker
use Doctrine\Persistence\ObjectManager;
use Faker;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use App\Entity\User;
class LoadData extends AbstractFixture implements ContainerAwareInterface, FixtureInterface, OrderedFixtureInterface
{
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $entityManager)
    {
        // initialisation de l'objet Faker
        $faker = Faker\Factory::create('fr_FR');
        $faker->seed(1337);
        // créations des customers
        $customers = [];
        $entityManager = $this->container->get('doctrine')->getManager();
        for ($k=0; $k < 50; $k++) {
            $customers[$k] = new User();
            $customers[$k]
                ->setEmail($faker->email)
                ->setPassword($faker->password)
                ->setName($faker->firstname)
            ;
            //encode the plain password
            $customers[$k]->setPassword(
                $this->container->get('security.password_encoder')->encodePassword(
                    $customers[$k],
                    $customers[$k]->getPassword()
                )
            );
            $customers[$k]->setRegisterDate(new \DateTime());
            $customers[$k]->setAdmin(false);
            
            $entityManager->persist($customers[$k]);
            $entityManager->flush();
        }

    }

    public function getOrder()
    {
        return 1;
    }
}