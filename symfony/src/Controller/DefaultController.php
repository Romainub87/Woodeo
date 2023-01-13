<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\SeriesSearch;
use Symfony\Component\HttpFoundation\Request;
use App\Form\SeriesSearchType;

class DefaultController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function index(Request $request): Response
    {
        $search = new SeriesSearch();
        $form = $this->createForm(SeriesSearchType::class, $search);
        $form->handleRequest($request);

        //render the form
        return $this->render('default/index.html.twig', [
            'controller_name' => 'DefaultController',
            'form' => $form->createView(),
        ]);
    }
}
