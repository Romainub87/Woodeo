<?php

namespace App\Controller;

use App\Entity\Rating;
use App\Form\RatingType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Series;
use App\Entity\User;
use Faker;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

#[Route('/rating')]
class RatingController extends AbstractController
{
    #[Route('/', name: 'app_rating_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $ratings = $entityManager
            ->getRepository(Rating::class)
            ->findAll();

        //render the form
        return $this->render('rating/index.html.twig', [
            'ratings' => $ratings,
        ]);
    }

    #[Route('/dashboard', name: 'app_rating_accepting', methods: ['GET'])]
    public function accepting(EntityManagerInterface $entityManager): Response
    {
        // only admin can accept ratings
        if (!$this->getUser() || !$this->getUser()->isAdmin()) {
            return $this->redirectToRoute('app_rating_index');
        }

        $ratings = $entityManager
            ->getRepository(Rating::class)
            ->createQueryBuilder('r')
            ->leftJoin('r.user', 'u')
            ->leftJoin('r.series', 's')
            ->select('r', 'u.name', 's.title')
            ->where('r.accepted = 0')
            ->andWhere('r.comment is not null')
            ->orderBy('r.id', 'DESC')
            ->getQuery()
            ->getResult();

        $dateActuelle = new \DateTime();
        //render the form
        return $this->render('rating/accepting.html.twig', [
            'ratings' => $ratings,
            'dateActuelle' => $dateActuelle,
        ]);
    }

    /**
     * Accept the rate to be show by the admin
     */
    #[Route('/{id}/accept', name: 'app_rating_accept', methods: ['GET'])]
    public function accept(Rating $r, EntityManagerInterface $entityManager): Response
    {
        $r->setAccepted(1);
        $entityManager->flush();

        return $this->redirectToRoute('app_rating_accepting', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Delete the rate, by the admin
     */
    #[Route('/{id}/refuse', name: 'app_rating_refuse', methods: ['GET'])]
    public function refuse(Rating $r, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($r);
        $entityManager->flush();

        return $this->redirectToRoute('app_rating_accepting', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/new/{id}', name: 'app_rating_new', methods: ['GET', 'POST'])]
    public function new (Request $request, Series $series, EntityManagerInterface $entityManager): Response
    {
        // Check if user is logged in
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_series_index');
        }

        // Check if user has already rated this series
        $query = $entityManager->createQuery(
            "SELECT COUNT(r) AS rate, r.id
             FROM App\Entity\Rating r 
             WHERE r.user = :user AND  r.series = :series"
        );

        $query->setParameter('user', $this->getUser()->getId());
        $query->setParameter('series', $series->getId());


        $result = $query->execute();

        // If user has already rated this series, redirect to the rating page
        if ($result[0]['rate'] > 0) {
            return $this->redirectToRoute('app_rating_show', array('id' => $result[0]['id']));
        }

        // If user has not rated this series, create a new rating
        $rating = new Rating();
        $form = $this->createForm(RatingType::class, $rating);
        $form->handleRequest($request);

        // If form is submitted and valid, persist the rating
        if (($form->isSubmitted() && $form->isValid())) {
            // If there is no comment, accepted is true
            $rating->setAccepted($rating->getComment() == null);
            $rating->setValue(intval(round($rating->getValue() * 2)));
            $rating->setUser($this->getUser());
            $rating->setSeries($series);
            $entityManager->persist($rating);
            $entityManager->flush();

            return $this->redirectToRoute('app_rating_show', array('id' => $rating->getId()));
        }

        // Render the form
        return $this->renderForm('rating/new.html.twig', [
            'rating' => $rating,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_rating_show', methods: ['GET'])]
    public function show(Rating $rating): Response
    {
        $dateActuelle = new \DateTime();
        $interval = $rating->getDate()->diff($dateActuelle);
        if ($interval->format("%a") > 0) {
            $interval = $interval->format("Il y a %a jours");
        } else {
            $interval = $interval->format("Il y a %H h %I m et %S s");
        }
        // Render the form
        return $this->render('rating/show.html.twig', [
            'rating' => $rating,
            'interval' => $interval,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_rating_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Rating $rating, EntityManagerInterface $entityManager): Response
    {
        // Check if user is logged in
        $form = $this->createForm(RatingType::class, $rating);
        $form->handleRequest($request);

        // If form is submitted and valid, persist the rating
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_rating_show', ['id' => $rating->getId()], Response::HTTP_SEE_OTHER);
        }

        // Render the form
        return $this->renderForm('rating/edit.html.twig', [
            'rating' => $rating,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_rating_delete', methods: ['POST'])]
    public function delete(Request $request, Rating $rating, EntityManagerInterface $entityManager): Response
    {
        // Check if user is logged in
        $id = $rating->getSeries()->getId();
        if ($this->isCsrfTokenValid('delete' . $rating->getId(), $request->request->get('_token'))) {
            $entityManager->remove($rating);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_rating_new', ['id' => $id], Response::HTTP_SEE_OTHER);
    }

    #[Route('/rates/{id}', name: 'app_rating_rates', methods: ['GET', 'POST'])]
    public function rates(Request $request, Series $serie, EntityManagerInterface $em): Response
    {
        $form = $this->createFormBuilder()
            ->add('value', NumberType::class, [
                'label' => 'Note',
                'required'=> 'false',
            ])
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) { 
            $rates = $em->getRepository(Rating::class)
                    ->createQueryBuilder('r')
                    ->where('r.series = :series')
                    ->setParameter('series', $serie)
                    ->andWhere('r.value = :value')
                    ->setParameter('value', round($form->get('value')->getData()*2))
                    ->getQuery()
                    ->getResult()
                    ;
        }
        else {
            $rates = $em->getRepository(Rating::class)
                    ->createQueryBuilder('r')
                    ->where('r.series = :series')
                    ->setParameter('series', $serie)
                    ->orderBy('r.date', 'desc')
                    ->getQuery()
                    ->getResult()
                    ;
        }


        $dateActuelle = new \DateTime();
        // Render the form
        return $this->render('rating/rates.html.twig', [
            'seriesRates' => $rates,
            'series' => $serie,
            'dateActuelle' => $dateActuelle,
            'valueForm' => $form->createView(),
        ]);
    }

    #[Route('rating_gen/{id}', name: 'app_rating_gen', methods: ['GET', 'POST'])]
    public function rating_gen(EntityManagerInterface $entityManager)
    {
        $series = $entityManager
            ->getRepository(Series::class)
            ->findAll();

        //get in an array all users where email contains AutoTesteur
        $users = $entityManager
            ->getRepository(User::class)
            ->findAll();

        $userBot = [];
        foreach ($users as $user) {
            if (strpos($user->getEmail(), 'AutoTesteur') !== false) {
                $userBot[] = $user;
            }
        }

        $faker = Faker\Factory::create('fr_FR');

        if (count($userBot) == 0) {
            return $this->redirectToRoute('app_rating_noUser');
        }



        //create 1000 ratings
        for ($i = 0; $i < 1000; $i++) {
            $user = $userBot[rand(0, count($userBot) - 1)];
            $serie = $series[rand(0, count($series) - 1)];
            $comment = $faker->realText(100);
            $rating = new Rating();
            $rating->setAccepted(true);
            $rating->setValue(rand(0, 10));
            $rating->setDate(new \DateTime());
            $rating->setComment($comment);
            $user->addRate($rating);
            $serie->addRate($rating);
            $entityManager->persist($rating);
        }
        $entityManager->flush();

        return $this->redirectToRoute('app_user_index');
    }

    #[Route('rating_autodel', name: 'app_rating_del', methods: ['GET', 'POST'])]
    public function rating_del(EntityManagerInterface $entityManager)
    {

        //admin can delete rating of auto generated users
        $entityManager->createQueryBuilder()
            ->delete('App\Entity\Rating', 'r')
            ->where('r.user IN (SELECT u.id FROM App\Entity\User u WHERE u.email LIKE :email)')
            ->setParameter('email', '%AutoTesteur%')
            ->getQuery()
            ->execute();

        return $this->redirectToRoute('app_user_index');
    }

    #[Route('noUser', name: 'app_rating_noUser', methods: ['GET', 'POST'])]
    public function noUser(EntityManagerInterface $entityManager)
    {
       
        return $this->render('rating/ratesNoUser.html.twig');
    }
}
