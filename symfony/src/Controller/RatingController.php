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
use Doctrine\ORM\Query;
use Symfony\Component\Validator\Constraints\Length;

#[Route('/rating')]
class RatingController extends AbstractController
{
    #[Route('/', name: 'app_rating_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $ratings = $entityManager
            ->getRepository(Rating::class)
            ->findAll();

        return $this->render('rating/index.html.twig', [
            'ratings' => $ratings,
        ]);
    }

    #[Route('/new/{id}', name: 'app_rating_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Series $series, EntityManagerInterface $entityManager): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_series_index');
        }
        
        $query = $entityManager->createQuery(
            "SELECT COUNT(r) AS rate, r.id
             FROM App\Entity\Rating r 
             WHERE r.user = :user AND  r.series = :series");
            
        $query->setParameter('user', $this->getUser()->getId());
        $query->setParameter('series', $series->getId());
    
      
        $result= $query->execute();

        if ($result[0]['rate'] > 0) {
            return $this->redirectToRoute('app_rating_show', array('id' => $result[0]['id']));
        }

        $rating = new Rating();
        $form = $this->createForm(RatingType::class, $rating);
        $form->handleRequest($request);

        if (($form->isSubmitted() && $form->isValid())) {
            $rating->setUser($this->getUser());
            $rating->setSeries($series);
            $entityManager->persist($rating);
            $entityManager->flush();

            return $this->redirectToRoute('app_rating_show', array('id' => $rating->getId()));
        }

        return $this->renderForm('rating/new.html.twig', [
            'rating' => $rating,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_rating_show', methods: ['GET'])]
    public function show(Rating $rating): Response
    {
        return $this->render('rating/show.html.twig', [
            'rating' => $rating,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_rating_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Rating $rating, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RatingType::class, $rating);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_rating_show', ['id' => $rating->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('rating/edit.html.twig', [
            'rating' => $rating,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_rating_delete', methods: ['POST'])]
    public function delete(Request $request, Rating $rating, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$rating->getId(), $request->request->get('_token'))) {
            $entityManager->remove($rating);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_rating_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/rates/{id}', name: 'app_rating_rates', methods: ['GET'])]
    public function rates(Series $serie): Response
    {

        return $this->render('rating/rates.html.twig', [
            'seriesRates' => $serie->getRates(),
        ]);
    }
}
