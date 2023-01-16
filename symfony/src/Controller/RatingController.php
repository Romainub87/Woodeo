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

    #[Route('/new/{id}', name: 'app_rating_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Series $series, EntityManagerInterface $entityManager): Response
    {
        // Check if user is logged in
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_series_index');
        }
        
        // Check if user has already rated this series
        $query = $entityManager->createQuery(
            "SELECT COUNT(r) AS rate, r.id
             FROM App\Entity\Rating r 
             WHERE r.user = :user AND  r.series = :series");
            
        $query->setParameter('user', $this->getUser()->getId());
        $query->setParameter('series', $series->getId());
    
      
        $result= $query->execute();

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
            $rating->setAccepted(false);
            $rating->setValue(intval(round($rating->getValue()*2)));
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
        }
        else {
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
        if ($this->isCsrfTokenValid('delete'.$rating->getId(), $request->request->get('_token'))) {
            $entityManager->remove($rating);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_rating_new', ['id' => $id], Response::HTTP_SEE_OTHER);
    }

    #[Route('/rates/{id}', name: 'app_rating_rates', methods: ['GET', 'POST'])]
    public function rates(Request $request, Series $serie, EntityManagerInterface $em): Response
    {
        $form = $this->createFormBuilder()
            ->add('value', NumberType::class)
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) { 
            $rates = $em->getRepository(Rating::class)
                    ->createQueryBuilder('r')
                    ->where('r.series = :series')
                    ->setParameter('series', $serie)
                    ->andWhere('r.value = :value')
                    ->setParameter('value', round($form->get('value')->getData()))
                    ->getQuery()
                    ->getResult()
                    ;
        }
        else {
            $rates = $serie->getRates();
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
}
