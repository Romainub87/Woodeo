<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\Null_;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use App\Entity\UserSearch;
use App\Form\UserSearchType;
use Faker;

#[Route('/user')]
class UserController extends AbstractController
{
    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        $search = new UserSearch();
        $form = $this->createForm(UserSearchType::class, $search);
        $form->handleRequest($request);

        // get all users
        $users = $entityManager
            ->getRepository(User::class)
            ->findAll();

        //filter by email
        if ($search->getEmail()) {
            foreach ($users as $key => $user) {
                if (!str_contains($user->getEmail(), $search->getEmail())) {
                    unset($users[$key]);
                }
            }
        }

        //filter by role
        $users_list = $paginator->paginate(
            $users,
            $request->query->getInt('page', 1),
            5
        );

        $users_list->setTemplate('knp_paginator/sliding.html.twig');

        // render the form
        return $this->render('user/index.html.twig', [
            'users' => $users_list,
            'UserSearchForm' => $form->createView(),
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // only admin can create new user
        if (!$this->getUser() || !$this->getUser()->isAdmin()) {
            return $this->redirectToRoute('app_series_index');
        }

        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        // if form is submitted and valid, persist the user
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        // render the form
        return $this->renderForm('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }   

    #[Route('/gen/{id}', name: 'app_user_gen', methods: ['GET'])]
    public function gen(int $id, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher){
        //admin can generate users
            $faker = Faker\Factory::create();
            $seed = rand(0, 1000000000000000000);
            $faker->seed($seed);
            for($i=0;$i<$id;$i++){
                $user = new User();
                $user->setEmail('AutoTesteur'.$seed.$i.'.'.$faker->email);
                $user->setPassword($faker->password);
                $user->setName($faker->firstname);
                $user->setAdmin(false);
                $user->setRegisterDate(new \DateTime());
                $entityManager->persist($user);
                $entityManager->flush();
            }
            $entityManager->flush();

        return $this->redirectToRoute('app_admin_dashboard');
    }

    #[Route('autodel', name: 'app_user_autodel', methods: ['GET'])]
    public function autodel(EntityManagerInterface $entityManager){
        //admin can delete auto generated users
        $users = $entityManager
            ->getRepository(User::class) 
            ->findAll();
        foreach($users as $user){
            if(str_contains($user->getEmail(), 'AutoTesteur')){
                $entityManager->remove($user);
            }
        }
        $entityManager->flush();
        return $this->redirectToRoute('app_admin_dashboard');
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user, Request $req, EntityManagerInterface $entityManager, PaginatorInterface $pag): Response
    {
        // get all series of the user and paginate them
        $liste_series = $pag->paginate(
            $user->getSeries(),
            $req->query->getInt('page', 1),
            5
        );

        // if user is not logged in, redirect to index
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_series_index');
        }

        $usercritique = $user->getRates();

        $liste_series->setTemplate('knp_paginator/sliding.html.twig');

        // render the form
        return $this->render('user/show.html.twig', [
            'user' => $user,
            'seriesList' => $liste_series,
            'rates' => $usercritique,
            'em' => $entityManager,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        // only admin can edit user
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_series_index');
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        // if form is submitted and valid, persist the user
        if ($form->isSubmitted()) {

            $passHash =  $userPasswordHasher->hashPassword(
                $user,
                $form->get('password')->getData()
            );
            $user->setPassword($passHash);
            $entityManager->flush();

            return $this->redirectToRoute('app_user_show', ['id' => $user->getId()], Response::HTTP_SEE_OTHER);
        }

        // render the form
        return $this->renderForm('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/followers', name: 'app_user_see_followers', methods: ['GET', 'POST'])]
    public function see_follower(User $user, EntityManagerInterface $entityManager): Response
    {
        // only admin can promote user
        if (!$this->getUser() ) {
            return $this->redirectToRoute('app_series_index');
        }

        $listFollowers = $user->getFollowers();

        return $this->renderForm('user/followers.html.twig', [
            'followers' => $listFollowers,
        ]);
    }

    #[Route('/{id}/follows', name: 'app_user_see_follows', methods: ['GET', 'POST'])]
    public function see_follow(User $user, EntityManagerInterface $entityManager): Response
    {
        // only admin can promote user
        if (!$this->getUser() ) {
            return $this->redirectToRoute('app_series_index');
        }

        $listFollows = $user->getFollowing();

        return $this->renderForm('user/follows.html.twig', [
            'follows' => $listFollows,
        ]);
    }

    #[Route('/{id}/add_follower', name: 'app_user_add_follower', methods: ['GET', 'POST'])]
    public function add_follower(User $user, EntityManagerInterface $entityManager): Response
    {
        // only admin can promote user
        if (!$this->getUser() ) {
            return $this->redirectToRoute('app_series_index');
        }

        $this->getUser()->addFollowing($user);
        $entityManager->flush();

        return $this->redirectToRoute('app_user_show', ['id'=> $user->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/remove_follower', name: 'app_user_remove_follower', methods: ['GET', 'POST'])]
    public function remove_follower(User $user, EntityManagerInterface $entityManager): Response
    {
        // only admin can promote user
        if (!$this->getUser() ) {
            return $this->redirectToRoute('app_series_index');
        }

        $this->getUser()->removeFollowing($user);
        $entityManager->flush();

        return $this->redirectToRoute('app_user_show', ['id'=> $user->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/add_follow', name: 'app_user_add_follow', methods: ['GET', 'POST'])]
    public function add_follow(User $user, EntityManagerInterface $entityManager): Response
    {
        // only admin can promote user
        if (!$this->getUser() ) {
            return $this->redirectToRoute('app_series_index');
        }

        $this->getUser()->addFollower($user);
        $entityManager->flush();

        return $this->redirectToRoute('app_user_show', ['id'=> $user->getId()], Response::HTTP_SEE_OTHER);
    }



    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        // only admin can delete user
        if (!$this->getUser() || !$this->getUser()->isAdmin()) {
            return $this->redirectToRoute('app_series_index');
        }

        // if token is valid, delete the user
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/promote', name: 'app_user_promote', methods: ['GET'])]
    public function promote(User $user, EntityManagerInterface $entityManager): Response
    {
        // only admin can promote user
        if (!$this->getUser() || !$this->getUser()->isAdmin()) {
            return $this->redirectToRoute('app_series_index');
        }

        // set the user as admin
        $user->setAdmin(!$user->isAdmin());
        $entityManager->flush();

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
