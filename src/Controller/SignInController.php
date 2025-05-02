<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('')]
class SignInController extends AbstractController
{
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    #[Route('/sign-in', name: 'app_sign_in')]
    public function index(): Response
    {
        return $this->render('sign_in/SignIn.html.twig', [
            'controller_name' => 'SignInController',
            'recaptcha_site_key' => $this->getParameter('app.recaptcha_site_key')
        ]);
    }

    #[Route('/login', name: 'app_login', methods: ['POST'])]
    public function login(Request $request, EntityManagerInterface $entityManager): Response
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $rememberMe = $request->request->get('remember_me') === 'on';

        if (!$email || !$password) {
            return new JsonResponse([
                'success' => false,
                'errors' => ['general' => 'Email et mot de passe requis']
            ]);
        }

        $user = $entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);

        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'errors' => ['email' => 'Aucun utilisateur trouvé avec cet email']
            ]);
        }

        if ($user->isBlocked()) {
            return new JsonResponse([
                'success' => false,
                'errors' => ['general' => 'Votre compte a été bloqué. Veuillez contacter l\'administrateur.']
            ]);
        }

        if (!password_verify($password, $user->getPassword())) {
            return new JsonResponse([
                'success' => false,
                'errors' => ['password' => 'Mot de passe incorrect']
            ]);
        }

        // Créer les données de session
        $userData = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'role' => $user->getRole(),
            'photo' => $user->getPhoto()
        ];

        // Stocker l'utilisateur en session
        $session = $this->requestStack->getSession();
        $session->set('user', $userData);

        // Créer la réponse
        $response = new JsonResponse([
            'success' => true,
            'redirect' => $user->getRole() === 'admin' ? 
                $this->generateUrl('app_dashboard_admin') : 
                $this->generateUrl('app_home')
        ]);

        // Si "Remember me" est coché, créer un cookie sécurisé
        if ($rememberMe) {
            try {
                // Générer un token unique pour le cookie
                $token = bin2hex(random_bytes(32));
                
                // Stocker le token en base de données
                $user->setRememberMeToken($token);
                $entityManager->flush();

                // Créer un cookie sécurisé qui expire dans 30 jours
                $cookie = Cookie::create('remember_me')
                    ->withValue($token)
                    ->withExpires(strtotime('+30 days'))
                    ->withPath('/')
                    ->withSecure(true)
                    ->withHttpOnly(true)
                    ->withSameSite('lax');

                $response->headers->setCookie($cookie);
            } catch (\Exception $e) {
                // Si une erreur survient avec le remember me, on continue quand même
                // mais on log l'erreur
                error_log('Erreur remember me: ' . $e->getMessage());
            }
        }

        return $response;
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Récupérer l'utilisateur actuel
        $session = $this->requestStack->getSession();
        $userData = $session->get('user');

        if ($userData) {
            // Supprimer le token remember_me de l'utilisateur en base de données
            $user = $entityManager->getRepository(Utilisateur::class)->find($userData['id']);
            if ($user) {
                $user->setRememberMeToken(null);
                $entityManager->flush();
            }
        }

        // Supprimer l'utilisateur de la session
        $session->remove('user');
        
        // Créer la réponse
        $response = $this->redirectToRoute('app_home');
        
        // Supprimer le cookie remember_me
        $response->headers->clearCookie('remember_me');
        
        $this->addFlash('success', 'Déconnexion réussie !');
        return $response;
    }

    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): Response {
        // Vérifier le reCAPTCHA
        $recaptchaResponse = $request->request->get('g-recaptcha-response');
        
        if (!$recaptchaResponse) {
            return new JsonResponse([
                'success' => false,
                'errors' => ['recaptcha' => 'Veuillez valider le reCAPTCHA']
            ]);
        }

        // Vérifier le token avec l'API Google
        $client = \Symfony\Component\HttpClient\HttpClient::create();
        $response = $client->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
            'body' => [
                'secret' => $this->getParameter('app.recaptcha_secret_key'),
                'response' => $recaptchaResponse
            ]
        ]);

        $recaptchaResult = json_decode($response->getContent(), true);

        if (!$recaptchaResult['success']) {
            return new JsonResponse([
                'success' => false,
                'errors' => ['recaptcha' => 'La vérification reCAPTCHA a échoué. Veuillez réessayer.']
            ]);
        }

        // Création de l'utilisateur avec les données du formulaire
        $user = new Utilisateur();
        $user->setNom($request->request->get('nom'));
        $user->setPrenom($request->request->get('prenom'));
        $user->setEmail($request->request->get('email'));
        $user->setPassword($request->request->get('password'));
        $user->setRole($request->request->get('role'));
        $user->setPhoto('Sign_in/img/user.png');
        $user->setIsBlocked(false);

        // Sauvegarder le descripteur facial si disponible
        $faceImage = $request->request->get('faceImage');
        if ($faceImage) {
            $user->setFaceDescriptor($faceImage);
        }

        // Validation avec les contraintes Assert
        $errors = $validator->validate($user);
        
        // Vérification du mot de passe de confirmation
        if ($request->request->get('password') !== $request->request->get('confirm_password')) {
            $errorArray = ['confirmPassword' => 'Les mots de passe ne correspondent pas'];
        } else {
            $errorArray = [];
        }

        // Conversion des erreurs de validation en tableau
        foreach ($errors as $error) {
            $propertyPath = $error->getPropertyPath();
            // Conversion des noms de propriétés pour correspondre aux data-error
            switch ($propertyPath) {
                case 'password':
                    $errorArray['password'] = $error->getMessage();
                    break;
                case 'email':
                    $errorArray['email'] = $error->getMessage();
                    break;
                case 'nom':
                    $errorArray['nom'] = $error->getMessage();
                    break;
                case 'prenom':
                    $errorArray['prenom'] = $error->getMessage();
                    break;
                case 'role':
                    $errorArray['role'] = $error->getMessage();
                    break;
                default:
                    $errorArray[$propertyPath] = $error->getMessage();
            }
        }

        // Vérification de l'email unique
        $existingUser = $entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $user->getEmail()]);
        if ($existingUser) {
            $errorArray['email'] = 'Cet email est déjà utilisé';
        }

        // Si nous avons des erreurs, retourner la réponse JSON avec les erreurs
        if (!empty($errorArray)) {
            return new JsonResponse([
                'success' => false,
                'errors' => $errorArray
            ]);
        }

        // Si tout est valide, hasher le mot de passe et sauvegarder
        try {
            $user->setPassword(password_hash($user->getPassword(), PASSWORD_BCRYPT));
            $entityManager->persist($user);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Inscription réussie ! Vous pouvez maintenant vous connecter.'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'errors' => ['general' => 'Une erreur est survenue lors de l\'inscription']
            ]);
        }
    }

    #[Route('/face-login', name: 'app_face_login', methods: ['POST'])]
    public function faceLogin(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $faceDescriptor = $data['faceDescriptor'];

        // Récupérer tous les utilisateurs avec un descripteur facial
        $users = $entityManager->getRepository(Utilisateur::class)->findAll();
        $bestMatch = null;
        $bestDistance = 0.6; // Seuil de similarité (plus petit = plus strict)

        foreach ($users as $user) {
            if ($user->getFaceDescriptor()) {
                $storedDescriptor = json_decode($user->getFaceDescriptor(), true);
                
                // Calculer la distance euclidienne entre les descripteurs
                $distance = $this->calculateDistance($faceDescriptor, $storedDescriptor);
                
                if ($distance < $bestDistance) {
                    $bestMatch = $user;
                    $bestDistance = $distance;
                }
            }
        }

        if ($bestMatch) {
            // Connecter l'utilisateur
            $session = $this->requestStack->getSession();
            $session->set('user', [
                'id' => $bestMatch->getId(),
                'email' => $bestMatch->getEmail(),
                'nom' => $bestMatch->getNom(),
                'prenom' => $bestMatch->getPrenom(),
                'role' => $bestMatch->getRole(),
                'photo' => $bestMatch->getPhoto()
            ]);

            return new JsonResponse([
                'success' => true,
                'redirect' => $bestMatch->getRole() === 'admin' ? 
                    $this->generateUrl('app_dashboard_admin') : 
                    $this->generateUrl('app_home')
            ]);
        }

        return new JsonResponse([
            'success' => false,
            'message' => 'Face not recognized'
        ]);
    }

    private function calculateDistance($descriptor1, $descriptor2): float
    {
        $sum = 0;
        for ($i = 0; $i < count($descriptor1); $i++) {
            $diff = $descriptor1[$i] - $descriptor2[$i];
            $sum += $diff * $diff;
        }
        return sqrt($sum);
    }
}

