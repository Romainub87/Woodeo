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
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Entity\Genre;
use App\Entity\Actor;
use App\Entity\ExternalRating;
use App\Entity\ExternalRatingSource;
use App\Entity\Source;

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
            ->orderBy('s.title', 'ASC');

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

        if ($search->getNoteMin() || $search->getNoteMax() || $search->getTrier() == 3 || $search->getTrier() == 4) {
            $series
                ->innerJoin('s.rate', 'ra')
                ->groupBy('s.id');
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
                    ->orderBy('AVG(ra.value)', 'DESC');
                break;
            case 4: // filter by rate increasing
                $series
                    ->orderBy('AVG(ra.value)', 'ASC');
                break;
            default:
                break;
        }

        if ($search->getDateMin()) {
            $series
                ->andWhere('s.yearStart >= :dateMin')
                ->setParameter('dateMin', $search->getDateMin());
        }

        if ($search->getDateMax()) {
            $series
                ->andWhere('s.yearEnd <= :dateMax')
                ->setParameter('dateMax', $search->getDateMax());
        }

        if ($search->getNoteMin()) {
            $series
                ->andHaving('AVG(ra.value) >= :noteMin')
                ->setParameter('noteMin', (($search->getNoteMin()*2)-0.5));
        }

        if ($search->getNoteMax()) {
            $series
                ->andHaving('AVG(ra.value) <= :noteMax')
                ->setParameter('noteMax', $search->getNoteMax()*2);
        }

        // pagination
        $listeSeries = $paginator->paginate(
            $series,
            $request->query->getInt('page', 1),
            8,
            ['wrap-queries' => true, 'distinct' => false]
        );

        $listeSeries->setTemplate('knp_paginator/sliding.html.twig');

        //render the form
        return $this->render('series/index.html.twig', [
            'series' => $listeSeries,
            'SeriesSearchForm' => $form->createView(),
        ]);
    }

    #[Route('/new/{imdbId}', name: 'app_series_new', methods: ['GET', 'POST'])]
    public function new(string $imdbId, EntityManagerInterface $entityManager, HttpClientInterface $client): Response
    {
        $response = $client->request('GET', 'http://www.omdbapi.com/?i='.$imdbId.'&apikey=a2996c2f&type=series')->toArray();
        $trailer = $client->request('GET', 'https://imdb-api.com/en/API/YoutubeTrailer/k_g0p41mv2/'.$imdbId)->toArray();
        $serie = new Series();
        $serie->setTitle($response['Title']);
        $serie->setPlot($response['Plot']);
        $serie->setImdb($response['imdbID']);
        $serie->setPoster(file_get_contents($response['Poster']));
        $director = $response['Director'];
        if ($director == 'N/A') {
            $serie->setDirector(null);
        } else {
            $serie->setDirector($director);
        }
        $serie->setYoutubeTrailer($trailer['videoUrl']);
        $serie->setAwards($response['Awards']);
        $serie->setYearStart(intval(explode('–', $response['Year'])[0]));
        $yearEnd = explode('–', $response['Year'])[1];
        if (strlen($yearEnd) < 4) {
            $serie->setYearEnd(null);
        } else {
            $serie->setYearEnd(intval($yearEnd));
        }

        $genres = explode(', ', $response['Genre']);
        foreach ($genres as $genre) {
            $genre = $entityManager->getRepository(Genre::class)->findOneBy(['name' => $genre]);
            $serie->addGenre($genre);
        }

        $actors = explode(', ', $response['Actors']);
        foreach ($actors as $actor) {
            $actor_name = $actor;
            $actor = $entityManager->getRepository(Actor::class)->findOneBy(['name' => $actor]);
            if ($actor == null) {
                $actor = new Actor();
                $actor->setName($actor_name);
                $entityManager->persist($actor);
            }
            $serie->addActor($actor);
        }

        $rate = new ExternalRating();
        $rate->setValue($response['imdbRating']);
        $source = $entityManager->getRepository(ExternalRatingSource::class)->findOneBy(['name' => 'IMDB']);
        if ($source == null) {
            $source = new ExternalRatingSource();
            $source->setName('IMDB');
            $entityManager->persist($source);
        }
        $rate->setSource($source);
        $rate->setVotes(intval(str_replace(',','',$response['imdbVotes'])));
        $entityManager->persist($rate);
        $serie->addExternalRate($rate);

        $entityManager->persist($serie);
        $entityManager->flush();

        return $this->redirectToRoute('app_admin_dashboard');
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
