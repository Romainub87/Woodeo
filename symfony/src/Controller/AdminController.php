<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class AdminController extends AbstractController
{
    #[Route('/admin_dashboard', name: 'app_admin_dashboard')]
    public function index(Request $request, ManagerRegistry $doctrine, HttpClientInterface $client): Response
    {
        $form = $this->createFormBuilder()
            ->add('title', TextType::class)
            ->getForm();
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) { 
            $response = $client->request('GET', 'http://www.omdbapi.com/?t='.$form->get('title')->getData().'&apikey=a2996c2f&type=series')->toArray();
            return $this->render('admin/index.html.twig', [
                'response' => $response,
                'form' => $form->createView(),
            ]);
        }
        

        return $this->render('admin/index.html.twig', [
            'form' => $form->createView(),  
            'response' => null,
        ]);
    }
}
