<?php

namespace App\Controller;

use App\Repository\SalleRepository;
use App\Repository\ReservationSalleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(SalleRepository $salleRepository, ReservationSalleRepository $reservationRepository): Response
    {
        $totalSalles = count($salleRepository->findAll());
        $totalReservations = count($reservationRepository->findAll());
        $reservationsEnAttente = count($reservationRepository->findBy(['statut' => 'En attente']));
        $reservationsConfirmees = count($reservationRepository->findBy(['statut' => 'Confirmée']));

        $dernieresReservations = $reservationRepository->findBy(
            [], 
            ['id' => 'DESC'],
            5
        );

        return $this->render('home/index.html.twig', [
            'total_salles' => $totalSalles,
            'total_reservations' => $totalReservations,
            'reservations_en_attente' => $reservationsEnAttente,
            'reservations_confirmees' => $reservationsConfirmees,
            'dernieres_reservations' => $dernieresReservations,
        ]);
    }
}
