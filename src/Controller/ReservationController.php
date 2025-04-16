<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Entity\Evenement;
use App\Entity\Utilisateur;
use App\Repository\ReservationRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;

#[Route('/reservation')]
final class ReservationController extends AbstractController{
    
    private function checkAdminAccess()
    {
        $session = $this->container->get('request_stack')->getSession();
        $user = $session->get('user');
        
        if (!$user) {
            $this->addFlash('error', 'Veuillez vous connecter pour accéder à cette page');
            return $this->redirectToRoute('app_sign_in');
        }

        if ($user['role'] !== 'admin' && $user['role'] !== 'organisateur') {
            $this->addFlash('error', 'Accès refusé - Vous devez être administrateur ou organisateur');
            return $this->redirectToRoute('app_home');
        }
        
        return null;
    }
    
    #[Route('/', name: 'app_reservation_index', methods: ['GET'])]
    public function index(ReservationRepository $reservationRepository, Request $request): Response
    {
        // Vérifier l'accès admin
        $adminCheck = $this->checkAdminAccess();
        if ($adminCheck !== null) {
            return $adminCheck;
        }
        
        $archived = $request->query->getBoolean('archived', false);
        $reservations = $reservationRepository->findBy(['archived' => $archived], ['dateReservation' => 'DESC']);

        // Calculer des statistiques pour le tableau de bord
        $totalReservations = count($reservations);
        $totalPersonnes = 0;
        $recentReservations = 0;
        
        $lastWeek = new \DateTime('-7 days');
        foreach ($reservations as $reservation) {
            $totalPersonnes += $reservation->getNombrepersonnes();
            
            if ($reservation->getDateReservation() >= $lastWeek) {
                $recentReservations++;
            }
        }

        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations,
            'archived' => $archived,
            'stats' => [
                'total' => $totalReservations,
                'personnes' => $totalPersonnes,
                'recent' => $recentReservations
            ],
            'isAdmin' => true
        ]);
    }

    #[Route('/new', name: 'app_reservation_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            // Récupérer et décoder les données JSON
            $data = json_decode($request->getContent(), true);
            if (!$data) {
                throw new \Exception('Données JSON invalides');
            }

            // Vérifier le token CSRF
            if (!$this->isCsrfTokenValid('reservation', $data['_token'])) {
                throw new \Exception('Token CSRF invalide');
            }

            // Récupérer et valider les données
            $eventId = $data['eventId'] ?? null;
            $userId = $data['userId'] ?? null;
            $nombrePersonnes = (int)($data['nombrePersonnes'] ?? 0);

            if (!$eventId || $nombrePersonnes < 1) {
                throw new \Exception('Données invalides');
            }

            // Récupérer l'événement
            $event = $entityManager->getRepository(Evenement::class)->find($eventId);
            if (!$event) {
                throw new \Exception('Événement non trouvé');
            }

            // Récupérer l'utilisateur
            $user = null;
            if ($userId) {
                $user = $entityManager->getRepository(Utilisateur::class)->find($userId);
                if (!$user) {
                    throw new \Exception('Utilisateur non trouvé');
                }
            }

            // Vérifier la capacité
            if ($event->getCapacite() < $nombrePersonnes) {
                throw new \Exception('Capacité insuffisante');
            }

            // Créer la réservation
            $reservation = new Reservation();
            $reservation->setEvenement($event);
            $reservation->setNombrepersonnes($nombrePersonnes);
            $reservation->setDateReservation(new \DateTime());
            $reservation->setArchived(false);
            
            if ($user) {
                $reservation->setUtilisateur($user);
            }

            // Mettre à jour la capacité de l'événement
            $nouvelleCapacite = $event->getCapacite() - $nombrePersonnes;
            $event->setCapacite($nouvelleCapacite);

            // Sauvegarder en base de données
            $entityManager->persist($reservation);
            $entityManager->persist($event);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Réservation créée avec succès'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/{id_reservation}', name: 'app_reservation_show', methods: ['GET'])]
    public function show(Reservation $reservation): Response
    {
        // Vérifier l'accès admin
        $adminCheck = $this->checkAdminAccess();
        if ($adminCheck !== null) {
            return $adminCheck;
        }
        
        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
            'isAdmin' => true
        ]);
    }

    #[Route('/{id_reservation}/edit', name: 'app_reservation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        // Vérifier l'accès admin
        $adminCheck = $this->checkAdminAccess();
        if ($adminCheck !== null) {
            return $adminCheck;
        }
        
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Réservation mise à jour avec succès');
            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
            'isAdmin' => true
        ]);
    }

    #[Route('/{id_reservation}/archive', name: 'app_reservation_archive', methods: ['POST'])]
    public function archive(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        // Vérifier l'accès admin
        $adminCheck = $this->checkAdminAccess();
        if ($adminCheck !== null) {
            return $adminCheck;
        }
        
        if ($this->isCsrfTokenValid('archive'.$reservation->getId_reservation(), $request->getPayload()->getString('_token'))) {
            $reservation->setArchived(true);
            $entityManager->flush();
            $this->addFlash('success', 'Réservation archivée avec succès');
        }

        return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id_reservation}/unarchive', name: 'app_reservation_unarchive', methods: ['POST'])]
    public function unarchive(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        // Vérifier l'accès admin
        $adminCheck = $this->checkAdminAccess();
        if ($adminCheck !== null) {
            return $adminCheck;
        }
        
        if ($this->isCsrfTokenValid('unarchive'.$reservation->getId_reservation(), $request->getPayload()->getString('_token'))) {
            $reservation->setArchived(false);
            $entityManager->flush();
            $this->addFlash('success', 'Réservation désarchivée avec succès');
        }

        return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id_reservation}', name: 'app_reservation_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        // Vérifier l'accès admin
        $adminCheck = $this->checkAdminAccess();
        if ($adminCheck !== null) {
            return $adminCheck;
        }
        
        if ($this->isCsrfTokenValid('delete'.$reservation->getId_reservation(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($reservation);
            $entityManager->flush();
            $this->addFlash('success', 'Réservation supprimée avec succès');
        }

        return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
    }
    
    #[Route('/user/{id}', name: 'app_reservation_by_user', methods: ['GET'])]
    public function reservationsByUser(Utilisateur $utilisateur, ReservationRepository $reservationRepository): Response
    {
        // Vérifier l'accès admin
        $adminCheck = $this->checkAdminAccess();
        if ($adminCheck !== null) {
            return $adminCheck;
        }
        
        $reservations = $reservationRepository->findByUtilisateur($utilisateur);
        
        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations,
            'utilisateur' => $utilisateur,
            'archived' => false,
            'isAdmin' => true
        ]);
    }
}
