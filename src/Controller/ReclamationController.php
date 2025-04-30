<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Entity\Reponse;
use App\Form\ReclamationType;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/reclamation')]
class ReclamationController extends AbstractController
{
    #[Route('/', name: 'app_reclamation_index', methods: ['GET'])]
    public function index(Request $request, ReclamationRepository $reclamationRepository): Response
    {
        // Récupérer le paramètre archived de la requête
        $archived = $request->query->get('archived', false);
        
        // Convertir en booléen pour la recherche
        $isArchived = filter_var($archived, FILTER_VALIDATE_BOOLEAN);
        
        // Statistiques totales (pour toutes les réclamations)
        $stats = [
            'total' => $reclamationRepository->count([]),
            'enAttente' => $reclamationRepository->count(['statut' => 'En attente']),
            'resolues' => $reclamationRepository->count(['statut' => 'Résolue']),
            'archivees' => $reclamationRepository->count(['archived' => true])
        ];

        return $this->render('reclamation/index.html.twig', [
            'reclamations' => $reclamationRepository->findBy(['archived' => $isArchived], ['date_creation' => 'DESC']),
            'stats' => $stats,
            'is_archived_view' => $isArchived
        ]);
    }

    #[Route('/new', name: 'app_reclamation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $reclamation = new Reclamation();
        $reclamation->setStatut('En attente');
        // La date est déjà initialisée dans le constructeur
        
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Vérification explicite des champs obligatoires
            $description = $form->get('description')->getData();
            $categorie = $form->get('categorie')->getData();
            
            $errors = [];
            
            if (!$description || strlen(trim($description)) < 10) {
                $errors[] = 'La description doit contenir au moins 10 caractères.';
            }
            
            if (!$categorie || empty(trim($categorie))) {
                $errors[] = 'La catégorie ne peut pas être vide.';
            }
            
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('danger', $error);
                }
            } elseif ($form->isValid()) {
                // Gestion de l'upload d'image
                $imageFile = $form->get('imageFile')->getData();
                if ($imageFile) {
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                    try {
                        $imageFile->move(
                            $this->getParameter('images_directory'),
                            $newFilename
                        );
                        $reclamation->setImagePath($newFilename);
                    } catch (FileException $e) {
                        // Gérer l'erreur
                        $this->addFlash('danger', 'Une erreur est survenue lors de l\'upload de l\'image');
                    }
                }

                // Gestion de l'upload de PDF
                $pdfFile = $form->get('pdfFile')->getData();
                if ($pdfFile) {
                    $originalFilename = pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$pdfFile->guessExtension();

                    try {
                        $pdfFile->move(
                            $this->getParameter('pdf_directory'),
                            $newFilename
                        );
                        $reclamation->setPdfPath($newFilename);
                    } catch (FileException $e) {
                        // Gérer l'erreur
                        $this->addFlash('danger', 'Une erreur est survenue lors de l\'upload du PDF');
                    }
                }

                $entityManager->persist($reclamation);
                $entityManager->flush();
                
                $this->addFlash('success', 'Réclamation créée avec succès');

                return $this->redirectToRoute('app_reclamation_index', [], Response::HTTP_SEE_OTHER);
            } else {
                // Formulaire soumis mais invalide pour d'autres raisons
                $this->addFlash('danger', 'Le formulaire contient des erreurs. Veuillez vérifier les champs.');
            }
        }

        return $this->render('reclamation/new.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_reclamation_show', methods: ['GET'])]
    public function show(Reclamation $reclamation): Response
    {
        return $this->render('reclamation/show.html.twig', [
            'reclamation' => $reclamation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reclamation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ReclamationType::class, $reclamation, [
            'is_edit' => true
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Vérification explicite des champs obligatoires
            $description = $form->get('description')->getData();
            $categorie = $form->get('categorie')->getData();
            
            $errors = [];
            
            if (!$description || strlen(trim($description)) < 10) {
                $errors[] = 'La description doit contenir au moins 10 caractères.';
            }
            
            if (!$categorie || empty(trim($categorie))) {
                $errors[] = 'La catégorie ne peut pas être vide.';
            }
            
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('danger', $error);
                }
            } elseif ($form->isValid()) {
                // Gestion de l'upload d'image
                $imageFile = $form->get('imageFile')->getData();
                if ($imageFile) {
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                    try {
                        $imageFile->move(
                            $this->getParameter('images_directory'),
                            $newFilename
                        );
                        // Supprimer l'ancienne image si elle existe
                        if ($reclamation->getImagePath()) {
                            $oldImagePath = $this->getParameter('images_directory') . '/' . $reclamation->getImagePath();
                            if (file_exists($oldImagePath)) {
                                unlink($oldImagePath);
                            }
                        }
                        $reclamation->setImagePath($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('danger', 'Une erreur est survenue lors de l\'upload de l\'image');
                    }
                }

                // Gestion de l'upload de PDF
                $pdfFile = $form->get('pdfFile')->getData();
                if ($pdfFile) {
                    $originalFilename = pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$pdfFile->guessExtension();

                    try {
                        $pdfFile->move(
                            $this->getParameter('pdf_directory'),
                            $newFilename
                        );
                        // Supprimer l'ancien PDF si il existe
                        if ($reclamation->getPdfPath()) {
                            $oldPdfPath = $this->getParameter('pdf_directory') . '/' . $reclamation->getPdfPath();
                            if (file_exists($oldPdfPath)) {
                                unlink($oldPdfPath);
                            }
                        }
                        $reclamation->setPdfPath($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('danger', 'Une erreur est survenue lors de l\'upload du PDF');
                    }
                }
                
                $entityManager->flush();
                
                $this->addFlash('success', 'Réclamation modifiée avec succès');

                return $this->redirectToRoute('app_reclamation_show', ['id' => $reclamation->getId()], Response::HTTP_SEE_OTHER);
            } else {
                // Formulaire soumis mais invalide pour d'autres raisons
                $this->addFlash('danger', 'Le formulaire contient des erreurs. Veuillez vérifier les champs.');
            }
        }

        return $this->render('reclamation/edit.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/archive', name: 'app_reclamation_archive', methods: ['POST'])]
    public function archive(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('archive'.$reclamation->getId(), $request->request->get('_token'))) {
            $reclamation->setArchived(true);
            $entityManager->flush();
            
            $this->addFlash('success', 'Réclamation archivée avec succès');
        }

        return $this->redirectToRoute('app_reclamation_index', [], Response::HTTP_SEE_OTHER);
    }
    
    #[Route('/{id}/unarchive', name: 'app_reclamation_unarchive', methods: ['POST'])]
    public function unarchive(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('unarchive'.$reclamation->getId(), $request->request->get('_token'))) {
            $reclamation->setArchived(false);
            $entityManager->flush();
            
            $this->addFlash('success', 'Réclamation restaurée avec succès');
        }

        return $this->redirectToRoute('app_reclamation_index', ['archived' => 'true'], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/repondre', name: 'app_reclamation_repondre', methods: ['POST'])]
    public function repondre(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        $contenu = $request->request->get('reponse');
        if ($contenu) {
            $reponse = new Reponse();
            $reponse->setContenu($contenu);
            $reponse->setReclamation($reclamation);
            
            $reclamation->setStatut('Résolue');
            $reclamation->setReponse($contenu);

            $entityManager->persist($reponse);
            $entityManager->flush();
            
            $this->addFlash('success', 'Réponse ajoutée avec succès');
        }

        return $this->redirectToRoute('app_reclamation_show', ['id' => $reclamation->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/pdf', name: 'app_reclamation_pdf', methods: ['GET'])]
    public function generatePdf(Reclamation $reclamation): Response
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);

        $dompdf = new Dompdf($options);
        $html = $this->renderView('reclamation/pdf.html.twig', [
            'reclamation' => $reclamation
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $response = new Response($dompdf->output());
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment;filename="reclamation-'.$reclamation->getId().'.pdf"');

        return $response;
    }

    #[Route('/{id}/delete', name: 'app_reclamation_delete', methods: ['POST'])]
    public function delete(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reclamation->getId(), $request->request->get('_token'))) {
            try {
                // Stocker les données pour le message de confirmation
                $id = $reclamation->getId();
                $nbReponses = count($reclamation->getReponses());
                $hasImage = !empty($reclamation->getImagePath());
                $hasPdf = !empty($reclamation->getPdfPath());
                
                // Suppression des fichiers associés si nécessaire
                if ($hasImage) {
                    $imagePath = $this->getParameter('images_directory') . '/' . $reclamation->getImagePath();
                    if (file_exists($imagePath)) {
                        if (unlink($imagePath)) {
                            // Succès de la suppression de l'image
                        } else {
                            // Échec de la suppression, mais on continue le processus
                            $this->addFlash('warning', 'L\'image associée n\'a pas pu être supprimée du serveur.');
                        }
                    }
                }
                
                if ($hasPdf) {
                    $pdfPath = $this->getParameter('pdf_directory') . '/' . $reclamation->getPdfPath();
                    if (file_exists($pdfPath)) {
                        if (unlink($pdfPath)) {
                            // Succès de la suppression du PDF
                        } else {
                            // Échec de la suppression, mais on continue le processus
                            $this->addFlash('warning', 'Le PDF associé n\'a pas pu être supprimé du serveur.');
                        }
                    }
                }
                
                // Supprimer les réponses associées
                $reponses = $reclamation->getReponses()->toArray(); // Copie pour éviter les problèmes d'itération
                foreach ($reponses as $reponse) {
                    $entityManager->remove($reponse);
                }
                
                // Supprimer la réclamation
                $entityManager->remove($reclamation);
                $entityManager->flush();
                
                // Message de confirmation détaillé
                $message = "Réclamation #$id supprimée avec succès.";
                if ($nbReponses > 0) {
                    $message .= " $nbReponses réponse(s) également supprimée(s).";
                }
                
                $this->addFlash('success', $message);
                
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_reclamation_index', [], Response::HTTP_SEE_OTHER);
    }
} 