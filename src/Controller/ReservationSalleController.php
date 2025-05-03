<?php

namespace App\Controller;

use App\Entity\ReservationSalle;
use App\Form\ReservationSalleType;
use App\Repository\ReservationSalleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Knp\Component\Pager\PaginatorInterface;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/reservation/salle')]
class ReservationSalleController extends AbstractController
{
    #[Route('/', name: 'app_reservation_salle_index', methods: ['GET'])]
    public function index(ReservationSalleRepository $reservationSalleRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $search = $request->query->get('search');
        $status = $request->query->get('status');

        $queryBuilder = $reservationSalleRepository->createQueryBuilder('r')
            ->leftJoin('r.salle', 's')
            ->addSelect('s');

        if ($search) {
            $queryBuilder
                ->andWhere('s.nom LIKE :search OR s.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($status) {
            $queryBuilder
                ->andWhere('r.statut = :status')
                ->setParameter('status', $status);
        }

        $queryBuilder->orderBy('r.id', 'DESC');
        $query = $queryBuilder->getQuery();

        // Always use paginator, regardless of total items
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            4 // Show 4 items per page
        );

        return $this->render('reservation_salle/index.html.twig', [
            'pagination' => $pagination,
            'show_pagination' => true // Always show pagination controls
        ]);
    }

    #[Route('/new', name: 'app_reservation_salle_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $reservationSalle = new ReservationSalle();
        $form = $this->createForm(ReservationSalleType::class, $reservationSalle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reservationSalle);
            $entityManager->flush();

            $this->addFlash('success', 'La réservation a été créée avec succès.');
            return $this->redirectToRoute('app_reservation_salle_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation_salle/new.html.twig', [
            'reservation' => $reservationSalle,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_salle_show', methods: ['GET'])]
    public function show(EntityManagerInterface $entityManager, int $id): Response
    {
        $reservationSalle = $entityManager->getRepository(ReservationSalle::class)->find($id);
        
        if (!$reservationSalle) {
            throw $this->createNotFoundException('La réservation demandée n\'existe pas.');
        }

        $deleteForm = $this->createFormBuilder()
            ->setAction($this->generateUrl('app_reservation_salle_delete', ['id' => $reservationSalle->getId()]))
            ->setMethod('POST')
            ->getForm();

        return $this->render('reservation_salle/show.html.twig', [
            'reservation' => $reservationSalle,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reservation_salle_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $entityManager, int $id): Response
    {
        $reservationSalle = $entityManager->getRepository(ReservationSalle::class)->find($id);
        
        if (!$reservationSalle) {
            throw $this->createNotFoundException('La réservation demandée n\'existe pas.');
        }

        $form = $this->createForm(ReservationSalleType::class, $reservationSalle);
        $form->handleRequest($request);

        $deleteForm = $this->createFormBuilder()
            ->setAction($this->generateUrl('app_reservation_salle_delete', ['id' => $reservationSalle->getId()]))
            ->setMethod('POST')
            ->getForm();

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La réservation a été modifiée avec succès.');
            return $this->redirectToRoute('app_reservation_salle_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation_salle/edit.html.twig', [
            'reservation' => $reservationSalle,
            'form' => $form->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_salle_delete', methods: ['POST'])]
    public function delete(Request $request, EntityManagerInterface $entityManager, int $id): Response
    {
        $reservationSalle = $entityManager->getRepository(ReservationSalle::class)->find($id);
        
        if (!$reservationSalle) {
            throw $this->createNotFoundException('La réservation demandée n\'existe pas.');
        }

        if ($this->isCsrfTokenValid('delete'.$reservationSalle->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($reservationSalle);
                $entityManager->flush();
                $this->addFlash('success', 'La réservation a été supprimée avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression de la réservation.');
                return $this->redirectToRoute('app_reservation_salle_show', ['id' => $id]);
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_reservation_salle_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/stats/data', name: 'app_reservation_salle_stats_data', methods: ['GET'])]
    public function getStatisticsData(ReservationSalleRepository $reservationSalleRepository): JsonResponse
    {
        $reservations = $reservationSalleRepository->findAll();
        
        // Initialize statistics arrays
        $statusStats = [];
        $monthlyStats = [];
        $currentYear = (new \DateTime())->format('Y');
        
        foreach ($reservations as $reservation) {
            // Status statistics
            $status = $reservation->getStatut();
            if (!isset($statusStats[$status])) {
                $statusStats[$status] = 0;
            }
            $statusStats[$status]++;
            
            // Monthly statistics for current year
            $dateDebut = $reservation->getDateDebut();
            if ($dateDebut->format('Y') === $currentYear) {
                $month = $dateDebut->format('m/Y');
                if (!isset($monthlyStats[$month])) {
                    $monthlyStats[$month] = [
                        'total' => 0,
                        'Confirmée' => 0,
                        'En attente' => 0,
                        'Annulée' => 0
                    ];
                }
                $monthlyStats[$month]['total']++;
                $monthlyStats[$month][$status]++;
            }
        }
        
        // Sort by month
        ksort($monthlyStats);
        
        // Prepare monthly data for chart
        $monthlyLabels = array_keys($monthlyStats);
        $monthlyConfirmed = array_map(fn($m) => $m['Confirmée'], $monthlyStats);
        $monthlyPending = array_map(fn($m) => $m['En attente'], $monthlyStats);
        $monthlyCanceled = array_map(fn($m) => $m['Annulée'], $monthlyStats);
        
        return new JsonResponse([
            'status' => [
                'labels' => array_keys($statusStats),
                'data' => array_values($statusStats)
            ],
            'monthly' => [
                'labels' => $monthlyLabels,
                'confirmed' => $monthlyConfirmed,
                'pending' => $monthlyPending,
                'canceled' => $monthlyCanceled
            ]
        ]);
    }

    #[Route('/export/pdf', name: 'app_reservation_salle_export_pdf', methods: ['GET'])]
    public function exportPdf(ReservationSalleRepository $reservationSalleRepository): Response
    {
        // Get all reservations
        $reservations = $reservationSalleRepository->findAll();
        
        // Configure Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        // Instantiate Dompdf
        $dompdf = new Dompdf($options);
        
        // Generate HTML content
        $html = $this->renderView('reservation_salle/pdf.html.twig', [
            'reservations' => $reservations,
            'date' => new \DateTime(),
        ]);
        
        // Load HTML to Dompdf
        $dompdf->loadHtml($html);
        
        // Set paper size and orientation
        $dompdf->setPaper('A4', 'portrait');
        
        // Render the PDF
        $dompdf->render();
        
        // Generate a filename
        $filename = 'liste_reservations_' . date('Y-m-d_H-i-s') . '.pdf';
        
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