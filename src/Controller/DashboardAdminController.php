<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\Evenement;
use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

final class DashboardAdminController extends AbstractController
{
    #[Route('/dashboard/admin', name: 'app_dashboard_admin')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur est connecté
        $session = $this->container->get('request_stack')->getSession();
        $user = $session->get('user');
        
        if (!$user) {
            $this->addFlash('error', 'Veuillez vous connecter pour accéder à cette page');
            return $this->redirectToRoute('app_sign_in');
        }

        // Vérifier si l'utilisateur est admin
        if ($user['role'] !== 'admin') {
            $this->addFlash('error', 'Accès refusé - Vous devez être administrateur pour accéder à cette page');
            return $this->redirectToRoute('app_home');
        }

        // Calculer les statistiques utilisateurs
        $userRepository = $entityManager->getRepository(Utilisateur::class);
        
        // Compter les utilisateurs par rôle (excluant l'admin)
        $joueurs = $userRepository->count(['role' => 'joueur']);
        $vendeurs = $userRepository->count(['role' => 'vendeur']);
        $organisateurs = $userRepository->count(['role' => 'organisateur']);
        
        // Calculer le total sans compter les admins
        $totalUtilisateurs = $joueurs + $vendeurs + $organisateurs;

        // Récupérer la liste de tous les utilisateurs sauf les admins
        $utilisateurs = $userRepository->createQueryBuilder('u')
            ->where('u.role != :role')
            ->setParameter('role', 'admin')
            ->getQuery()
            ->getResult();
            
        // Statistiques des événements
        $eventRepository = $entityManager->getRepository(Evenement::class);
        $allEvents = $eventRepository->findAll();
        $totalEvents = count($allEvents);
        
        // Compter les événements à venir et passés
        $now = new \DateTime();
        $upcomingEvents = 0;
        $pastEvents = 0;
        
        foreach($allEvents as $event) {
            if($event->getDate() > $now) {
                $upcomingEvents++;
            } else {
                $pastEvents++;
            }
        }
        
        // Statistiques des réservations
        $reservationRepository = $entityManager->getRepository(Reservation::class);
        $allReservations = $reservationRepository->findAll();
        $totalReservations = count($allReservations);
        
        // Compter les réservations récentes (dernière semaine)
        $lastWeek = new \DateTime('-7 days');
        $recentReservations = 0;
        $totalParticipants = 0;
        
        foreach($allReservations as $reservation) {
            $totalParticipants += $reservation->getNombrepersonnes();
            if($reservation->getDateReservation() >= $lastWeek) {
                $recentReservations++;
            }
        }
        
        // Obtenir les 5 dernières réservations
        $latestReservations = $reservationRepository->findBy(
            [], 
            ['dateReservation' => 'DESC'],
            5
        );
        
        // Obtenir les 5 prochains événements
        $upcomingEventsList = $eventRepository->createQueryBuilder('e')
            ->where('e.date > :now')
            ->setParameter('now', $now)
            ->orderBy('e.date', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $this->render('dashboard_admin/index.html.twig', [
            'stats' => [
                'joueurs' => $joueurs,
                'vendeurs' => $vendeurs,
                'organisateurs' => $organisateurs,
                'total' => $totalUtilisateurs,
                'events' => [
                    'total' => $totalEvents,
                    'upcoming' => $upcomingEvents,
                    'past' => $pastEvents
                ],
                'reservations' => [
                    'total' => $totalReservations,
                    'recent' => $recentReservations,
                    'participants' => $totalParticipants
                ]
            ],
            'utilisateurs' => $utilisateurs,
            'latestReservations' => $latestReservations,
            'upcomingEvents' => $upcomingEventsList
        ]);
    }

    #[Route('/dashboard/admin/toggle-block/{id}', name: 'app_toggle_block_user', methods: ['POST'])]
    public function toggleBlockUser(Request $request, int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            // Vérifier si l'utilisateur est connecté et est admin
            $session = $request->getSession();
            $user = $session->get('user');
            
            if (!$user || $user['role'] !== 'admin') {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Accès refusé - Vous devez être administrateur'
                ], 403);
            }

            $utilisateur = $entityManager->getRepository(Utilisateur::class)->find($id);
            
            if (!$utilisateur) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            }

            // Ne pas permettre de bloquer un admin
            if ($utilisateur->getRole() === 'admin') {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Impossible de bloquer un administrateur'
                ], 403);
            }

            // Inverser le statut de blocage
            $newStatus = !$utilisateur->isBlocked();
            $utilisateur->setIsBlocked($newStatus);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'isBlocked' => $newStatus,
                'message' => $newStatus ? 
                    'L\'utilisateur a été bloqué avec succès' : 
                    'L\'utilisateur a été débloqué avec succès'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Une erreur est survenue: ' . $e->getMessage()
            ], 500);
        }
    }
}
