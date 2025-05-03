<?php

namespace App\Controller;

use App\Entity\Salle;
use App\Form\SalleType;
use App\Repository\SalleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Knp\Component\Pager\PaginatorInterface;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/salle')]
class SalleController extends AbstractController
{
    #[Route('/', name: 'app_salle_index', methods: ['GET'])]
    public function index(SalleRepository $salleRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $search = $request->query->get('search');
        $queryBuilder = $salleRepository->createQueryBuilder('s');

        if ($search) {
            $queryBuilder
                ->where('s.nom LIKE :search')
                ->orWhere('s.description LIKE :search')
                ->orWhere('s.capacite LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $queryBuilder->orderBy('s.id', 'DESC');
        $query = $queryBuilder->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            4
        );

        return $this->render('salle/index.html.twig', [
            'pagination' => $pagination,
            'show_pagination' => true
        ]);
    }

    #[Route('/new', name: 'app_salle_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $salle = new Salle();
        $form = $this->createForm(SalleType::class, $salle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($salle);
            $entityManager->flush();

            $this->addFlash('success', 'La salle a été créée avec succès.');
            return $this->redirectToRoute('app_salle_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('salle/new.html.twig', [
            'salle' => $salle,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_salle_show', methods: ['GET'])]
    public function show(EntityManagerInterface $entityManager, int $id): Response
    {
        $salle = $entityManager->getRepository(Salle::class)->find($id);
        
        if (!$salle) {
            throw $this->createNotFoundException('La salle demandée n\'existe pas.');
        }

        $deleteForm = $this->createFormBuilder()
            ->setAction($this->generateUrl('app_salle_delete', ['id' => $salle->getId()]))
            ->setMethod('POST')
            ->getForm();

        return $this->render('salle/show.html.twig', [
            'salle' => $salle,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_salle_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $entityManager, int $id): Response
    {
        $salle = $entityManager->getRepository(Salle::class)->find($id);
        
        if (!$salle) {
            throw $this->createNotFoundException('La salle demandée n\'existe pas.');
        }

        $form = $this->createForm(SalleType::class, $salle);
        $form->handleRequest($request);

        $deleteForm = $this->createFormBuilder()
            ->setAction($this->generateUrl('app_salle_delete', ['id' => $salle->getId()]))
            ->setMethod('POST')
            ->getForm();

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La salle a été modifiée avec succès.');
            return $this->redirectToRoute('app_salle_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('salle/edit.html.twig', [
            'salle' => $salle,
            'form' => $form->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_salle_delete', methods: ['POST'])]
    public function delete(Request $request, EntityManagerInterface $entityManager, int $id): Response
    {
        $salle = $entityManager->getRepository(Salle::class)->find($id);
        
        if (!$salle) {
            throw $this->createNotFoundException('La salle demandée n\'existe pas.');
        }

        if ($this->isCsrfTokenValid('delete'.$salle->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($salle);
                $entityManager->flush();
                $this->addFlash('success', 'La salle a été supprimée avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Impossible de supprimer la salle car elle est liée à des réservations.');
                return $this->redirectToRoute('app_salle_show', ['id' => $id]);
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_salle_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/export/pdf', name: 'app_salle_export_pdf', methods: ['GET'])]
    public function exportPdf(SalleRepository $salleRepository): Response
    {
        // Get all salles
        $salles = $salleRepository->findAll();
        
        // Configure Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        // Instantiate Dompdf
        $dompdf = new Dompdf($options);
        
        // Generate HTML content
        $html = $this->renderView('salle/pdf.html.twig', [
            'salles' => $salles,
            'date' => new \DateTime(),
        ]);
        
        // Load HTML to Dompdf
        $dompdf->loadHtml($html);
        
        // Set paper size and orientation
        $dompdf->setPaper('A4', 'portrait');
        
        // Render the PDF
        $dompdf->render();
        
        // Generate a filename
        $filename = 'liste_salles_' . date('Y-m-d_H-i-s') . '.pdf';
        
        // Return the PDF as response
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]
        );
    }
} 