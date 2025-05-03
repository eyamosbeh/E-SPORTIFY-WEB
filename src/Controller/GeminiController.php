<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use GuzzleHttp\Exception\ClientException;

#[Route('/gemini')]
class GeminiController extends AbstractController
{
    private $apiKey = 'AIzaSyCg964AjbcuJPBG44rxSgZ9gtcjr6jRwHw';

    #[Route('/', name: 'app_gemini_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $response = null;
        $error = null;

        if ($request->isMethod('POST')) {
            $prompt = $request->request->get('prompt');
            
            try {
                $response = $this->generateResponse($prompt);
            } catch (ClientException $e) {
                $responseBody = json_decode($e->getResponse()->getBody(), true);
                if ($e->getResponse()->getStatusCode() === 403) {
                    $error = "The Gemini API needs to be enabled in your Google Cloud Console. Please follow these steps:\n";
                    $error .= "1. Go to https://console.cloud.google.com\n";
                    $error .= "2. Select your project\n";
                    $error .= "3. Go to API & Services > Library\n";
                    $error .= "4. Search for 'Gemini API' or 'Generative Language API'\n";
                    $error .= "5. Click Enable\n";
                    $error .= "\nOriginal error: " . ($responseBody['error']['message'] ?? 'Unknown error');
                } else {
                    $error = $responseBody['error']['message'] ?? $e->getMessage();
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }

        return $this->render('gemini/index.html.twig', [
            'response' => $response,
            'error' => $error
        ]);
    }

    private function generateResponse(string $prompt): string
    {
        $client = new \GuzzleHttp\Client([
            'verify' => false // Disable SSL verification temporarily
        ]);
        
        $response = $client->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $this->apiKey, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return $data['candidates'][0]['content']['parts'][0]['text'];
        }

        throw new \Exception('Failed to generate response from Gemini API');
    }
} 