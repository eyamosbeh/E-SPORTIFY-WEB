<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HoomeController extends AbstractController
{
    #[Route('/hoome', name: 'app_hoome')]
    public function index(): Response
    {
        return $this->render('temp/index.html.twig', [
            'controller_name' => 'HoomeController',
        ]);
    }
}
