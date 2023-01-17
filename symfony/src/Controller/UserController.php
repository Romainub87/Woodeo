<?php

namespace App\Controller;

use App\Entity\Rating;
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
use App\Form\PasswordResetType;
use Doctrine\ORM\Mapping\Id;
use Faker;
use App\Entity\Series;

#[Route('/user')]
class UserController extends AbstractController
{
    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        if($this->getUser() and $this->getUser()->isSuspended()){
            return $this->redirectToRoute('app_logout');
        }

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
    public function new (Request $request, EntityManagerInterface $entityManager): Response
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
    public function gen(int $id, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        //admin can generate users
        $faker = Faker\Factory::create();
        $seed = rand(0, 1000000000000000000);
        $faker->seed($seed);
        for ($i = 0; $i < $id; $i++) {
            $user = new User();
            $user->setSuspended(0);
            $user->setEmail('AutoTesteur' . $seed . $i . '.' . $faker->email);
            $user->setPassword($faker->password);
            $user->setName($faker->firstname);
            $user->setAdmin(false);
            $user->setRegisterDate(new \DateTime());
            $entityManager->persist($user);
        }
        $entityManager->flush();

        return $this->redirectToRoute('app_user_index');
    }

    #[Route('/genSuivi/{id}', name: 'app_user_genSuivi', methods: ['GET'])]
    public function genSuivi(int $id, EntityManagerInterface $entityManager)
    {
        //get all series
        $series = $entityManager->getRepository('App\Entity\Series')->findAll();
        $users = $entityManager->getRepository('App\Entity\User')->findAll();
        $userBot = [];
        foreach ($users as $user) {
            if (strpos($user->getEmail(), 'AutoTesteur') !== false) {
                $userBot[] = $user;
            }
        }
        

        //add some random series to user
        for($i = 0; $i < 1000; $i++){
            $user = $userBot[rand(0, count($userBot) - 1)];
            $series[rand(0, count($series) - 1)]->addUser($user);
            $series[rand(0, count($series) - 1)]->addUser($user);
            $entityManager->persist($user);
        }
        $entityManager->flush();
        return $this->redirectToRoute('app_user_index');
    }

    #[Route('/autoDelSuivi', name: 'app_user_autoDelSuivi', methods: ['GET'])]
    public function autoDelSuivi(EntityManagerInterface $entityManager)
    {
        //admin can delete all series from auto generated users
        $users = $entityManager->getRepository('App\Entity\User')->findAll();
        foreach ($users as $user) {
            if (strpos($user->getEmail(), 'AutoTesteur') !== false) {
                $user->removeAllSeries();
                $entityManager->persist($user);
            }
        }
        $entityManager->flush();
        return $this->redirectToRoute('app_user_index');
    }

    #[Route('autodel', name: 'app_user_autodel', methods: ['GET'])]
    public function autodel(EntityManagerInterface $entityManager){
        //admin can delete rating of auto generated users
        $entityManager->createQueryBuilder()
            ->delete('App\Entity\Rating', 'r')
            ->where('r.user IN (SELECT u.id FROM App\Entity\User u WHERE u.email LIKE :email)')
            ->setParameter('email', '%AutoTesteur%')
            ->getQuery()
            ->execute();

        $users = $entityManager->getRepository('App\Entity\User')->findAll();
        foreach ($users as $user) {
            if (strpos($user->getEmail(), 'AutoTesteur') !== false) {
                $user->removeAllSeries();
                $entityManager->persist($user);
                }
            }
        $entityManager->flush();

        //admin can delete auto generated users
        $entityManager->createQueryBuilder()
            ->delete('App\Entity\User', 'u')
            ->where('u.email LIKE :email')
            ->setParameter('email', '%AutoTesteur%')
            ->getQuery()
            ->execute();
        return $this->redirectToRoute('app_user_index');
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user, Request $req, EntityManagerInterface $entityManager, PaginatorInterface $pag): Response
    {
        // get all series of the user and paginate them

        $series = $entityManager->getRepository(Series::class)->createQueryBuilder('s')
            ->select('s', 'COUNT(e) as nbEpisodes', 'COUNT(DISTINCT se) as nbSeasons')
            ->innerJoin('s.user', 'u')
            ->leftJoin('s.seasons', 'se')
            ->leftJoin('se.episodes', 'e')
            ->groupBy('s.id')
            ->where('u.id = :id')
            ->setParameter('id', $user->getId());

        $liste_series = $pag->paginate(
            $series,
            $req->query->getInt('page', 1),
            5
        );

        // if user is not logged in, redirect to index
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_series_index');
        }

        $usercritique = $entityManager
            ->getRepository(Rating::class)
            ->createQueryBuilder('r')
            ->select('s.title', 'r.value', 'r.comment')
            ->innerJoin('r.series', 's')
            ->where('r.accepted = 1')
            ->andWhere('r.user = :id')
            ->setParameter('id', $user->getId())
            ->orderBy('r.id', 'DESC')
            ->getQuery()
            ->getResult();

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

    #[Route('/{id}/reset', name: 'app_user_reset_mdp', methods: ['GET', 'POST'])]
    public function reset(Request $request, User $user, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        // only admin can edit user
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_series_index');
        }

        $form = $this->createForm(PasswordResetType::class, $user);
        $form->handleRequest($request);

        // if form is submitted and valid, persist the user
        if ($form->isSubmitted()) {

            $passHash =  $userPasswordHasher->hashPassword(
                $user,
                $form->get('password')->getData()
            );
            $user->setPassword($passHash);

            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        // render the form
        return $this->renderForm('user/reset.html.twig', [
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

    #[Route('/{id}/suspend', name: 'app_user_suspend', methods: ['GET'])]
    public function suspend(User $user, EntityManagerInterface $entityManager): Response
    {
        // only admin can promote user
        if (!$this->getUser() || !$this->getUser()->isAdmin()) {
            return $this->redirectToRoute('app_series_index');
        }

        // set the user as admin
        $user->setSuspended(!$user->isSuspended());
        $user->removeAllFollowers();
        $user->removeAllFollowings();
        $entityManager->flush();

        $entityManager->createQueryBuilder()
            ->delete('App\Entity\Rating','r')
            ->andWhere('r.user = :user_id')
            ->setParameter('user_id', $user->getId())
            ->getQuery()
            ->execute();

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
