<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Series;
use App\Entity\Seasons;
use App\Entity\Episode;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use App\Entity\UserSearch;
use App\Form\UserSearchType;

#[Route('/user')]
class UserController extends AbstractController
{
    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        if (!$this->getUser() || !$this->getUser()->isAdmin()) {
            return $this->redirectToRoute('app_series_index');
        }

        $search = new UserSearch();
        $form = $this->createForm(UserSearchType::class, $search);
        $form->handleRequest($request);

        $users = $entityManager
            ->getRepository(User::class) 
            ->findAll();

        if ($search->getEmail()) {
            foreach ($users as $key => $user) {
                if (!str_contains($user->getEmail(), $search->getEmail()) ) {
                    unset($users[$key]);
                }
            }
        }

        $users_list = $paginator->paginate(
            $users,
            $request->query->getInt('page',1),
            7
        );

        return $this->render('user/index.html.twig', [
            'users' => $users_list,
            'UserSearchForm' => $form->createView(),
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->getUser() || !$this->getUser()->isAdmin()) {
            return $this->redirectToRoute('app_series_index');
        }

        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user,Request $req,  PaginatorInterface $pag): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_series_index');
        }

        $liste_series = $pag->paginate(
            $user->getSeries(),
            $req->query->getInt('page', 1),
            12
        );
        
        return $this->render('user/show.html.twig', [
            'user' => $user,
            'seriesList' => $liste_series,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if (!$this->getUser() || !$this->getUser()->isAdmin()) {
            return $this->redirectToRoute('app_series_index');
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if (!$this->getUser() || !$this->getUser()->isAdmin()) {
            return $this->redirectToRoute('app_series_index');
        }
        
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/promote', name: 'app_user_promote', methods: ['GET'])]
    public function promote(User $user, EntityManagerInterface $entityManager): Response
    {
        if (!$this->getUser() || !$this->getUser()->isAdmin()) {
            return $this->redirectToRoute('app_series_index');
        }

        $user->setAdmin(!$user->isAdmin());
        $entityManager->flush();

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
