<?php

namespace App\Controller;

use App\Entity\Series;
use App\Entity\SeriesSearch;
use App\Form\SeriesSearchType;
use App\Entity\Episode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/series')]
class SeriesController extends AbstractController
{
    #[Route('/', name: 'app_series_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {     
        $search = new SeriesSearch();
        $form = $this->createForm(SeriesSearchType::class, $search);
        $form->handleRequest($request);

        $series = $entityManager
            ->getRepository(Series::class)
            ->createQueryBuilder('s')
            ->orderBy('s.title', 'ASC');

        if ($search->getTitre()) {
            $series
                ->andWhere('s.title LIKE :title')
                ->setParameter('title', '%'.$search->getTitre().'%');
        }

        if ($search->getGenre()) {
            $series
                ->leftJoin('s.genre', 'g')
                ->addSelect('g')
                ->andWhere('g.id LIKE :genre')
                ->setParameter('genre', $search->getGenre()->getId());
        }

        $liste_series = $paginator->paginate(
            $series,
            $request->query->getInt('page', 1),
            8
        );

        $liste_series->setTemplate('knp_paginator/sliding.html.twig');

        return $this->render('series/index.html.twig', [
            'series' => $liste_series,
            'SeriesSearchForm' => $form->createView(),
        ]);
    }

    #[Route('/random', name: 'app_series_random')]
    public function random(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {

        $series = $entityManager
        ->getRepository(Series::class)
        ->createQueryBuilder('s')
        ->orderBy('s.title', 'ASC');

        $seriesRand = $entityManager
            ->getConnection()
            ->query('SELECT id, title, poster FROM series ORDER BY RAND() LIMIT 8')
            ->fetchAllAssociative();
        
        foreach ($seriesRand as &$serie) {
            $serie['poster'] = "data:image/png;base64,".base64_encode($serie['poster']);
        };
        
        return $this->render('series/random.html.twig', [
            'seriesRand' => $seriesRand,
        ]);
    }

    #[Route('/{id}', name: 'app_series_show', methods: ['GET'])]
    public function show(Series $series): Response
    {

        $seasons = $series->getSeasons();

        

        return $this->render('series/show.html.twig', [
            'series' => $series,
            'seasons' => $seasons,
        ]);
    }

    #[Route('/{id}/{user_id}/add', name: 'app_series_add', methods: ['GET', 'POST'])]
    public function add_serie(Series $series, EntityManagerInterface $entityManager): Response
    {

        $this->getUser()->addSeries($series);
        $entityManager->flush();

        return $this->redirectToRoute('app_series_show', ['id' => $series->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/{user_id}/add_from_index', name: 'app_series_add_from_index', methods: ['GET', 'POST'])]
    public function add_serie_from_index(Series $series, EntityManagerInterface $entityManager): Response
    {
        $this->getUser()->addSeries($series);
        $entityManager->flush();

        return $this->redirectToRoute('app_series_index', [], Response::HTTP_SEE_OTHER);
    }


    #[Route('/{id}/{user_id}/remove', name: 'app_series_remove', methods: ['GET', 'POST'])]
    public function remove_serie(Series $series, EntityManagerInterface $entityManager): Response
    {
        foreach ($this->getUser()->getEpisode() as $ep) {
            if ($ep->getSeason()->getSeries() == $series) {
                $this->getUser()->removeEpisode($ep);
            }
        }
        $this->getUser()->removeSeries($series);
        $entityManager->flush();

        return $this->redirectToRoute('app_series_show', ['id' => $series->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/{user_id}/remove_from_index', name: 'app_series_remove_from_index', methods: ['GET', 'POST'])]
    public function remove_serie_from_index(Series $series, EntityManagerInterface $entityManager): Response
    {

        foreach ($this->getUser()->getEpisode() as $ep) {
            if ($ep->getSeason()->getSeries() == $series) {
                $this->getUser()->removeEpisode($ep);
            }
        }
        $this->getUser()->removeSeries($series);
        $entityManager->flush();

        return $this->redirectToRoute('app_series_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/random/{id}/{user_id}/remove_from_random', name: 'app_series_remove_from_random', methods: ['GET', 'POST'])]
    public function remove_serie_from_random(Series $series, EntityManagerInterface $entityManager): Response
    {
        foreach ($this->getUser()->getEpisode() as $ep) {
            if ($ep->getSeason()->getSeries() == $series) {
                $this->getUser()->removeEpisode($ep);
            }
        }
        $this->getUser()->removeSeries($series);
        $entityManager->flush();

        return $this->redirectToRoute('app_series_index', [], Response::HTTP_SEE_OTHER);
    }


#[Route('/{id}/{user_id}/add_from_random', name: 'app_series_add_from_random', methods: ['GET', 'POST'])]
    public function add_serie_from_random(Series $series, EntityManagerInterface $entityManager): Response
    {

        $this->getUser()->addSeries($series);
        $entityManager->flush();

        return $this->redirectToRoute('app_series_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/{user_id}/add_episode', name: 'app_episode_add', methods: ['GET', 'POST'])]
    public function add_episode(Episode $episode, EntityManagerInterface $entityManager): Response
    {



        $series = $episode->getSeason()->getSeries();
        $season = $episode->getSeason();

        foreach ($season->getEpisodes() as $episod) {

            if ($episod->getNumber() <= $episode->getNumber()) {
                $this->getUser()->addEpisode($episod);
            }
        }

        foreach ($series->getSeasons() as $seasons) {
            if ($episode->getSeason()->getNumber() > $seasons->getNumber()) {
                foreach ($seasons->getEpisodes() as $episod) {
                    $this->getUser()->addEpisode($episod);
                }
            }
        }
        $this->getUser()->addSeries($series);
        $entityManager->flush();

        return $this->redirectToRoute('app_series_show', ['id' => $series->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/{user_id}/remove_episode', name: 'app_episode_remove', methods: ['GET', 'POST'])]
    public function remove_episode(Episode $episode, EntityManagerInterface $entityManager): Response
    {


        $series = $episode->getSeason()->getSeries();
        $season = $episode->getSeason();

        foreach ($season->getEpisodes() as $episod) {
            if ($episod->getNumber() <= $episode->getNumber()) {
                $this->getUser()->removeEpisode($episod);
            }
        }
        foreach ($series->getSeasons() as $seasons) {
            if ($episode->getSeason()->getNumber() <= $seasons->getNumber()) {
                foreach ($seasons->getEpisodes() as $episod) {
                    $this->getUser()->removeEpisode($episod);
                }
            }
        }
        $entityManager->flush();

        return $this->redirectToRoute('app_series_show', ['id' => $series->getId()], Response::HTTP_SEE_OTHER);
    }
    
}