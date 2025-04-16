<?php

namespace App\Controller;

use App\Repository\EvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(EvenementRepository $evenementRepository): Response
    {
        // Get upcoming events ordered by date
        $evenements = $evenementRepository->createQueryBuilder('e')
            ->where('e.date >= :today')
            ->setParameter('today', new \DateTime())
            ->orderBy('e.date', 'ASC')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();

        return $this->render('home/index.html.twig', [
            'evenements' => $evenements,
        ]);
    }
} 