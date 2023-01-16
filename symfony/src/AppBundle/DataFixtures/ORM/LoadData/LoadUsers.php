<?php
// src/AppBundle/DataFixtures/ORM/LoadUsers.php

namespace AppBundle\DataFixtures\ORM\LoadData;

use AppBundle\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Faker;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadUsers extends AbstractFixture implements ContainerAwareInterface, FixtureInterface, OrderedFixtureInterface
{
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $em)
    {
        // initialisation de l'objet Faker
        // on peut préciser en paramètre la localisation, 
        // pour avoir des données qui semblent "françaises"
        $faker = Faker\Factory::create('fr_FR');
        //get the entity manager
        $em = $this->container->get('doctrine')->getManager();
        $populator = new Faker\ORM\Doctrine\Populator($faker, $em);
        $populator->addEntity(User::class, 50); 
        // le deuxième paramètre (10) correspond au nombre d'objets qui vont être créés
        $insertedPKs = $populator->execute();
    }

    public function getOrder()
    {
        return 1;
    }
}