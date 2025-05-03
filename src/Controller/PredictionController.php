<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[Route('/prediction')]
class PredictionController extends AbstractController
{
    private string $flaskApiUrl = 'http://127.0.0.1:5000';
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    #[Route('/', name: 'app_prediction_index')]
    public function index(): Response
    {
        return $this->render('prediction/index.html.twig', [
            'api_status' => $this->checkApiStatus()
        ]);
    }

    #[Route('/run-pipeline', name: 'app_prediction_run_pipeline', methods: ['GET'])]
    public function runPipeline(): JsonResponse
    {
        try {
            $response = $this->client->request('GET', $this->flaskApiUrl . '/run-pipeline', [
                'timeout' => 60 // Increase timeout to 60 seconds
            ]);
            
            $statusCode = $response->getStatusCode();
            
            if ($statusCode !== 200) {
                return new JsonResponse([
                    'error' => true,
                    'message' => 'Prediction service returned status code: ' . $statusCode
                ], 500);
            }
            
            $content = $response->getContent();
            return new JsonResponse(json_decode($content, true));
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => true,
                'message' => 'Failed to connect to prediction service: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/get-prediction', name: 'app_prediction_get_prediction', methods: ['GET'])]
    public function getPrediction(): JsonResponse
    {
        try {
            $response = $this->client->request('GET', $this->flaskApiUrl . '/get-prediction', [
                'timeout' => 30
            ]);
            
            $statusCode = $response->getStatusCode();
            
            if ($statusCode !== 200) {
                return new JsonResponse([
                    'error' => true,
                    'message' => 'Prediction service returned status code: ' . $statusCode
                ], 500);
            }
            
            $content = $response->getContent();
            return new JsonResponse(json_decode($content, true));
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => true,
                'message' => 'Failed to connect to prediction service: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Check if the Flask API is running
     */
    private function checkApiStatus(): bool
    {
        try {
            $response = $this->client->request('GET', $this->flaskApiUrl, [
                'timeout' => 2
            ]);
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }
}
