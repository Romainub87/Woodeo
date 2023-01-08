<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\FormError;

class AdminController extends AbstractController
{
    #[Route('/admin_dashboard', name: 'app_admin_dashboard')]
    public function index(Request $request, ManagerRegistry $doctrine): Response
    {
        if (!$this->getUser() || !$this->getUser()->isAdmin()) {
            return $this->redirectToRoute('app_series_index');
        }

        $form = $this->createFormBuilder()
            ->add('email')
            ->getForm();
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $doctrine->getRepository(User::class)->findOneBy(array('email' => $form->get('email')->getData()));
            if ($user == null) {
                $form->addError(new FormError('Utilisateur introuvable'));
            } else {
                $user->setAdmin(true);
                $entityManager = $doctrine->getManager();
                $entityManager->persist($user);
                $entityManager->flush();
                return $this->redirectToRoute('app_admin_dashboard');
            }
        }

        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
            'form' => $form->createView(),
        ]);
    }
}
