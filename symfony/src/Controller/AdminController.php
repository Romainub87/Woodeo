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
        return $this->redirectToRoute('app_user_index');

        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }
}
