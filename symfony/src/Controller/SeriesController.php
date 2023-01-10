<?php

namespace App\Controller;

use App\Entity\Series;
use App\Form\SeriesType;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use Doctrine\ORM\EntityRepository;

#[Route('/series')]
class SeriesController extends AbstractController
{
    #[Route('/', name: 'app_series_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        $series = $entityManager
            ->getRepository(Series::class)
            ->findBy(array(), array('title' => 'ASC'));
        
        $liste_series = $paginator->paginate(
            $series,
            $request->query->getInt('page', 1),
            8
        );
                
        return $this->render('series/index.html.twig', [
            'series' => $liste_series,
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

        return $this->redirectToRoute('app_series_show', ['id'=>$series->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/random', name: 'app_series_random', methods: ['GET'])]
    public function random(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        $series = $entityManager
            ->getRepository(Series::class);
         
        
        $liste_seriesRandom = $paginator->paginate(
            $series,
            $request->query->getInt('page', 1),
            10
        );
                
        return $this->render('series/random.html.twig', [
            'seriesRandom' => $liste_seriesRandom,
        ]);
    }
}
