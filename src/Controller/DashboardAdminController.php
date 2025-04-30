<?php

namespace App\Controller;

use App\Entity\Utilisateur;
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

        // Calculer les statistiques
        $repository = $entityManager->getRepository(Utilisateur::class);
        
        // Compter les utilisateurs par rôle (excluant l'admin)
        $joueurs = $repository->count(['role' => 'joueur']);
        $vendeurs = $repository->count(['role' => 'vendeur']);
        $organisateurs = $repository->count(['role' => 'organisateur']);
        
        // Calculer le total sans compter les admins
        $totalUtilisateurs = $joueurs + $vendeurs + $organisateurs;

        // Récupérer la liste de tous les utilisateurs sauf les admins
        $utilisateurs = $repository->createQueryBuilder('u')
            ->where('u.role != :role')
            ->setParameter('role', 'admin')
            ->getQuery()
            ->getResult();

        return $this->render('dashboard_admin/index.html.twig', [
            'stats' => [
                'joueurs' => $joueurs,
                'vendeurs' => $vendeurs,
                'organisateurs' => $organisateurs,
                'total' => $totalUtilisateurs
            ],
            'utilisateurs' => $utilisateurs
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
