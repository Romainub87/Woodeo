<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Entity\Series;

class AdminController extends AbstractController
{
    #[Route('/admin_dashboard', name: 'app_admin_dashboard')]
    public function index(Request $request, ManagerRegistry $doctrine, HttpClientInterface $client): Response
    {
        $form = $this->createFormBuilder()
            ->add('title', TextType::class)
            ->getForm();
        $form->handleRequest($request);
        
        $response = null;
        $already_added = null;

        if ($form->isSubmitted() && $form->isValid()) { 
            $response = $client->request('GET', 'http://www.omdbapi.com/?t='.$form->get('title')->getData().'&apikey=a2996c2f&type=series')->toArray();
            if ($response['Response'] == 'True') {
                $already_added = $doctrine->getRepository(Series::class)->findOneBy(['imdb' => $response['imdbID']]);
            }
        }

        return $this->render('admin/index.html.twig', [
            'form' => $form->createView(),  
            'already_added' => $already_added,
            'response' => $response,
        ]);
    }
}
