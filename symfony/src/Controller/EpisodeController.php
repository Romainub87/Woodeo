<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Form\EpisodeType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Season;

#[Route('/episode')]
class EpisodeController extends AbstractController
{
    #[Route('/', name: 'app_episode_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $episodes = $entityManager
            ->getRepository(Episode::class)
            ->findAll();

        return $this->render('episode/index.html.twig', [
            'episodes' => $episodes,
        ]);
    }

    #[Route('/new/{season}', name: 'app_episode_new', methods: ['GET', 'POST'])]
    public function new(Season $season, Request $request, EntityManagerInterface $entityManager): Response
    {
        $episode = new Episode();
        $form = $this->createForm(EpisodeType::class, $episode);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $episode->setSeason($season);
            $entityManager->persist($episode);
            $entityManager->flush();

            return $this->redirectToRoute('app_season_edit', ['id' => $season->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('episode/new.html.twig', [
            'episode' => $episode,
            'season' => $season,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_episode_show', methods: ['GET'])]
    public function show(Episode $episode): Response
    {
        return $this->render('episode/show.html.twig', [
            'episode' => $episode,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_episode_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Episode $episode, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EpisodeType::class, $episode);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_season_edit', ['id' => $episode->getSeason()->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('episode/edit.html.twig', [
            'episode' => $episode,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_episode_delete', methods: ['POST'])]
    public function delete(Request $request, Episode $episode, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$episode->getId(), $request->request->get('_token'))) {
            $seasonId = $episode->getSeason()->getId();
            $entityManager->remove($episode);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_season_edit', ['id' => $seasonId], Response::HTTP_SEE_OTHER);
    }
}
