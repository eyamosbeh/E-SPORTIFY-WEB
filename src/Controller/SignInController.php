<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        ]);
    }

    #[Route('/login', name: 'app_login', methods: ['POST'])]
    public function login(Request $request, EntityManagerInterface $entityManager): Response
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');

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

        // Stocker l'utilisateur en session
        $session = $this->requestStack->getSession();
        $session->set('user', [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'role' => $user->getRole(),
            'photo' => $user->getPhoto()
        ]);

        return new JsonResponse([
            'success' => true,
            'redirect' => $user->getRole() === 'admin' ? 
                $this->generateUrl('app_dashboard_admin') : 
                $this->generateUrl('app_home')
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): Response
    {
        // Supprimer l'utilisateur de la session
        $session = $this->requestStack->getSession();
        $session->remove('user');
        
        $this->addFlash('success', 'Déconnexion réussie !');
        return $this->redirectToRoute('app_home');
    }

    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): Response {
        // Création de l'utilisateur avec les données du formulaire
        $user = new Utilisateur();
        $user->setNom($request->request->get('nom'));
        $user->setPrenom($request->request->get('prenom'));
        $user->setEmail($request->request->get('email'));
        $user->setPassword($request->request->get('password'));
        $user->setRole($request->request->get('role'));
        $user->setPhoto('Sign_in/img/user.png');
        $user->setIsBlocked(false);

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
}

