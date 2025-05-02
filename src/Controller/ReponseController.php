<?php

namespace App\Controller;

use App\Entity\Reponse;
use App\Entity\Reclamation;
use App\Form\ReponseType;
use App\Repository\ReponseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reponse')]
class ReponseController extends AbstractController
{
    #[Route('/', name: 'app_reponse_index', methods: ['GET'])]
    public function index(ReponseRepository $reponseRepository): Response
    {
        return $this->render('reponse/index.html.twig', [
            'reponses' => $reponseRepository->findBy([], ['date_creation' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'app_reponse_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $reponse = new Reponse();
        $form = $this->createForm(ReponseType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Mise à jour du statut de la réclamation
            $reclamation = $reponse->getReclamation();
            if ($reclamation) {
                $reclamation->setStatut('Résolue');
            }
            
            $entityManager->persist($reponse);
            $entityManager->flush();

            $this->addFlash('success', 'Réponse créée avec succès.');
            
            return $this->redirectToRoute('app_reclamation_show', [
                'id' => $reclamation->getId()
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reponse/new.html.twig', [
            'reponse' => $reponse,
            'form' => $form,
        ]);
    }

    #[Route('/new/{id}', name: 'app_reponse_new_for_reclamation', methods: ['GET', 'POST'])]
    public function newForReclamation(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        $reponse = new Reponse();
        $reponse->setReclamation($reclamation);
        
        $form = $this->createForm(ReponseType::class, $reponse, [
            'disabled_fields' => ['reclamation']
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Mise à jour du statut de la réclamation
            $reclamation->setStatut('Résolue');
            
            $entityManager->persist($reponse);
            $entityManager->flush();

            $this->addFlash('success', 'Réponse ajoutée avec succès.');
            
            return $this->redirectToRoute('app_reclamation_show', [
                'id' => $reclamation->getId()
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reponse/new.html.twig', [
            'reponse' => $reponse,
            'reclamation' => $reclamation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_reponse_show', methods: ['GET'])]
    public function show(Reponse $reponse): Response
    {
        return $this->render('reponse/show.html.twig', [
            'reponse' => $reponse,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reponse_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reponse $reponse, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReponseType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Réponse modifiée avec succès.');
            
            return $this->redirectToRoute('app_reponse_show', [
                'id' => $reponse->getId()
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reponse/edit.html.twig', [
            'reponse' => $reponse,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_reponse_delete', methods: ['POST'])]
    public function delete(Request $request, Reponse $reponse, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reponse->getId(), $request->request->get('_token'))) {
            $reclamation = $reponse->getReclamation();
            
            // Si c'est la seule réponse pour cette réclamation, on change le statut en "En attente"
            $reponses = $reclamation->getReponses();
            if (count($reponses) <= 1) {
                $reclamation->setStatut('En attente');
            }
            
            $entityManager->remove($reponse);
            $entityManager->flush();
            
            $this->addFlash('success', 'Réponse supprimée avec succès.');
        }
        
        return $this->redirectToRoute('app_reclamation_show', [
            'id' => $reponse->getReclamation()->getId()
        ], Response::HTTP_SEE_OTHER);
    }
} 