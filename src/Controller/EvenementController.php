<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Form\EvenementType;
use App\Repository\EvenementRepository;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

    #[Route('/evenement')]
    final class EvenementController extends AbstractController{
        
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
        
        #[Route('/liste', name: 'app_evenement_liste', methods: ['GET'])]
        public function liste(EvenementRepository $evenementRepository): Response
        {
            return $this->render('evenement/Listeevenement.html.twig', [
                'evenements' => $evenementRepository->findAll(),
            ]);
        }

        #[Route(name: 'app_evenement_index', methods: ['GET'])]
        public function index(EvenementRepository $evenementRepository): Response
        {
            // Vérifier l'accès admin
            $adminCheck = $this->checkAdminAccess();
            if ($adminCheck !== null) {
                return $adminCheck;
            }
            
            // Récupérer tous les événements
            $evenements = $evenementRepository->findAll();
            
            // Calculer quelques statistiques
            $totalEvents = count($evenements);
            $upcomingEvents = 0;
            $pastEvents = 0;
            $totalParticipants = 0;
            
            $now = new \DateTime();
            foreach ($evenements as $event) {
                if ($event->getDate() > $now) {
                    $upcomingEvents++;
                } else {
                    $pastEvents++;
                }
                // Le nombre de participants est calculé en soustrayant la capacité initiale par la capacité restante
                // Ceci suppose que capacite est le nombre de places restantes
                $totalParticipants += $event->getCapaciteInitiale() - $event->getCapacite();
            }
            
            return $this->render('evenement/index.html.twig', [
                'evenements' => $evenements,
                'stats' => [
                    'total' => $totalEvents,
                    'upcoming' => $upcomingEvents,
                    'past' => $pastEvents,
                    'participants' => $totalParticipants
                ],
                'isAdmin' => true
            ]);
        }
        
#[Route('/inscription/{id}', name: 'app_ev_ei_ei', methods: ['POST'])]
public function inscription(Request $request, Evenement $evenement, EntityManagerInterface $entityManager): Response
{
    // Vérification du token CSRF pour la sécurité
        // Incrémentation de la capacité
        $evenement->setCapacite($evenement->getCapacite() + 1);
        
        // Sauvegarde en base de données
        $entityManager->persist($evenement);
        $entityManager->flush();

        // Message de succès
        $this->addFlash('success', 'Inscription réussie !');
    

    // Redirection vers la page des événements
    return $this->redirectToRoute('app_ev_ei');
}

    #[Route('/eventlist',name: 'app_ev_ei', methods: ['GET'])]
    public function index1(EvenementRepository $evenementRepository): Response
    {
        
        return $this->render('evenement/Listeevenement.html.twig', [
            'evenements' => $evenementRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_evenement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérifier l'accès admin
        $adminCheck = $this->checkAdminAccess();
        if ($adminCheck !== null) {
            return $adminCheck;
        }
        
        $evenement = new Evenement();
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Enregistrer la capacité initiale
            $capaciteInitiale = $evenement->getCapacite();
            $evenement->setCapaciteInitiale($capaciteInitiale);
            
            $entityManager->persist($evenement);
            $entityManager->flush();

            $this->addFlash('success', 'Événement créé avec succès !');
            return $this->redirectToRoute('app_evenement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('evenement/new.html.twig', [
            'evenement' => $evenement,
            'form' => $form,
            'isAdmin' => true
        ]);
    }



    #[Route('/{id}', name: 'app_evenement_show', methods: ['GET'])]
    public function show(Evenement $evenement): Response
    {
        // Pour cette méthode, pas besoin de vérifier l'accès admin car elle peut être accessible par tous
        return $this->render('evenement/show.html.twig', [
            'evenement' => $evenement,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_evenement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Evenement $evenement, EntityManagerInterface $entityManager): Response
    {
        // Vérifier l'accès admin
        $adminCheck = $this->checkAdminAccess();
        if ($adminCheck !== null) {
            return $adminCheck;
        }
        
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Événement mis à jour avec succès !');
            return $this->redirectToRoute('app_evenement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('evenement/edit.html.twig', [
            'evenement' => $evenement,
            'form' => $form,
            'isAdmin' => true
        ]);
    }

    #[Route('/{id}', name: 'app_evenement_delete', methods: ['POST'])]
    public function delete(Request $request, Evenement $evenement, EntityManagerInterface $entityManager): Response
    {
        // Vérifier l'accès admin
        $adminCheck = $this->checkAdminAccess();
        if ($adminCheck !== null) {
            return $adminCheck;
        }
        
        if ($this->isCsrfTokenValid('delete'.$evenement->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($evenement);
            $entityManager->flush();
            $this->addFlash('success', 'Événement supprimé avec succès !');
        }

        return $this->redirectToRoute('app_evenement_index', [], Response::HTTP_SEE_OTHER);
    }
    
    #[Route('/user/{id}', name: 'app_evenement_by_user', methods: ['GET'])]
    public function evenementsByUser(Utilisateur $utilisateur, EvenementRepository $evenementRepository): Response
    {
        $evenements = $evenementRepository->findByUtilisateur($utilisateur);
        
        return $this->render('evenement/index.html.twig', [
            'evenements' => $evenements,
            'utilisateur' => $utilisateur
        ]);
    }

    #[Route('/evenement/events', name: 'app_events_cards', methods: ['GET'])]
    public function eventsCards(EntityManagerInterface $entityManager): Response
    {
        $evenements = $entityManager->getRepository(Evenement::class)->findAll();
        
        return $this->render('evenement/events_cards.html.twig', [
            'evenements' => $evenements,
        ]);
    }
}
