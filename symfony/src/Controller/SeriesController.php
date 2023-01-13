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
        // search
        $search = new SeriesSearch();
        $form = $this->createForm(SeriesSearchType::class, $search);
        $form->handleRequest($request);

        // get all series
        $series = $entityManager
            ->getRepository(Series::class)
            ->createQueryBuilder('s')
            ->orderBy('s.poster', 'ASC');

        //filter by title
        if ($search->getTitre()) {
            $series
                ->andWhere('s.title LIKE :title')
                ->setParameter('title', '%'.$search->getTitre().'%');
        }

        //filter by genre
        if ($search->getGenre()) {
            $series
                ->innerJoin('s.genre', 'g')
                ->groupBy('s.id')
                ->andWhere('g.id LIKE :genre')
                ->setParameter('genre', $search->getGenre()->getId());
        }

        switch($search->getTrier()){
            case 1: // filter by year of start decreasing
                $series
                    ->orderBy('s.yearStart', 'DESC');
                break;
            case 2: // filter by year of start increasing   
                $series
                    ->orderBy('s.yearStart', 'ASC');
                break;
            case 3: // filter by rate decreasing
                $series
                    ->innerJoin('s.rate', 'er')
                    ->groupBy('s.id')
                    ->orderBy('AVG(er.value)', 'DESC');
                break;
            case 4: // filter by rate increasing
                $series
                    ->innerJoin('s.rate', 'er')
                    ->groupBy('s.id')
                    ->orderBy('AVG(er.value)', 'ASC');
                break;
            default:
                break;
        }

        // pagination
        $listeSeries = $paginator->paginate(
            $series,
            $request->query->getInt('page', 1),
            8
        );

        $listeSeries->setTemplate('knp_paginator/sliding.html.twig');

        //render the form
        return $this->render('series/index.html.twig', [
            'series' => $listeSeries,
            'SeriesSearchForm' => $form->createView(),
        ]);
    }

    #[Route('/random', name: 'app_series_random')]
    public function random(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        // get all series
        $series = $entityManager
        ->getRepository(Series::class)
        ->createQueryBuilder('s')
        ->orderBy('s.title', 'ASC');

        //series random
        $seriesRand = $entityManager
            ->getConnection()
            ->query('SELECT id, title, poster FROM series ORDER BY RAND() LIMIT 14')
            ->fetchAllAssociative();
        
        //convert poster to base64
        foreach ($seriesRand as &$serie) {
            $serie['poster'] = "data:image/png;base64,".base64_encode($serie['poster']);
        };
        
        //render the form
        return $this->render('series/random.html.twig', [
            'seriesRand' => $seriesRand,
        ]);
    }

    #[Route('/{id}', name: 'app_series_show', methods: ['GET'])]
    public function show(Series $series): Response
    {
        //get seasons of the series
        $seasons = $series->getSeasons();

        //render the form
        return $this->render('series/show.html.twig', [
            'series' => $series,
            'seasons' => $seasons,
        ]);
    }

    #[Route('/{id}/{user_id}/add', name: 'app_series_add', methods: ['GET', 'POST'])]
    public function add_serie(Series $series, EntityManagerInterface $entityManager): Response
    {
        //add series to user
        $this->getUser()->addSeries($series);
        $entityManager->flush();

        return $this->redirectToRoute('app_series_show', ['id' => $series->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/{user_id}/add_from_index', name: 'app_series_add_from_index', methods: ['GET', 'POST'])]
    public function add_serie_from_index(Series $series, EntityManagerInterface $entityManager): Response
    {
        //add series to user
        $this->getUser()->addSeries($series);
        $entityManager->flush();

        return $this->redirectToRoute('app_series_index', [], Response::HTTP_SEE_OTHER);
    }


    #[Route('/{id}/{user_id}/remove', name: 'app_series_remove', methods: ['GET', 'POST'])]
    public function remove_serie(Series $series, EntityManagerInterface $entityManager): Response
    {
        //remove series from user
        foreach ($this->getUser()->getEpisode() as $ep) {
            if ($ep->getSeason()->getSeries() == $series) {
                $this->getUser()->removeEpisode($ep);
            }
        }

        //remove series from user
        $this->getUser()->removeSeries($series);
        $entityManager->flush();

        return $this->redirectToRoute('app_series_show', ['id' => $series->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/{user_id}/remove_from_index', name: 'app_series_remove_from_index', methods: ['GET', 'POST'])]
    public function remove_serie_from_index(Series $series, EntityManagerInterface $entityManager): Response
    {
        //remove series and episodes from user
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
        //remove series and episodes from user
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
        //add series to user
        $this->getUser()->addSeries($series);
        $entityManager->flush();

        return $this->redirectToRoute('app_series_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/{user_id}/add_episode', name: 'app_episode_add', methods: ['GET', 'POST'])]
    public function add_episode(Episode $episode, EntityManagerInterface $entityManager): Response
    {
        //add episode to user
        $series = $episode->getSeason()->getSeries();
        $season = $episode->getSeason();

        //add episodes of the season
        foreach ($season->getEpisodes() as $episod) {

            if ($episod->getNumber() <= $episode->getNumber()) {
                $this->getUser()->addEpisode($episod);
            }
        }

        //add episodes of the previous seasons
        foreach ($series->getSeasons() as $seasons) {
            if ($episode->getSeason()->getNumber() > $seasons->getNumber()) {
                foreach ($seasons->getEpisodes() as $episod) {
                    $this->getUser()->addEpisode($episod);
                }
            }
        }
        //add series to user
        $this->getUser()->addSeries($series);
        $entityManager->flush();

        return $this->redirectToRoute('app_series_show', ['id' => $series->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/{user_id}/remove_episode', name: 'app_episode_remove', methods: ['GET', 'POST'])]
    public function remove_episode(Episode $episode, EntityManagerInterface $entityManager): Response
    {
        //remove episode to user
        $series = $episode->getSeason()->getSeries();
        $season = $episode->getSeason();

        //remove episodes of the season
        foreach ($season->getEpisodes() as $episod) {
            if ($episod->getNumber() <= $episode->getNumber()) {
                $this->getUser()->removeEpisode($episod);
            }
        }

        //remove episodes of the previous seasons
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
