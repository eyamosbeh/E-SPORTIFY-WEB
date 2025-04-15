<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Entity\Reponse;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/reclamation/{id}/repondre', name: 'app_admin_reponse', methods: ['GET'])]
    public function reponseForm(Reclamation $reclamation): Response
    {
        // Vérifier que la réclamation existe
        if (!$reclamation) {
            $this->addFlash('error', 'La réclamation demandée n\'existe pas.');
            return $this->redirectToRoute('app_reclamation_index');
        }
        
        return $this->render('admin/reponse_reclamation.html.twig', [
            'reclamation' => $reclamation,
        ]);
    }
    
    #[Route('/reclamation/{id}/repondre/submit', name: 'app_admin_reponse_submit', methods: ['POST'])]
    public function submitReponse(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que la réclamation existe
        if (!$reclamation) {
            $this->addFlash('error', 'La réclamation demandée n\'existe pas.');
            return $this->redirectToRoute('app_reclamation_index');
        }
        
        // Récupérer le contenu de la réponse
        $contenu = $request->request->get('reponse');
        $notifyClient = $request->request->getBoolean('notify_client');
        
        // Vérifier que le contenu n'est pas vide
        if (!$contenu || empty(trim($contenu))) {
            $this->addFlash('error', 'Le contenu de la réponse ne peut pas être vide.');
            return $this->redirectToRoute('app_admin_reponse', ['id' => $reclamation->getId()]);
        }
        
        try {
            // Créer une nouvelle réponse
            $reponse = new Reponse();
            $reponse->setContenu($contenu);
            $reponse->setReclamation($reclamation);
            
            // Mettre à jour le statut de la réclamation
            $reclamation->setStatut('Résolue');
            
            // Persister les changements
            $entityManager->persist($reponse);
            $entityManager->flush();
            
            // Si l'option de notification est activée, on envoie un email (implémentation fictive pour l'exemple)
            if ($notifyClient) {
                // Dans une implémentation réelle, on enverrait un email au client ici
                // $this->emailService->sendResponseNotification($reclamation, $reponse);
                
                $this->addFlash('success', 'Réponse ajoutée avec succès. Le client a été notifié par email.');
            } else {
                $this->addFlash('success', 'Réponse ajoutée avec succès.');
            }
            
            // Rediriger vers le tableau de bord administrateur au lieu de la page de détail
            return $this->redirectToRoute('app_admin_dashboard');
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de l\'enregistrement de la réponse: ' . $e->getMessage());
            return $this->redirectToRoute('app_admin_reponse', ['id' => $reclamation->getId()]);
        }
    }
    
    #[Route('/dashboard', name: 'app_admin_dashboard', methods: ['GET'])]
    public function dashboard(ReclamationRepository $reclamationRepository): Response
    {
        // Statistiques pour le tableau de bord (incluant toutes les réclamations)
        $stats = [
            'total' => $reclamationRepository->count([]),
            'enAttente' => $reclamationRepository->count(['statut' => 'En attente']),
            'resolues' => $reclamationRepository->count(['statut' => 'Résolue'])
        ];
        
        // Récupérer les 5 dernières réclamations (toutes)
        $latestReclamations = $reclamationRepository->findBy([], ['date_creation' => 'DESC'], 5);
        
        // Récupérer les réclamations en attente (toutes)
        $pendingReclamations = $reclamationRepository->findBy(['statut' => 'En attente'], ['date_creation' => 'ASC']);
        
        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'latestReclamations' => $latestReclamations,
            'pendingReclamations' => $pendingReclamations
        ]);
    }

    #[Route('/reclamations/resolues', name: 'app_admin_reclamations_resolues', methods: ['GET'])]
    public function reclamationsResolues(ReclamationRepository $reclamationRepository): Response
    {
        // Récupérer toutes les réclamations résolues (incluant les archivées)
        $reclamationsResolues = $reclamationRepository->findBy(
            ['statut' => 'Résolue'],
            ['date_creation' => 'DESC']
        );
        
        return $this->render('admin/reclamations_resolues.html.twig', [
            'reclamations' => $reclamationsResolues
        ]);
    }
} 